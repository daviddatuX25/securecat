<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdmissionPeriod;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Staff UI: encode applicant form. Per 09-ui-routes-phase1, T3.1.3.
 */
class StaffEncodeUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_access_encode_page(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        AdmissionPeriod::create([
            'name' => 'AY 2026',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'status' => AdmissionPeriod::STATUS_ACTIVE,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($staff)->get('/staff/encode');

        $response->assertStatus(200)
            ->assertSee('Encode Applicant')
            ->assertSee('First name')
            ->assertSee('First preferred course');
    }

    public function test_staff_sees_courses_from_active_periods(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $period = AdmissionPeriod::create([
            'name' => 'AY 2026',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'status' => AdmissionPeriod::STATUS_ACTIVE,
            'created_by' => $admin->id,
        ]);
        Course::create([
            'admission_period_id' => $period->id,
            'name' => 'BS Information Technology',
            'code' => 'BSIT',
        ]);

        $response = $this->actingAs($staff)->get('/staff/encode');

        $response->assertStatus(200)
            ->assertSee('BS Information Technology')
            ->assertSee('BSIT');
    }

    public function test_admin_cannot_access_encode_page(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/staff/encode');

        $response->assertRedirect('/');
    }

    public function test_unauthenticated_redirected_to_login(): void
    {
        $response = $this->get('/staff/encode');

        $response->assertRedirect(route('login'));
    }
}
