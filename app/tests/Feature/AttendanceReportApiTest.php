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
use App\Models\ScanEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /api/reports/attendance/:session_id — assignments with scanned status. Per 08-api-spec-phase1 §6.
 */
class AttendanceReportApiTest extends TestCase
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

    public function test_attendance_returns_assignments_with_scanned_status_for_admin(): void
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
        $assignment = ExamAssignment::create([
            'application_id' => $application->id,
            'exam_session_id' => $this->session->id,
            'seat_number' => 'A-01',
            'qr_payload' => '{}',
            'qr_signature' => str_repeat('a', 64),
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/reports/attendance/' . $this->session->id);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.assignment_id', $assignment->id)
            ->assertJsonPath('data.0.scanned_at', null)
            ->assertJsonPath('data.0.validation_result', null)
            ->assertJsonStructure(['data' => [0 => ['assignment_id', 'seat_number', 'applicant', 'scanned_at', 'validation_result']]]);
    }

    public function test_attendance_includes_scanned_at_and_validation_result_when_scanned(): void
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
        $assignment = ExamAssignment::create([
            'application_id' => $application->id,
            'exam_session_id' => $this->session->id,
            'seat_number' => 'B-02',
            'qr_payload' => '{}',
            'qr_signature' => str_repeat('b', 64),
        ]);
        ScanEntry::create([
            'exam_assignment_id' => $assignment->id,
            'proctor_id' => $this->proctor->id,
            'scanned_at' => now(),
            'validation_result' => ScanEntry::RESULT_VALID,
            'failure_reason' => null,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/reports/attendance/' . $this->session->id);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.validation_result', 'valid')
            ->assertJsonPath('data.0.applicant.first_name', 'Maria');
        $this->assertNotNull($response->json('data.0.scanned_at'));
    }

    public function test_attendance_returns_403_for_proctor_not_assigned_to_session(): void
    {
        $otherProctor = User::factory()->proctor()->create();
        $response = $this->actingAs($otherProctor)->getJson('/api/reports/attendance/' . $this->session->id);

        $response->assertStatus(403)->assertJsonPath('error', 'forbidden');
    }

    public function test_attendance_returns_404_for_missing_session(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/reports/attendance/99999');

        $response->assertStatus(404);
    }
}
