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
 * T3.2 — Applications API: GET list/show, POST approve/reject/request-revision. Per 08-api-spec-phase1 §4.
 */
class ApplicationActionApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $staff;

    private Application $pendingApplication;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $period = AdmissionPeriod::create([
            'name' => 'AY 2026-2027',
            'start_date' => '2026-01-15',
            'end_date' => '2026-06-15',
            'status' => AdmissionPeriod::STATUS_ACTIVE,
            'created_by' => $this->admin->id,
        ]);
        $course = Course::create([
            'admission_period_id' => $period->id,
            'name' => 'BSIT',
            'code' => 'BSIT',
        ]);
        $applicant = Applicant::create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'date_of_birth' => '2005-03-15',
            'encoded_by' => $this->staff->id,
        ]);
        $this->pendingApplication = Application::create([
            'applicant_id' => $applicant->id,
            'course_id' => $course->id,
            'admission_period_id' => $period->id,
            'status' => Application::STATUS_PENDING_REVIEW,
        ]);
    }

    public function test_index_returns_applications_for_admin(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/applications?status=pending_review');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta'])
            ->assertJsonPath('data.0.id', $this->pendingApplication->id)
            ->assertJsonPath('data.0.status', Application::STATUS_PENDING_REVIEW)
            ->assertJsonPath('data.0.applicant.first_name', 'Juan')
            ->assertJsonPath('data.0.course.code', 'BSIT');
    }

    public function test_show_returns_application_detail_for_admin(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/applications/' . $this->pendingApplication->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->pendingApplication->id)
            ->assertJsonPath('data.status', Application::STATUS_PENDING_REVIEW)
            ->assertJsonPath('data.applicant.first_name', 'Juan')
            ->assertJsonPath('data.applicant.last_name', 'Dela Cruz')
            ->assertJsonPath('data.course.code', 'BSIT');
    }

    public function test_staff_can_view_only_applications_they_encoded(): void
    {
        $response = $this->actingAs($this->staff)->getJson('/api/applications/' . $this->pendingApplication->id);
        $response->assertStatus(200);

        $otherStaff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $response2 = $this->actingAs($otherStaff)->getJson('/api/applications/' . $this->pendingApplication->id);
        $response2->assertStatus(403);
    }

    public function test_approve_without_assignment_sets_approved_and_audits(): void
    {
        $response = $this->actingAs($this->admin)->postJson(
            '/api/applications/' . $this->pendingApplication->id . '/approve',
            []
        );

        $response->assertStatus(200)
            ->assertJsonPath('status', Application::STATUS_APPROVED)
            ->assertJsonPath('reviewed_by', $this->admin->id)
            ->assertJsonMissingPath('assignment');

        $this->pendingApplication->refresh();
        $this->assertSame(Application::STATUS_APPROVED, $this->pendingApplication->status);
        $this->assertSame($this->admin->id, $this->pendingApplication->reviewed_by);
        $this->assertNotNull($this->pendingApplication->reviewed_at);

        $this->assertDatabaseHas('audit_log', [
            'action' => 'application.approve',
            'entity_type' => 'Application',
            'entity_id' => (string) $this->pendingApplication->id,
        ]);
    }

    public function test_reject_with_admin_notes_sets_rejected_and_audits(): void
    {
        $response = $this->actingAs($this->admin)->postJson(
            '/api/applications/' . $this->pendingApplication->id . '/reject',
            ['admin_notes' => 'Incomplete requirements.']
        );

        $response->assertStatus(200)
            ->assertJsonPath('status', Application::STATUS_REJECTED)
            ->assertJsonPath('admin_notes', 'Incomplete requirements.')
            ->assertJsonPath('reviewed_by', $this->admin->id);

        $this->pendingApplication->refresh();
        $this->assertSame(Application::STATUS_REJECTED, $this->pendingApplication->status);
        $this->assertSame('Incomplete requirements.', $this->pendingApplication->admin_notes);

        $this->assertDatabaseHas('audit_log', [
            'action' => 'application.reject',
            'entity_type' => 'Application',
            'entity_id' => (string) $this->pendingApplication->id,
        ]);
    }

    public function test_request_revision_sets_revision_requested_and_audits(): void
    {
        $response = $this->actingAs($this->admin)->postJson(
            '/api/applications/' . $this->pendingApplication->id . '/request-revision',
            ['admin_notes' => 'Please provide correct date of birth.']
        );

        $response->assertStatus(200)
            ->assertJsonPath('status', Application::STATUS_REVISION_REQUESTED)
            ->assertJsonPath('admin_notes', 'Please provide correct date of birth.');

        $this->assertDatabaseHas('audit_log', [
            'action' => 'application.revision_request',
            'entity_type' => 'Application',
            'entity_id' => (string) $this->pendingApplication->id,
        ]);
    }

    public function test_approve_with_exam_session_creates_assignment_and_qr(): void
    {
        $room = Room::create(['name' => 'Room 101', 'capacity' => 40]);
        $course = Course::first();
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

        $response = $this->actingAs($this->admin)->postJson(
            '/api/applications/' . $this->pendingApplication->id . '/approve',
            [
                'exam_session_id' => $session->id,
                'seat_number' => 'A-01',
            ]
        );

        $response->assertStatus(200)
            ->assertJsonPath('status', Application::STATUS_APPROVED)
            ->assertJsonStructure(['assignment' => ['id', 'exam_session_id', 'seat_number', 'qr_payload', 'qr_signature']])
            ->assertJsonPath('assignment.seat_number', 'A-01')
            ->assertJsonPath('assignment.exam_session_id', $session->id);

        $assignment = ExamAssignment::where('application_id', $this->pendingApplication->id)->first();
        $this->assertNotNull($assignment);
        $this->assertNotEmpty($assignment->qr_payload);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $assignment->qr_signature);

        $this->assertDatabaseHas('audit_log', ['action' => 'application.approve', 'entity_type' => 'Application']);
        $this->assertDatabaseHas('audit_log', ['action' => 'assignment.create', 'entity_type' => 'ExamAssignment']);
    }

    public function test_approve_room_at_capacity_returns_validation_error(): void
    {
        $room = Room::create(['name' => 'Small', 'capacity' => 1]);
        $course = Course::first();
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
        // Fill the only seat
        $otherApplicant = Applicant::create([
            'first_name' => 'Other',
            'last_name' => 'Person',
            'date_of_birth' => '2004-01-01',
            'encoded_by' => $this->staff->id,
        ]);
        $otherApp = Application::create([
            'applicant_id' => $otherApplicant->id,
            'course_id' => $course->id,
            'admission_period_id' => $this->pendingApplication->admission_period_id,
            'status' => Application::STATUS_APPROVED,
        ]);
        ExamAssignment::create([
            'application_id' => $otherApp->id,
            'exam_session_id' => $session->id,
            'seat_number' => 'A-01',
            'qr_payload' => '{}',
            'qr_signature' => str_repeat('a', 64),
        ]);

        $response = $this->actingAs($this->admin)->postJson(
            '/api/applications/' . $this->pendingApplication->id . '/approve',
            ['exam_session_id' => $session->id]
        );

        $response->assertStatus(422)->assertJsonValidationErrors(['exam_session_id']);
    }

    public function test_approve_when_not_pending_review_returns_validation_error(): void
    {
        $this->pendingApplication->update(['status' => Application::STATUS_APPROVED]);

        $response = $this->actingAs($this->admin)->postJson(
            '/api/applications/' . $this->pendingApplication->id . '/approve',
            []
        );

        $response->assertStatus(422)->assertJsonValidationErrors(['application']);
    }

    public function test_reject_when_not_pending_review_returns_validation_error(): void
    {
        $this->pendingApplication->update(['status' => Application::STATUS_REJECTED]);

        $response = $this->actingAs($this->admin)->postJson(
            '/api/applications/' . $this->pendingApplication->id . '/reject',
            []
        );

        $response->assertStatus(422)->assertJsonValidationErrors(['application']);
    }

    public function test_staff_cannot_approve(): void
    {
        $response = $this->actingAs($this->staff)->postJson(
            '/api/applications/' . $this->pendingApplication->id . '/approve',
            []
        );

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_approve(): void
    {
        $response = $this->postJson(
            '/api/applications/' . $this->pendingApplication->id . '/approve',
            []
        );

        $response->assertStatus(401);
    }

    public function test_reject_validates_admin_notes_max_length(): void
    {
        $response = $this->actingAs($this->admin)->postJson(
            '/api/applications/' . $this->pendingApplication->id . '/reject',
            ['admin_notes' => str_repeat('x', 2001)]
        );

        $response->assertStatus(422)->assertJsonValidationErrors(['admin_notes']);
    }

    public function test_staff_can_revise_revision_requested_application_and_resubmit(): void
    {
        $this->pendingApplication->update(['status' => Application::STATUS_REVISION_REQUESTED]);

        $response = $this->actingAs($this->staff)->patchJson(
            '/api/applications/' . $this->pendingApplication->id,
            [
                'first_name' => 'Juan',
                'last_name' => 'Updated',
                'date_of_birth' => '2005-03-15',
                'first_course_id' => $this->pendingApplication->course_id,
            ]
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.status', Application::STATUS_PENDING_REVIEW)
            ->assertJsonPath('data.applicant.last_name', 'Updated');

        $this->pendingApplication->refresh();
        $this->assertSame(Application::STATUS_PENDING_REVIEW, $this->pendingApplication->status);
        $this->assertDatabaseHas('audit_log', [
            'action' => 'application.resubmit',
            'entity_type' => 'Application',
            'entity_id' => (string) $this->pendingApplication->id,
        ]);
    }

    public function test_staff_cannot_revise_when_not_revision_requested(): void
    {
        $response = $this->actingAs($this->staff)->patchJson(
            '/api/applications/' . $this->pendingApplication->id,
            [
                'first_name' => 'Juan',
                'last_name' => 'X',
                'date_of_birth' => '2005-03-15',
                'first_course_id' => $this->pendingApplication->course_id,
            ]
        );

        $response->assertStatus(422)->assertJsonValidationErrors(['application']);
    }

    public function test_assign_creates_assignment_for_approved_application(): void
    {
        $this->pendingApplication->update([
            'status' => Application::STATUS_APPROVED,
            'reviewed_by' => $this->admin->id,
            'reviewed_at' => now(),
        ]);
        $room = Room::create(['name' => 'Room 201', 'capacity' => 50]);
        $proctor = User::factory()->proctor()->create();
        $session = ExamSession::create([
            'course_id' => $this->pendingApplication->course_id,
            'room_id' => $room->id,
            'proctor_id' => $proctor->id,
            'date' => '2026-03-20',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->admin)->postJson(
            '/api/applications/' . $this->pendingApplication->id . '/assign',
            ['exam_session_id' => $session->id, 'seat_number' => 'B-02']
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.assignment.seat_number', 'B-02');
        $this->assertSame((int) $session->id, (int) $response->json('data.assignment.exam_session_id'));

        $this->assertDatabaseHas('exam_assignments', [
            'application_id' => $this->pendingApplication->id,
            'exam_session_id' => $session->id,
        ]);
        $this->assertDatabaseHas('audit_log', ['action' => 'assignment.create', 'entity_type' => 'ExamAssignment']);
    }

    public function test_assign_returns_422_when_already_assigned(): void
    {
        $this->pendingApplication->update([
            'status' => Application::STATUS_APPROVED,
            'reviewed_by' => $this->admin->id,
            'reviewed_at' => now(),
        ]);
        $room = Room::create(['name' => 'Room 301', 'capacity' => 10]);
        $proctor = User::factory()->proctor()->create();
        $session = ExamSession::create([
            'course_id' => $this->pendingApplication->course_id,
            'room_id' => $room->id,
            'proctor_id' => $proctor->id,
            'date' => '2026-03-21',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'scheduled',
        ]);
        ExamAssignment::create([
            'application_id' => $this->pendingApplication->id,
            'exam_session_id' => $session->id,
            'seat_number' => 'C-01',
            'qr_payload' => '{}',
            'qr_signature' => str_repeat('a', 64),
        ]);

        $response = $this->actingAs($this->admin)->postJson(
            '/api/applications/' . $this->pendingApplication->id . '/assign',
            ['exam_session_id' => $session->id]
        );

        $response->assertStatus(422)->assertJsonValidationErrors(['application']);
    }
}
