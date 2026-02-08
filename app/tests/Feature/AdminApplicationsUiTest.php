<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdmissionPeriod;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin UI: approval queue list and detail. Per 09-ui-routes-phase1, T3.2.2.
 */
class AdminApplicationsUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_applications_list(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/applications');

        $response->assertStatus(200)->assertSee('Approval queue');
    }

    public function test_admin_can_access_application_detail(): void
    {
        $admin = User::factory()->admin()->create();
        $period = AdmissionPeriod::create([
            'name' => 'AY 2026',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'status' => AdmissionPeriod::STATUS_ACTIVE,
            'created_by' => $admin->id,
        ]);
        $course = Course::create([
            'admission_period_id' => $period->id,
            'name' => 'BSIT',
            'code' => 'BSIT',
        ]);
        $applicant = Applicant::create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'date_of_birth' => '2005-01-15',
            'encoded_by' => $admin->id,
        ]);
        $application = Application::create([
            'applicant_id' => $applicant->id,
            'course_id' => $course->id,
            'admission_period_id' => $period->id,
            'status' => Application::STATUS_PENDING_REVIEW,
        ]);

        $response = $this->actingAs($admin)->get('/admin/applications/' . $application->id);

        $response->assertStatus(200)->assertSee('Application detail');
    }

    public function test_staff_cannot_access_admin_applications(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $response = $this->actingAs($staff)->get('/admin/applications');

        $response->assertRedirect('/');
    }
}
