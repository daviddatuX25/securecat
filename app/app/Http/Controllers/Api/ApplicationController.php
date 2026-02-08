<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdmissionPeriod;
use App\Models\Application;
use App\Models\Applicant;
use App\Models\Course;
use App\Models\ExamAssignment;
use App\Models\ExamSession;
use App\Services\AuditLogger;
use App\Services\QrSigningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * GET list/show and POST approve/reject/request-revision for applications. Per 08-api-spec-phase1 §4.
 * Also: PATCH update (Staff, revision_requested → resubmit); POST assign (Admin, approved → create assignment).
 */
class ApplicationController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly QrSigningService $qrSigning,
    ) {}

    /**
     * GET /api/applications — list with filters. Admin: all; Staff: own encoded only.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Application::query()
            ->with(['applicant:id,first_name,last_name,email,encoded_by', 'course:id,name,code', 'admissionPeriod:id,name'])
            ->orderByDesc('created_at');

        $user = Auth::user();
        if ($user && $user->role === 'staff') {
            $query->whereHas('applicant', fn ($q) => $q->where('encoded_by', $user->getAuthIdentifier()));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('admission_period_id')) {
            $query->where('admission_period_id', $request->input('admission_period_id'));
        }
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->input('course_id'));
        }

        $limit = min((int) ($request->input('limit', 50) ?: 50), 100);
        $page = max(1, (int) ($request->input('page', 1) ?: 1));
        $paginated = $query->paginate($limit, ['*'], 'page', $page);

        $items = $paginated->map(fn (Application $a) => $this->toListItem($a));

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * GET /api/applications/:id — full detail. Admin: all; Staff: only if encoded by them.
     */
    public function show(Application $application): JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->role === 'staff') {
            $applicant = $application->applicant;
            if (! $applicant || (string) $applicant->encoded_by !== (string) $user->getAuthIdentifier()) {
                abort(403, 'You can only view applications you encoded.');
            }
        }

        $application->load(['applicant', 'course', 'secondCourse', 'thirdCourse', 'admissionPeriod', 'reviewedByUser', 'examAssignment.examSession.room']);
        $assignment = $application->examAssignment;

        return response()->json(['data' => $this->toDetailResponse($application, $assignment)]);
    }

    /**
     * POST /api/applications/:id/approve — set status=approved, optional exam assignment + QR.
     */
    public function approve(Request $request, Application $application): JsonResponse
    {
        $this->assertPendingReview($application);

        $validated = $request->validate([
            'exam_session_id' => 'nullable|exists:exam_sessions,id',
            'seat_number' => 'nullable|string|max:10',
        ]);

        $assignment = null;

        $result = DB::transaction(function () use ($application, $validated, &$assignment) {
            $application->update([
                'status' => Application::STATUS_APPROVED,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            $examSessionId = isset($validated['exam_session_id']) ? (int) $validated['exam_session_id'] : null;
            $seatNumber = $validated['seat_number'] ?? null;

            if ($examSessionId !== null) {
                $assignment = $this->createAssignmentWithCapacityCheck($application, $examSessionId, $seatNumber);
            }

            return $this->applicationResponse($application, $assignment);
        });

        $this->auditLogger->log(
            'application.approve',
            'Application',
            (string) $application->id,
            [],
        );

        if ($assignment !== null) {
            $this->auditLogger->log(
                'assignment.create',
                'ExamAssignment',
                (string) $assignment->id,
                [],
            );
        }

        return response()->json($result, 200);
    }

    /**
     * POST /api/applications/:id/reject — set status=rejected, optional admin_notes.
     */
    public function reject(Request $request, Application $application): JsonResponse
    {
        $this->assertPendingReview($application);

        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $application->update([
            'status' => Application::STATUS_REJECTED,
            'admin_notes' => $validated['admin_notes'] ?? null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $this->auditLogger->log(
            'application.reject',
            'Application',
            (string) $application->id,
            array_filter(['admin_notes' => $validated['admin_notes'] ?? null]),
        );

        return response()->json($this->applicationResponse($application, null), 200);
    }

    /**
     * POST /api/applications/:id/request-revision — set status=revision_requested, optional admin_notes.
     */
    public function requestRevision(Request $request, Application $application): JsonResponse
    {
        $this->assertPendingReview($application);

        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $application->update([
            'status' => Application::STATUS_REVISION_REQUESTED,
            'admin_notes' => $validated['admin_notes'] ?? null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $this->auditLogger->log(
            'application.revision_request',
            'Application',
            (string) $application->id,
            array_filter(['admin_notes' => $validated['admin_notes'] ?? null]),
        );

        return response()->json($this->applicationResponse($application, null), 200);
    }

    /**
     * PATCH /api/applications/:id — Staff only. Update applicant + application when status=revision_requested; set status to pending_review.
     */
    public function update(Request $request, Application $application): JsonResponse
    {
        $user = Auth::user();
        if (! $user || $user->role !== 'staff') {
            abort(403, 'Only staff can revise applications.');
        }

        $applicant = $application->applicant;
        if (! $applicant || (string) $applicant->encoded_by !== (string) $user->getAuthIdentifier()) {
            abort(403, 'You can only revise applications you encoded.');
        }

        if ($application->status !== Application::STATUS_REVISION_REQUESTED) {
            throw ValidationException::withMessages([
                'application' => ['Only applications with status "revision_requested" can be revised.'],
            ]);
        }

        $validated = $this->validateRevisionRequest($request);

        $firstCourse = Course::with('admissionPeriod')->findOrFail($validated['first_course_id']);

        DB::transaction(function () use ($applicant, $application, $validated, $firstCourse) {
            $applicant->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'] ?? null,
                'contact_number' => $validated['contact_number'] ?? null,
                'date_of_birth' => $validated['date_of_birth'],
                'address' => $validated['address'] ?? null,
            ]);

            $application->update([
                'course_id' => $validated['first_course_id'],
                'second_course_id' => $validated['second_course_id'] ?? null,
                'third_course_id' => $validated['third_course_id'] ?? null,
                'admission_period_id' => $firstCourse->admission_period_id,
                'status' => Application::STATUS_PENDING_REVIEW,
                'admin_notes' => null,
            ]);
        });

        $this->auditLogger->log(
            'application.resubmit',
            'Application',
            (string) $application->id,
            ['applicant_id' => $applicant->id],
        );

        $application->load(['applicant', 'course', 'secondCourse', 'thirdCourse']);
        $assignment = $application->examAssignment;

        return response()->json(['data' => $this->toDetailResponse($application, $assignment)], 200);
    }

    /**
     * POST /api/applications/:id/assign — Admin only. Create exam assignment for an already-approved application.
     */
    public function assign(Request $request, Application $application): JsonResponse
    {
        if ($application->status !== Application::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'application' => ['Only approved applications can be assigned to a session.'],
            ]);
        }

        if ($application->examAssignment()->exists()) {
            throw ValidationException::withMessages([
                'application' => ['This application is already assigned to a session.'],
            ]);
        }

        $validated = $request->validate([
            'exam_session_id' => 'required|exists:exam_sessions,id',
            'seat_number' => 'nullable|string|max:10',
        ]);

        $assignment = $this->createAssignmentWithCapacityCheck(
            $application,
            (int) $validated['exam_session_id'],
            $validated['seat_number'] ?? null
        );

        $this->auditLogger->log(
            'assignment.create',
            'ExamAssignment',
            (string) $assignment->id,
            [],
        );

        $application->load(['applicant', 'course', 'reviewedByUser', 'examAssignment.examSession.room']);
        return response()->json([
            'data' => $this->toDetailResponse($application, $application->examAssignment),
        ], 200);
    }

    /**
     * Validation rules for PATCH (revision resubmit). Same as POST /api/applicants.
     *
     * @return array<string, mixed>
     */
    private function validateRevisionRequest(Request $request): array
    {
        $rules = [
            'first_name' => 'required|string|min:1|max:100|regex:/^[\pL\s\-]+$/u',
            'last_name' => 'required|string|min:1|max:100|regex:/^[\pL\s\-]+$/u',
            'email' => 'nullable|email',
            'contact_number' => 'nullable|string|min:7|max:20|regex:/^[\d\-\+]+$/',
            'date_of_birth' => 'required|date|before_or_equal:today',
            'address' => 'nullable|string|max:500',
            'first_course_id' => [
                'required',
                'exists:courses,id',
                $this->courseInActivePeriodRule(),
            ],
            'second_course_id' => ['nullable', 'exists:courses,id', $this->courseInActivePeriodRule()],
            'third_course_id' => ['nullable', 'exists:courses,id', $this->courseInActivePeriodRule()],
        ];

        $validated = $request->validate($rules);

        $dob = \Carbon\Carbon::parse($validated['date_of_birth']);
        if ($dob->age < 15) {
            throw ValidationException::withMessages([
                'date_of_birth' => ['Date of birth must indicate age 15 or older.'],
            ]);
        }

        $first = (int) $validated['first_course_id'];
        $second = isset($validated['second_course_id']) ? (int) $validated['second_course_id'] : null;
        $third = isset($validated['third_course_id']) ? (int) $validated['third_course_id'] : null;
        $ids = array_filter([$first, $second, $third]);
        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages([
                'second_course_id' => ['first_course_id, second_course_id, and third_course_id must be distinct.'],
            ]);
        }

        return $validated;
    }

    private function courseInActivePeriodRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $course = Course::with('admissionPeriod')->find($value);
            if (! $course || $course->admissionPeriod?->status !== AdmissionPeriod::STATUS_ACTIVE) {
                $fail('The selected course must belong to an active admission period.');
            }
        };
    }

    private function assertPendingReview(Application $application): void
    {
        if ($application->status !== Application::STATUS_PENDING_REVIEW) {
            throw ValidationException::withMessages([
                'application' => ['Application must be in pending_review status to perform this action.'],
            ]);
        }
    }

    /**
     * Create ExamAssignment with capacity check and QR. Throws ValidationException if room at capacity.
     */
    private function createAssignmentWithCapacityCheck(Application $application, int $examSessionId, ?string $seatNumber): \App\Models\ExamAssignment
    {
        $session = ExamSession::with('room')->findOrFail($examSessionId);

        if (! Schema::hasTable('exam_assignments')) {
            throw ValidationException::withMessages([
                'exam_session_id' => ['Exam assignments are not available. Run migrations.'],
            ]);
        }

        $currentCount = ExamAssignment::where('exam_session_id', $examSessionId)->count();
        if ($currentCount >= $session->room->capacity) {
            throw ValidationException::withMessages([
                'exam_session_id' => ['Room at capacity.'],
            ]);
        }

        $payload = $this->qrSigning->buildPayload(
            $application->applicant_id,
            $session->id,
        );
        $signature = $this->qrSigning->sign($payload);

        return ExamAssignment::create([
            'application_id' => $application->id,
            'exam_session_id' => $examSessionId,
            'seat_number' => $seatNumber,
            'qr_payload' => $payload,
            'qr_signature' => $signature,
        ]);
    }

    /**
     * @param  ExamAssignment|null  $assignment
     * @return array<string, mixed>
     */
    private function applicationResponse(Application $application, $assignment): array
    {
        $application->load(['applicant', 'course', 'reviewedByUser']);

        $data = [
            'id' => $application->id,
            'applicant_id' => $application->applicant_id,
            'course_id' => $application->course_id,
            'status' => $application->status,
            'admin_notes' => $application->admin_notes,
            'reviewed_by' => $application->reviewed_by,
            'reviewed_at' => $application->reviewed_at?->toIso8601String(),
            'applicant' => $application->applicant ? [
                'id' => $application->applicant->id,
                'first_name' => $application->applicant->first_name,
                'last_name' => $application->applicant->last_name,
            ] : null,
            'course' => $application->course ? [
                'id' => $application->course->id,
                'name' => $application->course->name,
                'code' => $application->course->code,
            ] : null,
        ];

        if ($assignment !== null) {
            $data['assignment'] = [
                'id' => $assignment->id,
                'exam_session_id' => $assignment->exam_session_id,
                'seat_number' => $assignment->seat_number,
                'qr_payload' => $assignment->qr_payload,
                'qr_signature' => $assignment->qr_signature,
            ];
        }

        return $data;
    }

    /**
     * @param  ExamAssignment|null  $assignment
     * @return array<string, mixed>
     */
    private function toDetailResponse(Application $application, $assignment): array
    {
        $base = $this->applicationResponse($application, $assignment);

        // Enrich with full applicant for admin detail view
        if ($application->applicant) {
            $base['applicant'] = [
                'id' => $application->applicant->id,
                'first_name' => $application->applicant->first_name,
                'last_name' => $application->applicant->last_name,
                'email' => $application->applicant->email,
                'contact_number' => $application->applicant->contact_number,
                'date_of_birth' => $application->applicant->date_of_birth?->format('Y-m-d'),
                'address' => $application->applicant->address,
            ];
        }

        $base['second_course'] = $application->secondCourse ? [
            'id' => $application->secondCourse->id,
            'name' => $application->secondCourse->name,
            'code' => $application->secondCourse->code,
        ] : null;
        $base['third_course'] = $application->thirdCourse ? [
            'id' => $application->thirdCourse->id,
            'name' => $application->thirdCourse->name,
            'code' => $application->thirdCourse->code,
        ] : null;

        if ($assignment && $assignment->relationLoaded('examSession') && $assignment->examSession) {
            $session = $assignment->examSession;
            $base['assignment']['exam_session'] = [
                'id' => $session->id,
                'date' => $session->date instanceof \DateTimeInterface ? $session->date->format('Y-m-d') : $session->date,
                'start_time' => $session->start_time,
                'end_time' => $session->end_time,
                'room' => $session->room ? ['id' => $session->room->id, 'name' => $session->room->name] : null,
            ];
        }

        return $base;
    }

    /**
     * @return array<string, mixed>
     */
    private function toListItem(Application $application): array
    {
        return [
            'id' => $application->id,
            'applicant_id' => $application->applicant_id,
            'course_id' => $application->course_id,
            'status' => $application->status,
            'admin_notes' => $application->admin_notes,
            'reviewed_at' => $application->reviewed_at?->toIso8601String(),
            'applicant' => $application->applicant ? [
                'id' => $application->applicant->id,
                'first_name' => $application->applicant->first_name,
                'last_name' => $application->applicant->last_name,
            ] : null,
            'course' => $application->course ? [
                'id' => $application->course->id,
                'name' => $application->course->name,
                'code' => $application->course->code,
            ] : null,
            'admission_period' => $application->admissionPeriod ? [
                'id' => $application->admissionPeriod->id,
                'name' => $application->admissionPeriod->name,
            ] : null,
            'created_at' => $application->created_at?->toIso8601String(),
        ];
    }
}
