<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdmissionPeriod;
use App\Models\Course;
use App\Models\ExamSession;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin UI: exam sessions list/create/edit (assign proctor). Per 09-ui-routes-phase1, T2.4.3.
 */
class AdminSessionsUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_sessions_list(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/sessions');

        $response->assertStatus(200)->assertSee('Exam sessions');
    }

    public function test_admin_can_access_sessions_new(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/sessions/new');

        $response->assertStatus(200)->assertSee('New exam session');
    }

    public function test_admin_can_access_session_edit(): void
    {
        $admin = User::factory()->admin()->create();
        $proctor = User::factory()->proctor()->create();
        $period = AdmissionPeriod::create([
            'name' => 'AY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-01',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);
        $course = Course::create([
            'admission_period_id' => $period->id,
            'name' => 'BSIT',
            'code' => 'BSIT',
        ]);
        $room = Room::create(['name' => 'Room 101', 'capacity' => 40]);
        $session = ExamSession::create([
            'course_id' => $course->id,
            'room_id' => $room->id,
            'proctor_id' => $proctor->id,
            'date' => now()->addDays(1),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($admin)->get('/admin/sessions/' . $session->id . '/edit');

        $response->assertStatus(200)->assertSee('Edit exam session');
    }

    public function test_staff_cannot_access_sessions_list(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $response = $this->actingAs($staff)->get('/admin/sessions');

        $response->assertRedirect('/');
    }

    public function test_staff_cannot_access_sessions_new(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $response = $this->actingAs($staff)->get('/admin/sessions/new');

        $response->assertRedirect('/');
    }
}
