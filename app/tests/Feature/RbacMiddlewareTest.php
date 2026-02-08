<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RBAC middleware — per 08-api-spec-phase1 §2, T1.3.1.
 */
class RbacMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure(['pending_applications_count', 'upcoming_sessions']);
    }

    public function test_staff_gets_403_on_admin_only_dashboard(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $response = $this->actingAs($staff)->getJson('/api/dashboard');

        $response->assertStatus(403)
            ->assertJson(['error' => 'forbidden']);
    }

    public function test_proctor_gets_403_on_admin_only_dashboard(): void
    {
        $proctor = User::factory()->proctor()->create();

        $response = $this->actingAs($proctor)->getJson('/api/dashboard');

        $response->assertStatus(403)
            ->assertJson(['error' => 'forbidden']);
    }

    public function test_any_authenticated_user_can_logout(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $response = $this->actingAs($staff)->postJson('/api/auth/logout');

        $response->assertStatus(204);
    }
}
