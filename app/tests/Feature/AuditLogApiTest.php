<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /api/audit-log — Admin only; paginated, filters. Per 08-api-spec-phase1 §7.
 */
class AuditLogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_paginated_audit_log_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        AuditLog::create([
            'user_id' => $admin->id,
            'role' => 'admin',
            'action' => 'application.approve',
            'entity_type' => 'Application',
            'entity_id' => '1',
            'ip_address' => '127.0.0.1',
            'timestamp' => now(),
        ]);

        $response = $this->actingAs($admin)->getJson('/api/audit-log');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'application.approve')
            ->assertJsonPath('data.0.entity_type', 'Application')
            ->assertJsonPath('data.0.entity_id', '1')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_index_respects_filters(): void
    {
        $admin = User::factory()->admin()->create();
        AuditLog::create([
            'user_id' => $admin->id,
            'role' => 'admin',
            'action' => 'application.approve',
            'entity_type' => 'Application',
            'entity_id' => '1',
            'ip_address' => null,
            'timestamp' => now(),
        ]);
        AuditLog::create([
            'user_id' => $admin->id,
            'role' => 'admin',
            'action' => 'course.create',
            'entity_type' => 'Course',
            'entity_id' => '2',
            'ip_address' => null,
            'timestamp' => now(),
        ]);

        $response = $this->actingAs($admin)->getJson('/api/audit-log?action=course.create');

        $response->assertStatus(200)->assertJsonCount(1, 'data')->assertJsonPath('data.0.action', 'course.create');
    }

    public function test_index_staff_gets_403(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $response = $this->actingAs($staff)->getJson('/api/audit-log');

        $response->assertStatus(403);
    }

    public function test_index_unauthenticated_gets_401(): void
    {
        $response = $this->getJson('/api/audit-log');

        $response->assertStatus(401);
    }
}
