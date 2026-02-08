<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdmissionPeriod;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\ExamAssignment;
use App\Models\ExamSession;
use App\Models\Room;
use App\Models\ScanEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * POST /api/scan — Validate QR, create ScanEntry. Per 08-api-spec-phase1 §5.
 */
class ScanApiTest extends TestCase
{
    use RefreshDatabase;

    private User $proctor;

    private ExamAssignment $assignment;

    private string $validPayload;

    private string $validSignature;

    protected function setUp(): void
    {
        parent::setUp();
        $this->proctor = User::factory()->proctor()->create();
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $period = AdmissionPeriod::create([
            'name' => 'AY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
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
            'proctor_id' => $this->proctor->id,
            'date' => now()->addDays(1)->format('Y-m-d'), // tomorrow so time window can be set
            'start_time' => '08:00',
            'end_time' => '18:00',
            'status' => 'scheduled',
        ]);
        $applicant = Applicant::create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'date_of_birth' => '2005-03-15',
            'encoded_by' => $staff->id,
        ]);
        $application = Application::create([
            'applicant_id' => $applicant->id,
            'course_id' => $course->id,
            'admission_period_id' => $period->id,
            'status' => Application::STATUS_APPROVED,
        ]);
        $this->validPayload = json_encode([
            'applicant_id' => $applicant->id,
            'exam_session_id' => $session->id,
            'generated_at' => now()->toIso8601String(),
        ], JSON_THROW_ON_ERROR);
        $this->validSignature = hash_hmac('sha256', $this->validPayload, config('app.key'));
        $this->assignment = ExamAssignment::create([
            'application_id' => $application->id,
            'exam_session_id' => $session->id,
            'seat_number' => 'A-01',
            'qr_payload' => $this->validPayload,
            'qr_signature' => $this->validSignature,
        ]);
    }

    public function test_valid_scan_returns_200_and_creates_scan_entry(): void
    {
        // Update session to include now; QR payload is NOT changed (no-regenerate policy).
        $session = $this->assignment->examSession;
        $session->update([
            'date' => now()->format('Y-m-d'),
            'start_time' => '00:00',
            'end_time' => '23:59',
        ]);

        $response = $this->actingAs($this->proctor)->postJson('/api/scan', [
            'qr_payload' => $this->validPayload,
            'qr_signature' => $this->validSignature,
            'device_info' => 'Test Device',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('result', 'valid')
            ->assertJsonPath('applicant_name', 'Juan Dela Cruz')
            ->assertJsonPath('exam_session_id', (string) $session->id);

        $this->assertDatabaseHas('scan_entries', [
            'exam_assignment_id' => $this->assignment->id,
            'proctor_id' => $this->proctor->id,
            'validation_result' => 'valid',
            'failure_reason' => null,
        ]);
        $this->assertDatabaseHas('audit_log', [
            'action' => 'scan.validate',
            'entity_type' => 'ScanEntry',
        ]);
        $audit = AuditLog::where('action', 'scan.validate')->latest('timestamp')->first();
        $this->assertNotNull($audit);
        $this->assertSame('valid', $audit->details['result'] ?? null);
        $this->assertNull($audit->details['failure_reason'] ?? null);
        $this->assertEquals($this->assignment->id, $audit->details['exam_assignment_id'] ?? null);
        $this->assertEquals($session->id, $audit->details['exam_session_id'] ?? null);
    }

    public function test_invalid_signature_returns_invalid(): void
    {
        $response = $this->actingAs($this->proctor)->postJson('/api/scan', [
            'qr_payload' => $this->validPayload,
            'qr_signature' => str_repeat('f', 64),
            'device_info' => null,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('result', 'invalid')
            ->assertJsonPath('failure_reason', 'Invalid or tampered QR');

        $this->assertDatabaseHas('scan_entries', [
            'validation_result' => 'invalid',
            'failure_reason' => 'Invalid or tampered QR',
        ]);
        $audit = AuditLog::where('action', 'scan.validate')->latest('timestamp')->first();
        $this->assertNotNull($audit);
        $this->assertSame('invalid', $audit->details['result'] ?? null);
        $this->assertSame('Invalid or tampered QR', $audit->details['failure_reason'] ?? null);
    }

    public function test_already_scanned_returns_invalid(): void
    {
        $session = $this->assignment->examSession;
        $session->update(['date' => now()->format('Y-m-d'), 'start_time' => '00:00', 'end_time' => '23:59']);

        ScanEntry::create([
            'exam_assignment_id' => $this->assignment->id,
            'proctor_id' => $this->proctor->id,
            'scanned_at' => now(),
            'validation_result' => ScanEntry::RESULT_VALID,
            'failure_reason' => null,
        ]);

        $response = $this->actingAs($this->proctor)->postJson('/api/scan', [
            'qr_payload' => $this->validPayload,
            'qr_signature' => $this->validSignature,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('result', 'invalid')
            ->assertJsonPath('failure_reason', 'Already scanned');
        $audit = AuditLog::where('action', 'scan.validate')->latest('timestamp')->first();
        $this->assertNotNull($audit);
        $this->assertSame('invalid', $audit->details['result'] ?? null);
        $this->assertSame('Already scanned', $audit->details['failure_reason'] ?? null);
    }

    public function test_proctor_not_assigned_to_session_returns_invalid(): void
    {
        $otherProctor = User::factory()->proctor()->create();
        $session = $this->assignment->examSession;
        $session->update(['date' => now()->format('Y-m-d'), 'start_time' => '00:00', 'end_time' => '23:59']);

        $response = $this->actingAs($otherProctor)->postJson('/api/scan', [
            'qr_payload' => $this->validPayload,
            'qr_signature' => $this->validSignature,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('result', 'invalid')
            ->assertJsonPath('failure_reason', 'Wrong session');
    }

    /**
     * Session room/date/time changed after QR was generated — scan still succeeds (no-regenerate policy).
     * QR payload has only applicant_id + exam_session_id; session DB record is source of truth.
     */
    public function test_session_edited_after_qr_generated_still_valid(): void
    {
        $session = $this->assignment->examSession;
        // Admin changes session's room, date, and time after the QR was generated.
        $otherRoom = Room::create(['name' => 'Room 999', 'capacity' => 20]);
        $session->update([
            'room_id' => $otherRoom->id,
            'date' => now()->format('Y-m-d'),
            'start_time' => '00:00',
            'end_time' => '23:59',
        ]);

        // Same QR payload + signature — NOT regenerated.
        $response = $this->actingAs($this->proctor)->postJson('/api/scan', [
            'qr_payload' => $this->validPayload,
            'qr_signature' => $this->validSignature,
            'device_info' => 'Test',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('result', 'valid');
        $this->assertDatabaseHas('scan_entries', [
            'exam_assignment_id' => $this->assignment->id,
            'validation_result' => 'valid',
        ]);
    }

    public function test_outside_time_window_returns_invalid(): void
    {
        // Session is tomorrow 08:00–18:00; "now" is today → outside window.
        // Time check uses session DB record, not QR payload.
        $response = $this->actingAs($this->proctor)->postJson('/api/scan', [
            'qr_payload' => $this->validPayload,
            'qr_signature' => $this->validSignature,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('result', 'invalid')
            ->assertJsonPath('failure_reason', 'Outside time window');
        $this->assertDatabaseHas('scan_entries', [
            'exam_assignment_id' => $this->assignment->id,
            'validation_result' => 'invalid',
            'failure_reason' => 'Outside time window',
        ]);
    }

    public function test_staff_cannot_scan(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $response = $this->actingAs($staff)->postJson('/api/scan', [
            'qr_payload' => $this->validPayload,
            'qr_signature' => $this->validSignature,
        ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_scan(): void
    {
        $response = $this->postJson('/api/scan', [
            'qr_payload' => $this->validPayload,
            'qr_signature' => $this->validSignature,
        ]);

        $response->assertStatus(401);
    }
}
