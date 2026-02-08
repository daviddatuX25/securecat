<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * CRUD API: courses. Per 08-api-spec-phase1 §3.
 * Admin only. Audit: course.create, course.update, course.delete.
 */
class CourseController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger
    ) {}

    /**
     * GET /api/courses — list (optional admission_period_id filter).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Course::query()->with('admissionPeriod:id,name,status');
        if ($request->filled('admission_period_id')) {
            $query->where('admission_period_id', $request->input('admission_period_id'));
        }
        $courses = $query->orderBy('name')->get();

        return response()->json(['data' => $courses->map(fn (Course $c) => $this->toResource($c))]);
    }

    /**
     * POST /api/courses — create.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'admission_period_id' => 'required|exists:admission_periods,id',
            'name' => 'required|string|min:1|max:255',
            'code' => [
                'required',
                'string',
                'min:1',
                'max:20',
                Rule::unique('courses')->where('admission_period_id', $request->input('admission_period_id')),
            ],
            'description' => 'nullable|string|max:2000',
        ]);

        $course = Course::create($validated);

        $this->auditLogger->log(
            'course.create',
            'Course',
            (string) $course->id,
            ['entity_id' => $course->id],
        );

        return response()->json(['data' => $this->toResource($course->load('admissionPeriod:id,name,status'))], 201);
    }

    /**
     * GET /api/courses/:id — get one.
     */
    public function show(string $course): JsonResponse
    {
        $model = Course::with('admissionPeriod:id,name,status')->findOrFail($course);

        return response()->json(['data' => $this->toResource($model)]);
    }

    /**
     * PATCH /api/courses/:id — update.
     */
    public function update(Request $request, string $course): JsonResponse
    {
        $model = Course::findOrFail($course);
        $validated = $request->validate([
            'admission_period_id' => 'sometimes|exists:admission_periods,id',
            'name' => 'sometimes|string|min:1|max:255',
            'code' => [
                'sometimes',
                'string',
                'min:1',
                'max:20',
                Rule::unique('courses')
                    ->where('admission_period_id', $request->input('admission_period_id', $model->admission_period_id))
                    ->ignore($model->id),
            ],
            'description' => 'nullable|string|max:2000',
        ]);

        $changed = array_keys($validated);
        $model->update($validated);

        $this->auditLogger->log(
            'course.update',
            'Course',
            (string) $model->id,
            ['entity_id' => $model->id, 'changed_fields' => $changed],
        );

        return response()->json(['data' => $this->toResource($model->load('admissionPeriod:id,name,status'))]);
    }

    /**
     * DELETE /api/courses/:id — delete only if no applications/sessions.
     */
    public function destroy(string $course): JsonResponse|Response
    {
        $model = Course::findOrFail($course);

        $hasSessions = DB::table('exam_sessions')->where('course_id', $model->id)->exists();
        if ($hasSessions) {
            return response()->json([
                'error' => 'conflict',
                'message' => 'Cannot delete course with dependent exam sessions.',
            ], 409);
        }

        $hasApplications = DB::table('applications')
            ->where('course_id', $model->id)
            ->orWhere('second_course_id', $model->id)
            ->orWhere('third_course_id', $model->id)
            ->exists();
        if ($hasApplications) {
            return response()->json([
                'error' => 'conflict',
                'message' => 'Cannot delete course with dependent applications.',
            ], 409);
        }

        $this->auditLogger->log(
            'course.delete',
            'Course',
            (string) $model->id,
            ['entity_id' => $model->id],
        );
        $model->delete();

        return response()->noContent();
    }

    /**
     * @return array<string, mixed>
     */
    private function toResource(Course $course): array
    {
        return [
            'id' => $course->id,
            'admission_period_id' => $course->admission_period_id,
            'name' => $course->name,
            'code' => $course->code,
            'description' => $course->description,
            'created_at' => $course->created_at?->toIso8601String(),
            'updated_at' => $course->updated_at?->toIso8601String(),
        ];
    }
}
