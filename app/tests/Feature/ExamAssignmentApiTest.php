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
 * GET /api/exam-assignments/:id — assignment + QR for admission slip. Per 08-api-spec-phase1 §4.
 */
class ExamAssignmentApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $staff;

    private ExamAssignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->staff = User::factory()->create(['role' => User::ROLE_STAFF]);

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
        $proctor = User::factory()->proctor()->create();
        $session = ExamSession::create([
            'course_id' => $course->id,
            'room_id' => $room->id,
            'proctor_id' => $proctor->id,
            'date' => '2026-03-15',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'scheduled',
        ]);
        $applicant = Applicant::create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'date_of_birth' => '2005-03-15',
            'encoded_by' => $this->staff->id,
        ]);
        $application = Application::create([
            'applicant_id' => $applicant->id,
            'course_id' => $course->id,
            'admission_period_id' => $period->id,
            'status' => Application::STATUS_APPROVED,
        ]);
        $this->assignment = ExamAssignment::create([
            'application_id' => $application->id,
            'exam_session_id' => $session->id,
            'seat_number' => 'A-01',
            'qr_payload' => '{"applicant_id":1,"exam_session_id":1}',
            'qr_signature' => str_repeat('a', 64),
        ]);
    }

    public function test_show_returns_assignment_with_qr_and_session_for_admin(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/exam-assignments/' . $this->assignment->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->assignment->id)
            ->assertJsonPath('data.seat_number', 'A-01')
            ->assertJsonPath('data.qr_payload', '{"applicant_id":1,"exam_session_id":1}')
            ->assertJsonPath('data.qr_signature', str_repeat('a', 64))
            ->assertJsonStructure(['data' => ['exam_session' => ['room', 'course'], 'applicant']])
            ->assertJsonPath('data.exam_session.room.name', 'Room 101')
            ->assertJsonPath('data.applicant.first_name', 'Juan')
            ->assertJsonPath('data.applicant.last_name', 'Dela Cruz');
    }

    public function test_show_returns_assignment_for_staff(): void
    {
        $response = $this->actingAs($this->staff)->getJson('/api/exam-assignments/' . $this->assignment->id);

        $response->assertStatus(200)->assertJsonPath('data.id', $this->assignment->id);
    }

    public function test_show_returns_404_for_missing_assignment(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/exam-assignments/99999');

        $response->assertStatus(404);
    }

    public function test_proctor_cannot_show_assignment(): void
    {
        $proctor = User::factory()->proctor()->create();
        $response = $this->actingAs($proctor)->getJson('/api/exam-assignments/' . $this->assignment->id);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_show(): void
    {
        $response = $this->getJson('/api/exam-assignments/' . $this->assignment->id);

        $response->assertStatus(401);
    }
}
