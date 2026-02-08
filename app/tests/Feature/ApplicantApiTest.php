<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdmissionPeriod;
use App\Models\Application;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T3.1.2 — POST /api/applicants (create applicant + application) + audit.
 * Per 08-api-spec-phase1 §4. Staff only.
 */
class ApplicantApiTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private AdmissionPeriod $activePeriod;

    private Course $course1;

    private Course $course2;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = User::factory()->admin()->create();
        $this->staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $this->activePeriod = AdmissionPeriod::create([
            'name' => 'AY 2026-2027',
            'start_date' => '2026-01-15',
            'end_date' => '2026-06-15',
            'status' => AdmissionPeriod::STATUS_ACTIVE,
            'created_by' => $admin->id,
        ]);
        $this->course1 = Course::create([
            'admission_period_id' => $this->activePeriod->id,
            'name' => 'BSIT',
            'code' => 'BSIT',
        ]);
        $this->course2 = Course::create([
            'admission_period_id' => $this->activePeriod->id,
            'name' => 'BSCS',
            'code' => 'BSCS',
        ]);
    }

    public function test_store_creates_applicant_and_application_with_audit(): void
    {
        $payload = [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@email.com',
            'contact_number' => '09171234567',
            'date_of_birth' => '2005-03-15',
            'address' => '123 Main St',
            'first_course_id' => $this->course1->id,
        ];

        $response = $this->actingAs($this->staff)->postJson('/api/applicants', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure(['applicant_id', 'application_id', 'status'])
            ->assertJsonPath('status', Application::STATUS_PENDING_REVIEW);

        $applicantId = $response->json('applicant_id');
        $applicationId = $response->json('application_id');

        $this->assertDatabaseHas('applicants', [
            'id' => $applicantId,
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@email.com',
            'encoded_by' => $this->staff->id,
        ]);
        $this->assertDatabaseHas('applications', [
            'id' => $applicationId,
            'applicant_id' => $applicantId,
            'course_id' => $this->course1->id,
            'status' => Application::STATUS_PENDING_REVIEW,
        ]);
        $this->assertDatabaseHas('audit_log', [
            'action' => 'application.create',
            'entity_type' => 'Application',
            'entity_id' => (string) $applicationId,
        ]);
    }

    public function test_store_accepts_optional_second_and_third_course(): void
    {
        $payload = [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'date_of_birth' => '2004-11-22',
            'first_course_id' => $this->course1->id,
            'second_course_id' => $this->course2->id,
        ];

        $response = $this->actingAs($this->staff)->postJson('/api/applicants', $payload);

        $response->assertStatus(201);
        $application = Application::find($response->json('application_id'));
        $this->assertSame($this->course1->id, $application->course_id);
        $this->assertSame($this->course2->id, $application->second_course_id);
        $this->assertNull($application->third_course_id);
    }

    public function test_store_validates_first_name_required(): void
    {
        $response = $this->actingAs($this->staff)->postJson('/api/applicants', [
            'last_name' => 'Dela Cruz',
            'date_of_birth' => '2005-03-15',
            'first_course_id' => $this->course1->id,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['first_name']);
    }

    public function test_store_validates_first_course_id_required(): void
    {
        $response = $this->actingAs($this->staff)->postJson('/api/applicants', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'date_of_birth' => '2005-03-15',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['first_course_id']);
    }

    public function test_store_validates_course_in_active_period(): void
    {
        $draftPeriod = AdmissionPeriod::create([
            'name' => 'Draft',
            'start_date' => '2027-01-01',
            'end_date' => '2027-06-01',
            'status' => AdmissionPeriod::STATUS_DRAFT,
            'created_by' => User::factory()->admin()->create()->id,
        ]);
        $draftCourse = Course::create([
            'admission_period_id' => $draftPeriod->id,
            'name' => 'Draft Course',
            'code' => 'DRAFT',
        ]);

        $response = $this->actingAs($this->staff)->postJson('/api/applicants', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'date_of_birth' => '2005-03-15',
            'first_course_id' => $draftCourse->id,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['first_course_id']);
    }

    public function test_store_validates_distinct_courses(): void
    {
        $response = $this->actingAs($this->staff)->postJson('/api/applicants', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'date_of_birth' => '2005-03-15',
            'first_course_id' => $this->course1->id,
            'second_course_id' => $this->course1->id,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['second_course_id']);
    }

    public function test_store_validates_age_at_least_15(): void
    {
        $response = $this->actingAs($this->staff)->postJson('/api/applicants', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'date_of_birth' => now()->subYears(14)->format('Y-m-d'),
            'first_course_id' => $this->course1->id,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['date_of_birth']);
    }

    public function test_admin_cannot_post_applicants(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/applicants', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'date_of_birth' => '2005-03-15',
            'first_course_id' => $this->course1->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_post_applicants(): void
    {
        $response = $this->postJson('/api/applicants', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'date_of_birth' => '2005-03-15',
            'first_course_id' => $this->course1->id,
        ]);

        $response->assertStatus(401);
    }
}
