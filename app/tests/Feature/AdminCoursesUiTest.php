<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdmissionPeriod;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin UI: courses list/create/edit. Per 09-ui-routes-phase1, T2.2.3.
 */
class AdminCoursesUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_courses_list(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/courses');

        $response->assertStatus(200)->assertSee('Courses');
    }

    public function test_admin_can_access_courses_new(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/courses/new');

        $response->assertStatus(200)->assertSee('New course');
    }

    public function test_admin_can_access_course_edit(): void
    {
        $admin = User::factory()->admin()->create();
        $period = AdmissionPeriod::create([
            'name' => 'Test Period',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'status' => AdmissionPeriod::STATUS_DRAFT,
            'created_by' => $admin->id,
        ]);
        $course = Course::create([
            'admission_period_id' => $period->id,
            'name' => 'BSIT',
            'code' => 'BSIT',
        ]);

        $response = $this->actingAs($admin)->get('/admin/courses/' . $course->id . '/edit');

        $response->assertStatus(200)->assertSee('Edit course');
    }

    public function test_staff_cannot_access_courses_list(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $response = $this->actingAs($staff)->get('/admin/courses');

        $response->assertRedirect('/');
    }

    public function test_staff_cannot_access_courses_new(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $response = $this->actingAs($staff)->get('/admin/courses/new');

        $response->assertRedirect('/');
    }
}
