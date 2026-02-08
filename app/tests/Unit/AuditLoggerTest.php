<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T6.1.2 — Audit logger: single interface log(user, role, action, entity_type, entity_id, ip, timestamp, details).
 */
class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_writes_record_with_explicit_params(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $logger = app(AuditLogger::class);

        $logger->log(
            'application.approve',
            'Application',
            '42',
            ['exam_session_id' => 7],
            $user->id,
            'admin',
            '192.168.1.1',
            new \DateTime('2026-02-08 12:00:00'),
        );

        $this->assertDatabaseCount('audit_log', 1);
        $record = AuditLog::first();
        $this->assertSame((string) $user->id, (string) $record->user_id);
        $this->assertSame('admin', $record->role);
        $this->assertSame('application.approve', $record->action);
        $this->assertSame('Application', $record->entity_type);
        $this->assertSame('42', $record->entity_id);
        $this->assertSame('192.168.1.1', $record->ip_address);
        $this->assertSame(['exam_session_id' => 7], $record->details);
    }

    public function test_log_resolves_user_and_role_from_auth_when_null(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STAFF]);
        $this->actingAs($user);

        app(AuditLogger::class)->log('admission_period.create', 'AdmissionPeriod', '1', []);

        $record = AuditLog::first();
        $this->assertSame((string) $user->id, (string) $record->user_id);
        $this->assertSame('staff', $record->role);
        $this->assertSame('admission_period.create', $record->action);
        $this->assertSame('AdmissionPeriod', $record->entity_type);
        $this->assertSame('1', $record->entity_id);
    }

    public function test_logEvent_static_uses_same_interface(): void
    {
        AuditLogger::logEvent('room.create', 'Room', '3', ['name' => 'Room 101']);

        $this->assertDatabaseCount('audit_log', 1);
        $record = AuditLog::first();
        $this->assertSame('room.create', $record->action);
        $this->assertSame('Room', $record->entity_type);
        $this->assertSame('3', $record->entity_id);
        $this->assertSame(['name' => 'Room 101'], $record->details);
    }
}
