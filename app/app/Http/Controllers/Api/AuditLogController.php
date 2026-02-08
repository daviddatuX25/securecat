<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/audit-log — Admin only. Paginated list with filters. Per 08-api-spec-phase1 §7.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'action' => 'nullable|string|max:100',
            'entity_type' => 'nullable|string|max:50',
            'entity_id' => 'nullable|string|max:50',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $query = AuditLog::query()->with('user:id,email,first_name,last_name,role');
        if (isset($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }
        if (! empty($validated['action'] ?? '')) {
            $query->where('action', $validated['action']);
        }
        if (! empty($validated['entity_type'] ?? '')) {
            $query->where('entity_type', $validated['entity_type']);
        }
        if (! empty($validated['entity_id'] ?? '')) {
            $query->where('entity_id', $validated['entity_id']);
        }
        if (! empty($validated['date_from'] ?? '')) {
            $query->whereDate('timestamp', '>=', $validated['date_from']);
        }
        if (! empty($validated['date_to'] ?? '')) {
            $query->whereDate('timestamp', '<=', $validated['date_to']);
        }
        $perPage = (int) ($validated['limit'] ?? 15);
        $paginator = $query->orderByDesc('timestamp')->paginate($perPage);

        $data = $paginator->getCollection()->map(function (AuditLog $log): array {
            return [
                'id' => $log->id,
                'user_id' => $log->user_id,
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'email' => $log->user->email,
                    'first_name' => $log->user->first_name,
                    'last_name' => $log->user->last_name,
                    'role' => $log->user->role,
                ] : null,
                'role' => $log->role,
                'action' => $log->action,
                'entity_type' => $log->entity_type,
                'entity_id' => $log->entity_id,
                'ip_address' => $log->ip_address,
                'timestamp' => $log->timestamp?->toIso8601String(),
                'details' => $log->details,
            ];
        })->values()->all();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
