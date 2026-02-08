<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin UI: reports — roster, attendance. Per 09-ui-routes-phase1, T5.2.2.
 */
class AdminReportsUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_roster_page(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/reports/roster');

        $response->assertStatus(200)->assertSee('Roster report');
    }

    public function test_admin_can_access_attendance_page(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/reports/attendance');

        $response->assertStatus(200)->assertSee('Attendance report');
    }

    public function test_staff_cannot_access_admin_attendance(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $response = $this->actingAs($staff)->get('/admin/reports/attendance');

        $response->assertRedirect('/');
    }
}
