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
 * Proctor UI: session list, scan page, attendance. Per 09-ui-routes-phase1, T4.2.3.
 */
class ProctorUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_proctor_can_access_sessions_list(): void
    {
        $proctor = User::factory()->proctor()->create();
        $response = $this->actingAs($proctor)->get('/proctor/sessions');

        $response->assertStatus(200);
        $response->assertSee('My sessions');
    }

    public function test_proctor_can_access_scan_page_when_assigned_to_session(): void
    {
        $proctor = User::factory()->proctor()->create();
        $period = AdmissionPeriod::create([
            'name' => 'AY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'status' => 'active',
            'created_by' => User::factory()->admin()->create()->id,
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
            'date' => '2026-03-15',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($proctor)->get('/proctor/scan/' . $session->id);

        $response->assertStatus(200);
        $response->assertSee('Scan check-in');
    }

    public function test_proctor_gets_403_for_scan_page_when_not_assigned_to_session(): void
    {
        $proctor = User::factory()->proctor()->create();
        $otherProctor = User::factory()->proctor()->create();
        $period = AdmissionPeriod::create([
            'name' => 'AY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'status' => 'active',
            'created_by' => User::factory()->admin()->create()->id,
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
            'proctor_id' => $otherProctor->id,
            'date' => '2026-03-15',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($proctor)->get('/proctor/scan/' . $session->id);

        $response->assertStatus(403);
    }

    public function test_proctor_can_access_attendance_page_when_assigned_to_session(): void
    {
        $proctor = User::factory()->proctor()->create();
        $period = AdmissionPeriod::create([
            'name' => 'AY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'status' => 'active',
            'created_by' => User::factory()->admin()->create()->id,
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
            'date' => '2026-03-15',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($proctor)->get('/proctor/attendance/' . $session->id);

        $response->assertStatus(200);
        $response->assertSee('Attendance');
    }

    public function test_proctor_home_redirects_to_sessions(): void
    {
        $proctor = User::factory()->proctor()->create();
        $response = $this->actingAs($proctor)->get('/proctor/home');

        $response->assertRedirect('/proctor/sessions');
    }
}
