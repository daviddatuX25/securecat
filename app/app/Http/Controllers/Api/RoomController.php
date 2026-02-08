<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * CRUD API: rooms. Per 08-api-spec-phase1 §3.
 * Admin only. Audit: room.create, room.update, room.delete.
 */
class RoomController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger
    ) {}

    /**
     * GET /api/rooms — list.
     */
    public function index(): JsonResponse
    {
        $rooms = Room::query()->orderBy('name')->get();

        return response()->json(['data' => $rooms->map(fn (Room $r) => $this->toResource($r))]);
    }

    /**
     * POST /api/rooms — create.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:1|max:100',
            'capacity' => 'required|integer|min:1',
            'location_notes' => 'nullable|string|max:1000',
        ]);

        $room = Room::create($validated);

        $this->auditLogger->log(
            'room.create',
            'Room',
            (string) $room->id,
            ['entity_id' => $room->id],
        );

        return response()->json(['data' => $this->toResource($room)], 201);
    }

    /**
     * GET /api/rooms/:id — get one.
     */
    public function show(string $room): JsonResponse
    {
        $model = Room::findOrFail($room);

        return response()->json(['data' => $this->toResource($model)]);
    }

    /**
     * PATCH /api/rooms/:id — update.
     */
    public function update(Request $request, string $room): JsonResponse
    {
        $model = Room::findOrFail($room);
        $validated = $request->validate([
            'name' => 'sometimes|string|min:1|max:100',
            'capacity' => 'sometimes|integer|min:1',
            'location_notes' => 'nullable|string|max:1000',
        ]);

        $changed = array_keys($validated);
        $model->update($validated);

        $this->auditLogger->log(
            'room.update',
            'Room',
            (string) $model->id,
            ['entity_id' => $model->id, 'changed_fields' => $changed],
        );

        return response()->json(['data' => $this->toResource($model)]);
    }

    /**
     * DELETE /api/rooms/:id — delete only if no sessions.
     */
    public function destroy(string $room): JsonResponse|Response
    {
        $model = Room::findOrFail($room);

        $hasSessions = DB::table('exam_sessions')->where('room_id', $model->id)->exists();
        if ($hasSessions) {
            return response()->json([
                'error' => 'conflict',
                'message' => 'Cannot delete room with dependent exam sessions.',
            ], 409);
        }

        $this->auditLogger->log(
            'room.delete',
            'Room',
            (string) $model->id,
            ['entity_id' => $model->id],
        );
        $model->delete();

        return response()->noContent();
    }

    /**
     * @return array<string, mixed>
     */
    private function toResource(Room $room): array
    {
        return [
            'id' => $room->id,
            'name' => $room->name,
            'capacity' => $room->capacity,
            'location_notes' => $room->location_notes,
            'created_at' => $room->created_at?->toIso8601String(),
            'updated_at' => $room->updated_at?->toIso8601String(),
        ];
    }
}
