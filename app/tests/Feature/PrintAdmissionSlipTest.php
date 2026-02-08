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
 * GET /print/admission-slip/:assignment_id — Printable slip with QR. Per 09-ui-routes, T4.1.1.
 */
class PrintAdmissionSlipTest extends TestCase
{
    use RefreshDatabase;

    private ExamAssignment $assignment;

    private User $admin;

    private User $staff;

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
            'qr_payload' => '{"applicant_id":1}',
            'qr_signature' => str_repeat('a', 64),
        ]);
    }

    public function test_admin_can_view_admission_slip(): void
    {
        $response = $this->actingAs($this->admin)->get('/print/admission-slip/' . $this->assignment->id);

        $response->assertStatus(200)
            ->assertSee('Admission slip')
            ->assertSee('Juan Dela Cruz')
            ->assertSee('BSIT')
            ->assertSee('Room 101')
            ->assertSee('A-01')
            ->assertSee('api.qrserver.com'); // QR image URL
    }

    public function test_staff_can_view_admission_slip(): void
    {
        $response = $this->actingAs($this->staff)->get('/print/admission-slip/' . $this->assignment->id);

        $response->assertStatus(200)->assertSee('Juan Dela Cruz');
    }

    public function test_proctor_cannot_view_admission_slip(): void
    {
        $proctor = User::factory()->proctor()->create();
        $response = $this->actingAs($proctor)->get('/print/admission-slip/' . $this->assignment->id);

        // RBAC redirects web requests to / when forbidden
        $response->assertRedirect('/');
    }

    public function test_guest_redirected_to_login(): void
    {
        $response = $this->get('/print/admission-slip/' . $this->assignment->id);

        $response->assertRedirect('/login');
    }

    public function test_returns_404_for_invalid_assignment(): void
    {
        $response = $this->actingAs($this->admin)->get('/print/admission-slip/99999');

        $response->assertStatus(404);
    }
}
