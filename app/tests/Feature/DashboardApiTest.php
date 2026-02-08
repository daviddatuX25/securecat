<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdmissionPeriod;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\Course;
use App\Models\ExamSession;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T5.3.1 — Dashboard API: pending count, upcoming sessions. Per 08-api-spec-phase1.
 */
class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_pending_count_and_upcoming_sessions(): void
    {
        $admin = User::factory()->admin()->create();
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

        $applicant1 = Applicant::create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'date_of_birth' => '2005-01-15',
            'encoded_by' => $admin->id,
        ]);
        $applicant2 = Applicant::create([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'date_of_birth' => '2004-06-20',
            'encoded_by' => $admin->id,
        ]);

        Application::create([
            'applicant_id' => $applicant1->id,
            'course_id' => $course->id,
            'admission_period_id' => $period->id,
            'status' => Application::STATUS_PENDING_REVIEW,
        ]);
        Application::create([
            'applicant_id' => $applicant2->id,
            'course_id' => $course->id,
            'admission_period_id' => $period->id,
            'status' => Application::STATUS_PENDING_REVIEW,
        ]);
        Application::create([
            'applicant_id' => $applicant1->id,
            'course_id' => $course->id,
            'admission_period_id' => $period->id,
            'status' => Application::STATUS_APPROVED,
        ]);

        $tomorrow = now()->addDay();
        ExamSession::create([
            'course_id' => $course->id,
            'room_id' => $room->id,
            'date' => $tomorrow,
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($admin)->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('pending_applications_count', 2)
            ->assertJsonStructure([
                'pending_applications_count',
                'upcoming_sessions' => [
                    '*' => ['id', 'date', 'start_time', 'end_time', 'course_name', 'room_name', 'label'],
                ],
            ]);
        $sessions = $response->json('upcoming_sessions');
        $this->assertCount(1, $sessions);
        $this->assertStringContainsString('BSIT', $sessions[0]['label']);
        $this->assertStringContainsString('Room 101', $sessions[0]['label']);
    }

    public function test_dashboard_excludes_past_sessions(): void
    {
        $admin = User::factory()->admin()->create();
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

        ExamSession::create([
            'course_id' => $course->id,
            'room_id' => $room->id,
            'date' => now()->subDay(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($admin)->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('pending_applications_count', 0)
            ->assertJsonPath('upcoming_sessions', []);
    }

    public function test_dashboard_includes_today_sessions(): void
    {
        $admin = User::factory()->admin()->create();
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

        ExamSession::create([
            'course_id' => $course->id,
            'room_id' => $room->id,
            'date' => now()->toDateString(),
            'start_time' => '14:00',
            'end_time' => '16:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($admin)->getJson('/api/dashboard');

        $response->assertStatus(200);
        $sessions = $response->json('upcoming_sessions');
        $this->assertCount(1, $sessions);
        $this->assertSame(now()->toDateString(), $sessions[0]['date']);
    }
}
