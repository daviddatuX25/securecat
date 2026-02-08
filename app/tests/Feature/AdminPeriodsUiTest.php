<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdmissionPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin UI: admission periods list/create/edit. Per 09-ui-routes-phase1, T2.1.3.
 */
class AdminPeriodsUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_periods_list(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/periods');

        $response->assertStatus(200)->assertSee('Admission Periods');
    }

    public function test_admin_can_access_periods_new(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/periods/new');

        $response->assertStatus(200)->assertSee('New admission period');
    }

    public function test_admin_can_access_period_edit(): void
    {
        $admin = User::factory()->admin()->create();
        $period = AdmissionPeriod::create([
            'name' => 'Test Period',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'status' => AdmissionPeriod::STATUS_DRAFT,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get('/admin/periods/' . $period->id . '/edit');

        $response->assertStatus(200)->assertSee('Edit admission period');
    }

    public function test_staff_cannot_access_periods_list(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $response = $this->actingAs($staff)->get('/admin/periods');

        $response->assertRedirect('/');
    }

    public function test_staff_cannot_access_periods_new(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $response = $this->actingAs($staff)->get('/admin/periods/new');

        $response->assertRedirect('/');
    }
}
