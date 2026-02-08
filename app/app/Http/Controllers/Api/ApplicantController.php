<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdmissionPeriod;
use App\Models\Application;
use App\Models\Applicant;
use App\Models\Course;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * POST /api/applicants — create applicant + application in one call.
 * Per 08-api-spec-phase1 §4. Staff only. Audit: application.create.
 */
class ApplicantController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger
    ) {}

    /**
     * POST /api/applicants — create Applicant and Application (status = pending_review) in one call.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateApplicantRequest($request);

        $applicant = new Applicant([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'] ?? null,
            'contact_number' => $validated['contact_number'] ?? null,
            'date_of_birth' => $validated['date_of_birth'],
            'address' => $validated['address'] ?? null,
            'encoded_by' => Auth::id(),
        ]);

        $firstCourse = Course::with('admissionPeriod')->findOrFail($validated['first_course_id']);

        $application = DB::transaction(function () use ($applicant, $validated, $firstCourse) {
            $applicant->save();

            return Application::create([
                'applicant_id' => $applicant->id,
                'course_id' => $validated['first_course_id'],
                'second_course_id' => $validated['second_course_id'] ?? null,
                'third_course_id' => $validated['third_course_id'] ?? null,
                'admission_period_id' => $firstCourse->admission_period_id,
                'status' => Application::STATUS_PENDING_REVIEW,
            ]);
        });

        $this->auditLogger->log(
            'application.create',
            'Application',
            (string) $application->id,
            [
                'applicant_id' => $applicant->id,
                'course_id' => $application->course_id,
            ],
        );

        return response()->json([
            'applicant_id' => $applicant->id,
            'application_id' => $application->id,
            'status' => $application->status,
        ], 201);
    }

    /**
     * Validate POST /api/applicants request body.
     *
     * @return array<string, mixed>
     */
    private function validateApplicantRequest(Request $request): array
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
            'second_course_id' => [
                'nullable',
                'exists:courses,id',
                $this->courseInActivePeriodRule(),
            ],
            'third_course_id' => [
                'nullable',
                'exists:courses,id',
                $this->courseInActivePeriodRule(),
            ],
        ];

        $validated = $request->validate($rules);

        // Custom: date_of_birth age >= 15
        $dob = \Carbon\Carbon::parse($validated['date_of_birth']);
        if ($dob->age < 15) {
            throw ValidationException::withMessages([
                'date_of_birth' => ['Date of birth must indicate age 15 or older.'],
            ]);
        }

        // Custom: second/third distinct from each other and from first
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

    /**
     * Rule: course must belong to an active admission period.
     */
    private function courseInActivePeriodRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $course = Course::with('admissionPeriod')->find($value);
            if (! $course || $course->admissionPeriod?->status !== AdmissionPeriod::STATUS_ACTIVE) {
                $fail('The selected course must belong to an active admission period.');
            }
        };
    }
}
