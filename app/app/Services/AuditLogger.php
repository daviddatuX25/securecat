<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Single audit logging interface for all mutating flows. Per SC-10, 04-data-model.
 * Logs: user, role, action, entity_type, entity_id, ip, timestamp, details.
 */
class AuditLogger
{
    public function log(
        string $action,
        string $entityType,
        string $entityId,
        array $details = [],
        ?int $userId = null,
        ?string $role = null,
        ?string $ipAddress = null,
        ?\DateTimeInterface $timestamp = null,
    ): void {
        $user = $userId !== null ? null : Auth::user();
        $resolvedUserId = $userId ?? ($user instanceof Authenticatable ? $user->getAuthIdentifier() : null);
        $resolvedRole = $role ?? ($user && method_exists($user, 'getAttribute') ? ($user->role ?? null) : null);
        $resolvedIp = $ipAddress ?? Request::ip();
        $resolvedTimestamp = $timestamp ?? now();

        AuditLog::create([
            'user_id' => $resolvedUserId,
            'role' => $resolvedRole ?? '',
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => (string) $entityId,
            'ip_address' => $resolvedIp,
            'timestamp' => $resolvedTimestamp,
            'details' => $details ?: null,
        ]);
    }

    /**
     * Convenience: log using the default logger instance (e.g. when not injecting).
     */
    public static function logEvent(
        string $action,
        string $entityType,
        string $entityId,
        array $details = [],
        ?int $userId = null,
        ?string $role = null,
        ?string $ipAddress = null,
        ?\DateTimeInterface $timestamp = null,
    ): void {
        app(self::class)->log($action, $entityType, $entityId, $details, $userId, $role, $ipAddress, $timestamp);
    }
}
