<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdmissionPeriod;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\Course;
use App\Models\ExamAssignment;
use App\Models\ExamSession;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /api/reports/roster/:session_id — assignments for session with applicant details. Per 08-api-spec-phase1 §6.
 */
class RosterReportApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $proctor;

    private ExamSession $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->proctor = User::factory()->proctor()->create();

        $period = AdmissionPeriod::create([
            'name' => 'AY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);
        $course = Course::create([
            'admission_period_id' => $period->id,
            'name' => 'BSIT',
            'code' => 'BSIT',
        ]);
        $room = Room::create(['name' => 'Room 101', 'capacity' => 40]);
        $this->session = ExamSession::create([
            'course_id' => $course->id,
            'room_id' => $room->id,
            'proctor_id' => $this->proctor->id,
            'date' => '2026-03-15',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'scheduled',
        ]);
    }

    public function test_roster_returns_assignments_for_admin(): void
    {
        $applicant = Applicant::create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@example.com',
            'contact_number' => '09171234567',
            'date_of_birth' => '2005-03-15',
            'encoded_by' => $this->admin->id,
        ]);
        $application = Application::create([
            'applicant_id' => $applicant->id,
            'course_id' => Course::first()->id,
            'admission_period_id' => AdmissionPeriod::first()->id,
            'status' => Application::STATUS_APPROVED,
        ]);
        ExamAssignment::create([
            'application_id' => $application->id,
            'exam_session_id' => $this->session->id,
            'seat_number' => 'A-01',
            'qr_payload' => '{}',
            'qr_signature' => str_repeat('a', 64),
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/reports/roster/' . $this->session->id);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.seat_number', 'A-01')
            ->assertJsonPath('data.0.applicant.first_name', 'Juan')
            ->assertJsonPath('data.0.applicant.last_name', 'Dela Cruz')
            ->assertJsonPath('data.0.applicant.email', 'juan@example.com')
            ->assertJsonPath('data.0.applicant.contact_number', '09171234567')
            ->assertJsonStructure(['data' => [0 => ['assignment_id', 'application_id', 'seat_number', 'applicant']]]);
    }

    public function test_roster_returns_assignments_for_proctor_assigned_to_session(): void
    {
        $applicant = Applicant::create([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'date_of_birth' => '2004-01-01',
            'encoded_by' => $this->admin->id,
        ]);
        $application = Application::create([
            'applicant_id' => $applicant->id,
            'course_id' => Course::first()->id,
            'admission_period_id' => AdmissionPeriod::first()->id,
            'status' => Application::STATUS_APPROVED,
        ]);
        ExamAssignment::create([
            'application_id' => $application->id,
            'exam_session_id' => $this->session->id,
            'seat_number' => 'B-02',
            'qr_payload' => '{}',
            'qr_signature' => str_repeat('b', 64),
        ]);

        $response = $this->actingAs($this->proctor)->getJson('/api/reports/roster/' . $this->session->id);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.applicant.first_name', 'Maria')
            ->assertJsonPath('data.0.seat_number', 'B-02');
    }

    public function test_roster_returns_403_for_proctor_not_assigned_to_session(): void
    {
        $otherProctor = User::factory()->proctor()->create();
        $response = $this->actingAs($otherProctor)->getJson('/api/reports/roster/' . $this->session->id);

        $response->assertStatus(403)
            ->assertJsonPath('error', 'forbidden');
    }

    public function test_roster_returns_404_for_missing_session(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/reports/roster/99999');

        $response->assertStatus(404);
    }

    public function test_roster_returns_empty_list_when_no_assignments(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/reports/roster/' . $this->session->id);

        $response->assertStatus(200)->assertJsonPath('data', []);
    }

    public function test_staff_cannot_access_roster(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $response = $this->actingAs($staff)->getJson('/api/reports/roster/' . $this->session->id);

        $response->assertStatus(403);
    }
}
