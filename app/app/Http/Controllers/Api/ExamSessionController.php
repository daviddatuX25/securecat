<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

/**
 * CRUD API: exam sessions. Per 08-api-spec-phase1 §3.
 * Admin: full CRUD. Proctor: list/show assigned only. Audit: exam_session.create/update/delete.
 * When a session is updated, assignment QR payloads are NOT regenerated; printed slips stay valid. Scan uses session (DB) for time window. See docs/plans/QR-NO-REGENERATE-REFACTOR.md.
 */
class ExamSessionController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * GET /api/exam-sessions — list (Admin: all; Proctor: assigned only).
     */
    public function index(): JsonResponse
    {
        $query = ExamSession::query()
            ->with(['course:id,name,code', 'room:id,name,capacity', 'proctor:id,first_name,last_name,email'])
            ->withCount('examAssignments');
        $user = Auth::user();
        if ($user && $user->role === User::ROLE_PROCTOR) {
            $query->where('proctor_id', $user->getAuthIdentifier());
        }
        $sessions = $query->orderBy('date')->orderBy('start_time')->get();

        return response()->json(['data' => $sessions->map(fn (ExamSession $s) => $this->toResource($s))]);
    }

    /**
     * POST /api/exam-sessions — create (Admin only).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'room_id' => 'required|exists:rooms,id',
            'proctor_id' => [
                'required',
                'exists:users,id',
                Rule::exists('users', 'id')->where('role', User::ROLE_PROCTOR),
            ],
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'status' => ['required', Rule::in(['scheduled', 'in_progress', 'completed'])],
        ]);

        $session = ExamSession::create($validated);

        $this->auditLogger->log(
            'exam_session.create',
            'ExamSession',
            (string) $session->id,
            ['entity_id' => $session->id],
        );

        return response()->json(['data' => $this->toResource($session->load(['course', 'room', 'proctor']))], 201);
    }

    /**
     * GET /api/exam-sessions/:id — get one (Proctor: only if assigned).
     */
    public function show(string $exam_session): JsonResponse
    {
        $model = ExamSession::with(['course', 'room', 'proctor'])->findOrFail($exam_session);
        $user = Auth::user();
        if ($user && $user->role === User::ROLE_PROCTOR && (int) $model->proctor_id !== (int) $user->getAuthIdentifier()) {
            abort(403, 'Not assigned to this session.');
        }

        return response()->json(['data' => $this->toResource($model)]);
    }

    /**
     * PATCH /api/exam-sessions/:id — update (Admin only).
     */
    public function update(Request $request, string $exam_session): JsonResponse
    {
        $model = ExamSession::findOrFail($exam_session);
        $validated = $request->validate([
            'course_id' => 'sometimes|exists:courses,id',
            'room_id' => 'sometimes|exists:rooms,id',
            'proctor_id' => [
                'sometimes',
                'exists:users,id',
                Rule::exists('users', 'id')->where('role', User::ROLE_PROCTOR),
            ],
            'date' => 'sometimes|date',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i',
            'status' => ['sometimes', Rule::in(['scheduled', 'in_progress', 'completed'])],
        ]);
        $start = $validated['start_time'] ?? $model->getRawOriginal('start_time');
        $end = $validated['end_time'] ?? $model->getRawOriginal('end_time');
        if (is_string($start) && strlen($start) > 5) {
            $start = substr($start, 0, 5);
        }
        if (is_string($end) && strlen($end) > 5) {
            $end = substr($end, 0, 5);
        }
        if ($start >= $end) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'end_time' => ['The end time must be after the start time.'],
            ]);
        }

        $changed = array_keys($validated);
        $model->update($validated);

        $this->auditLogger->log(
            'exam_session.update',
            'ExamSession',
            (string) $model->id,
            ['entity_id' => $model->id, 'changed_fields' => $changed],
        );

        return response()->json(['data' => $this->toResource($model->load(['course', 'room', 'proctor']))]);
    }

    /**
     * DELETE /api/exam-sessions/:id — delete only if no assignments (Admin only).
     */
    public function destroy(string $exam_session): JsonResponse|Response
    {
        $model = ExamSession::findOrFail($exam_session);

        if (Schema::hasTable('exam_assignments') &&
            DB::table('exam_assignments')->where('exam_session_id', $model->id)->exists()) {
            return response()->json([
                'error' => 'conflict',
                'message' => 'Cannot delete exam session with existing assignments.',
            ], 409);
        }

        $this->auditLogger->log(
            'exam_session.delete',
            'ExamSession',
            (string) $model->id,
            ['entity_id' => $model->id],
        );
        $model->delete();

        return response()->noContent();
    }

    /**
     * @return array<string, mixed>
     */
    private function toResource(ExamSession $session): array
    {
        $startTime = $session->start_time;
        $endTime = $session->end_time;
        if ($startTime instanceof \Carbon\CarbonInterface) {
            $startTime = $startTime->format('H:i');
        } elseif (is_string($startTime) && strlen($startTime) > 5) {
            $startTime = substr($startTime, 0, 5);
        }
        if ($endTime instanceof \Carbon\CarbonInterface) {
            $endTime = $endTime->format('H:i');
        } elseif (is_string($endTime) && strlen($endTime) > 5) {
            $endTime = substr($endTime, 0, 5);
        }

        return [
            'id' => $session->id,
            'course_id' => $session->course_id,
            'room_id' => $session->room_id,
            'proctor_id' => $session->proctor_id,
            'date' => $session->date instanceof \DateTimeInterface ? $session->date->format('Y-m-d') : $session->date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => $session->status,
            'course' => $session->relationLoaded('course') && $session->course
                ? ['id' => $session->course->id, 'name' => $session->course->name, 'code' => $session->course->code]
                : null,
            'room' => $session->relationLoaded('room') && $session->room
                ? ['id' => $session->room->id, 'name' => $session->room->name, 'capacity' => $session->room->capacity]
                : null,
            'assignments_count' => isset($session->exam_assignments_count) ? (int) $session->exam_assignments_count : null,
            'proctor' => $session->relationLoaded('proctor') && $session->proctor
                ? ['id' => $session->proctor->id, 'first_name' => $session->proctor->first_name, 'last_name' => $session->proctor->last_name, 'email' => $session->proctor->email]
                : null,
            'created_at' => $session->created_at?->toIso8601String(),
            'updated_at' => $session->updated_at?->toIso8601String(),
        ];
    }
}
