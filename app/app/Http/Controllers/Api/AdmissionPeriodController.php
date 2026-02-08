<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdmissionPeriod;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * CRUD API: admission periods. Per 08-api-spec-phase1 §3.
 * Admin only. Audit: admission_period.create, .update, .delete.
 */
class AdmissionPeriodController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger
    ) {}

    /**
     * GET /api/admission-periods — list (optional status filter).
     */
    public function index(Request $request): JsonResponse
    {
        $query = AdmissionPeriod::query()->with('createdByUser:id,first_name,last_name');
        if ($request->has('status') && $request->string('status')->isNotEmpty()) {
            $status = $request->input('status');
            if (in_array($status, [AdmissionPeriod::STATUS_DRAFT, AdmissionPeriod::STATUS_ACTIVE, AdmissionPeriod::STATUS_CLOSED], true)) {
                $query->where('status', $status);
            }
        }
        $periods = $query->orderBy('start_date', 'desc')->get();

        return response()->json(['data' => $periods->map(fn (AdmissionPeriod $p) => $this->toResource($p))]);
    }

    /**
     * POST /api/admission-periods — create.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:1|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|string|in:draft,active,closed',
        ]);

        $user = $request->user();
        $period = AdmissionPeriod::create([
            ...$validated,
            'created_by' => $user->getAuthIdentifier(),
        ]);

        $this->auditLogger->log(
            'admission_period.create',
            'AdmissionPeriod',
            (string) $period->id,
            ['entity_id' => $period->id],
        );

        return response()->json(['data' => $this->toResource($period->load('createdByUser:id,first_name,last_name'))], 201);
    }

    /**
     * GET /api/admission-periods/:id — get one.
     */
    public function show(string $admission_period): JsonResponse
    {
        $period = AdmissionPeriod::with('createdByUser:id,first_name,last_name')->findOrFail($admission_period);

        return response()->json(['data' => $this->toResource($period)]);
    }

    /**
     * PATCH /api/admission-periods/:id — update.
     */
    public function update(Request $request, string $admission_period): JsonResponse
    {
        $period = AdmissionPeriod::findOrFail($admission_period);
        $validated = $request->validate([
            'name' => 'sometimes|string|min:1|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'status' => 'sometimes|string|in:draft,active,closed',
        ]);

        $changed = array_keys($validated);
        $period->update($validated);

        $this->auditLogger->log(
            'admission_period.update',
            'AdmissionPeriod',
            (string) $period->id,
            ['entity_id' => $period->id, 'changed_fields' => $changed],
        );

        return response()->json(['data' => $this->toResource($period->load('createdByUser:id,first_name,last_name'))]);
    }

    /**
     * DELETE /api/admission-periods/:id — delete only if no dependent courses.
     */
    public function destroy(string $admission_period): JsonResponse|Response
    {
        $period = AdmissionPeriod::findOrFail($admission_period);
        if ($period->courses()->exists()) {
            return response()->json([
                'error' => 'conflict',
                'message' => 'Cannot delete admission period with dependent courses.',
            ], 409);
        }

        $this->auditLogger->log(
            'admission_period.delete',
            'AdmissionPeriod',
            (string) $period->id,
            ['entity_id' => $period->id],
        );
        $period->delete();

        return response()->noContent();
    }

    /**
     * @return array<string, mixed>
     */
    private function toResource(AdmissionPeriod $period): array
    {
        return [
            'id' => $period->id,
            'name' => $period->name,
            'start_date' => $period->start_date->format('Y-m-d'),
            'end_date' => $period->end_date->format('Y-m-d'),
            'status' => $period->status,
            'created_by' => $period->created_by,
            'created_at' => $period->created_at?->toIso8601String(),
            'updated_at' => $period->updated_at?->toIso8601String(),
        ];
    }
}
