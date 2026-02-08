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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T2.4.2 — CRUD API: exam-sessions (include proctor_id) + audit events. Per 08-api-spec-phase1 §3.
 */
class ExamSessionApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $proctor;

    private Course $course;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->proctor = User::factory()->proctor()->create();
        $period = AdmissionPeriod::create([
            'name' => 'AY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-01',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);
        $this->course = Course::create([
            'admission_period_id' => $period->id,
            'name' => 'BSIT',
            'code' => 'BSIT',
        ]);
        $this->room = Room::create(['name' => 'Room 101', 'capacity' => 40]);
    }

    private function validPayload(): array
    {
        return [
            'course_id' => $this->course->id,
            'room_id' => $this->room->id,
            'proctor_id' => $this->proctor->id,
            'date' => now()->addDays(2)->format('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'scheduled',
        ];
    }

    public function test_index_returns_empty_list_when_no_sessions(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/exam-sessions');

        $response->assertStatus(200)->assertJson(['data' => []]);
    }

    public function test_index_admin_sees_all_sessions(): void
    {
        ExamSession::create([
            'course_id' => $this->course->id,
            'room_id' => $this->room->id,
            'proctor_id' => $this->proctor->id,
            'date' => now()->addDays(1),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/exam-sessions');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_index_proctor_sees_only_assigned_sessions(): void
    {
        $otherProctor = User::factory()->proctor()->create();
        $assigned = ExamSession::create([
            'course_id' => $this->course->id,
            'room_id' => $this->room->id,
            'proctor_id' => $this->proctor->id,
            'date' => now()->addDays(1),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'scheduled',
        ]);
        ExamSession::create([
            'course_id' => $this->course->id,
            'room_id' => $this->room->id,
            'proctor_id' => $otherProctor->id,
            'date' => now()->addDays(2),
            'start_time' => '14:00',
            'end_time' => '16:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->proctor)->getJson('/api/exam-sessions');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame((string) $assigned->id, (string) $data[0]['id']);
    }

    public function test_store_creates_session_and_audit(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/exam-sessions', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonPath('data.course_id', $this->course->id)
            ->assertJsonPath('data.room_id', $this->room->id)
            ->assertJsonPath('data.proctor_id', $this->proctor->id)
            ->assertJsonPath('data.start_time', '08:00')
            ->assertJsonPath('data.end_time', '10:00')
            ->assertJsonPath('data.status', 'scheduled');

        $this->assertDatabaseCount('exam_sessions', 1);
        $this->assertDatabaseHas('audit_log', [
            'action' => 'exam_session.create',
            'entity_type' => 'ExamSession',
        ]);
    }

    public function test_store_validates_proctor_must_be_proctor_role(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $payload = $this->validPayload();
        $payload['proctor_id'] = $staff->id;

        $response = $this->actingAs($this->admin)->postJson('/api/exam-sessions', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['proctor_id']);
    }

    public function test_store_validates_date_not_in_past(): void
    {
        $payload = $this->validPayload();
        $payload['date'] = now()->subDay()->format('Y-m-d');

        $response = $this->actingAs($this->admin)->postJson('/api/exam-sessions', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['date']);
    }

    public function test_store_validates_end_time_after_start_time(): void
    {
        $payload = $this->validPayload();
        $payload['start_time'] = '10:00';
        $payload['end_time'] = '08:00';

        $response = $this->actingAs($this->admin)->postJson('/api/exam-sessions', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['end_time']);
    }

    public function test_store_validates_status_enum(): void
    {
        $payload = $this->validPayload();
        $payload['status'] = 'invalid';

        $response = $this->actingAs($this->admin)->postJson('/api/exam-sessions', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['status']);
    }

    public function test_show_returns_session_for_admin(): void
    {
        $session = ExamSession::create([
            'course_id' => $this->course->id,
            'room_id' => $this->room->id,
            'proctor_id' => $this->proctor->id,
            'date' => now()->addDays(1),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/exam-sessions/' . $session->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $session->id)
            ->assertJsonPath('data.status', 'scheduled');
    }

    public function test_show_proctor_can_access_assigned_session(): void
    {
        $session = ExamSession::create([
            'course_id' => $this->course->id,
            'room_id' => $this->room->id,
            'proctor_id' => $this->proctor->id,
            'date' => now()->addDays(1),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->proctor)->getJson('/api/exam-sessions/' . $session->id);

        $response->assertStatus(200)->assertJsonPath('data.id', $session->id);
    }

    public function test_show_proctor_gets_403_for_unassigned_session(): void
    {
        $otherProctor = User::factory()->proctor()->create();
        $session = ExamSession::create([
            'course_id' => $this->course->id,
            'room_id' => $this->room->id,
            'proctor_id' => $otherProctor->id,
            'date' => now()->addDays(1),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->proctor)->getJson('/api/exam-sessions/' . $session->id);

        $response->assertStatus(403);
    }

    public function test_update_modifies_session_and_audits(): void
    {
        $session = ExamSession::create([
            'course_id' => $this->course->id,
            'room_id' => $this->room->id,
            'proctor_id' => $this->proctor->id,
            'date' => now()->addDays(1),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->admin)->patchJson('/api/exam-sessions/' . $session->id, [
            'status' => 'in_progress',
        ]);

        $response->assertStatus(200)->assertJsonPath('data.status', 'in_progress');
        $this->assertDatabaseHas('audit_log', [
            'action' => 'exam_session.update',
            'entity_type' => 'ExamSession',
            'entity_id' => (string) $session->id,
        ]);
    }

    public function test_update_does_not_regenerate_qr_assignments_stay_valid(): void
    {
        $session = ExamSession::create([
            'course_id' => $this->course->id,
            'room_id' => $this->room->id,
            'proctor_id' => $this->proctor->id,
            'date' => '2026-03-15',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'scheduled',
        ]);
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $applicant = Applicant::create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'date_of_birth' => '2005-01-01',
            'encoded_by' => $staff->id,
        ]);
        $application = Application::create([
            'applicant_id' => $applicant->id,
            'course_id' => $this->course->id,
            'admission_period_id' => $this->course->admission_period_id,
            'status' => 'approved',
        ]);
        $originalPayload = json_encode([
            'applicant_id' => $applicant->id,
            'exam_session_id' => $session->id,
            'generated_at' => '2026-01-01T00:00:00+00:00',
        ], JSON_THROW_ON_ERROR);
        $assignment = ExamAssignment::create([
            'application_id' => $application->id,
            'exam_session_id' => $session->id,
            'seat_number' => 'A-01',
            'qr_payload' => $originalPayload,
            'qr_signature' => str_repeat('a', 64),
        ]);

        $this->actingAs($this->admin)->patchJson('/api/exam-sessions/' . $session->id, [
            'date' => '2026-03-20',
            'start_time' => '09:00',
            'end_time' => '11:00',
        ])->assertStatus(200);

        $assignment->refresh();
        $this->assertSame($originalPayload, $assignment->qr_payload);
        $this->assertSame(str_repeat('a', 64), $assignment->qr_signature);
        $this->assertDatabaseMissing('audit_log', [
            'action' => 'assignment.qr_regenerate',
        ]);
    }

    public function test_destroy_deletes_and_audits_when_no_assignments(): void
    {
        $session = ExamSession::create([
            'course_id' => $this->course->id,
            'room_id' => $this->room->id,
            'proctor_id' => $this->proctor->id,
            'date' => now()->addDays(1),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->admin)->deleteJson('/api/exam-sessions/' . $session->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('exam_sessions', ['id' => $session->id]);
        $this->assertDatabaseHas('audit_log', [
            'action' => 'exam_session.delete',
            'entity_type' => 'ExamSession',
            'entity_id' => (string) $session->id,
        ]);
    }

    public function test_destroy_returns_409_when_has_assignments(): void
    {
        $session = ExamSession::create([
            'course_id' => $this->course->id,
            'room_id' => $this->room->id,
            'proctor_id' => $this->proctor->id,
            'date' => now()->addDays(1),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'scheduled',
        ]);
        $applicantId = DB::table('applicants')->insertGetId([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@test.com',
            'contact_number' => '09171234567',
            'date_of_birth' => '2005-01-01',
            'address' => 'Manila',
            'encoded_by' => $this->admin->id,
            'created_at' => now(),
        ]);
        $applicationId = DB::table('applications')->insertGetId([
            'applicant_id' => $applicantId,
            'course_id' => $this->course->id,
            'admission_period_id' => $this->course->admission_period_id,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('exam_assignments')->insert([
            'exam_session_id' => $session->id,
            'application_id' => $applicationId,
            'assigned_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->deleteJson('/api/exam-sessions/' . $session->id);

        $response->assertStatus(409)->assertJson(['error' => 'conflict']);
        $this->assertDatabaseHas('exam_sessions', ['id' => $session->id]);
    }

    public function test_staff_cannot_access_exam_sessions_list(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $response = $this->actingAs($staff)->getJson('/api/exam-sessions');

        $response->assertStatus(403);
    }

    public function test_proctor_cannot_store_session(): void
    {
        $response = $this->actingAs($this->proctor)->postJson('/api/exam-sessions', $this->validPayload());

        $response->assertStatus(403);
    }

    public function test_proctor_cannot_update_or_destroy_session(): void
    {
        $session = ExamSession::create([
            'course_id' => $this->course->id,
            'room_id' => $this->room->id,
            'proctor_id' => $this->proctor->id,
            'date' => now()->addDays(1),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'scheduled',
        ]);

        $this->actingAs($this->proctor)->patchJson('/api/exam-sessions/' . $session->id, ['status' => 'completed'])
            ->assertStatus(403);
        $this->actingAs($this->proctor)->deleteJson('/api/exam-sessions/' . $session->id)
            ->assertStatus(403);
    }
}
