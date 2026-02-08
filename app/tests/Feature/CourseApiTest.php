<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdmissionPeriod;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T2.2.2 — CRUD API: courses + audit events. Per 08-api-spec-phase1 §3.
 */
class CourseApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private AdmissionPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->period = AdmissionPeriod::create([
            'name' => 'AY 2026-2027',
            'start_date' => '2026-01-15',
            'end_date' => '2026-06-15',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_index_returns_empty_list_when_no_courses(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/courses');

        $response->assertStatus(200)->assertJson(['data' => []]);
    }

    public function test_index_can_filter_by_admission_period_id(): void
    {
        Course::create([
            'admission_period_id' => $this->period->id,
            'name' => 'BSIT',
            'code' => 'BSIT',
        ]);
        $otherPeriod = AdmissionPeriod::create([
            'name' => 'Other',
            'start_date' => '2025-01-01',
            'end_date' => '2025-06-01',
            'status' => 'closed',
            'created_by' => $this->admin->id,
        ]);
        Course::create([
            'admission_period_id' => $otherPeriod->id,
            'name' => 'Other Course',
            'code' => 'OC',
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/courses?admission_period_id=' . $this->period->id);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame('BSIT', $data[0]['code']);
    }

    public function test_store_creates_course_and_audit(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/courses', [
            'admission_period_id' => $this->period->id,
            'name' => 'BS Information Technology',
            'code' => 'BSIT',
            'description' => 'Bachelor of Science in IT',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'BS Information Technology')
            ->assertJsonPath('data.code', 'BSIT')
            ->assertJsonPath('data.admission_period_id', $this->period->id)
            ->assertJsonPath('data.description', 'Bachelor of Science in IT');

        $this->assertDatabaseCount('courses', 1);
        $this->assertDatabaseHas('audit_log', [
            'action' => 'course.create',
            'entity_type' => 'Course',
        ]);
    }

    public function test_store_validates_admission_period_id_required(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/courses', [
            'name' => 'BSIT',
            'code' => 'BSIT',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['admission_period_id']);
    }

    public function test_store_validates_code_unique_per_period(): void
    {
        Course::create([
            'admission_period_id' => $this->period->id,
            'name' => 'Existing',
            'code' => 'BSIT',
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/courses', [
            'admission_period_id' => $this->period->id,
            'name' => 'Another',
            'code' => 'BSIT',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['code']);
    }

    public function test_show_returns_course(): void
    {
        $course = Course::create([
            'admission_period_id' => $this->period->id,
            'name' => 'BSIT',
            'code' => 'BSIT',
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/courses/' . $course->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $course->id)
            ->assertJsonPath('data.name', 'BSIT')
            ->assertJsonPath('data.code', 'BSIT');
    }

    public function test_update_modifies_course_and_audits(): void
    {
        $course = Course::create([
            'admission_period_id' => $this->period->id,
            'name' => 'Original',
            'code' => 'ORIG',
        ]);

        $response = $this->actingAs($this->admin)->patchJson('/api/courses/' . $course->id, [
            'name' => 'Updated Name',
            'description' => 'New description',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.description', 'New description');
        $course->refresh();
        $this->assertSame('Updated Name', $course->name);
        $this->assertDatabaseHas('audit_log', [
            'action' => 'course.update',
            'entity_type' => 'Course',
            'entity_id' => (string) $course->id,
        ]);
    }

    public function test_destroy_deletes_and_audits_when_no_dependents(): void
    {
        $course = Course::create([
            'admission_period_id' => $this->period->id,
            'name' => 'To Delete',
            'code' => 'DEL',
        ]);

        $response = $this->actingAs($this->admin)->deleteJson('/api/courses/' . $course->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
        $this->assertDatabaseHas('audit_log', [
            'action' => 'course.delete',
            'entity_type' => 'Course',
            'entity_id' => (string) $course->id,
        ]);
    }

    public function test_destroy_returns_409_when_has_exam_sessions(): void
    {
        $course = Course::create([
            'admission_period_id' => $this->period->id,
            'name' => 'Has Session',
            'code' => 'HAS',
        ]);
        \Illuminate\Support\Facades\DB::table('rooms')->insert([
            'name' => 'Room 1',
            'capacity' => 40,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('exam_sessions')->insert([
            'course_id' => $course->id,
            'room_id' => 1,
            'date' => '2026-03-15',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'scheduled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->deleteJson('/api/courses/' . $course->id);

        $response->assertStatus(409)->assertJson(['error' => 'conflict']);
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    public function test_destroy_returns_409_when_has_applications(): void
    {
        $course = Course::create([
            'admission_period_id' => $this->period->id,
            'name' => 'Has App',
            'code' => 'APP',
        ]);
        $applicantId = \Illuminate\Support\Facades\DB::table('applicants')->insertGetId([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@test.com',
            'contact_number' => '09171234567',
            'date_of_birth' => '2005-01-01',
            'address' => 'Manila',
            'encoded_by' => $this->admin->id,
            'created_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('applications')->insert([
            'applicant_id' => $applicantId,
            'course_id' => $course->id,
            'admission_period_id' => $this->period->id,
            'status' => 'pending_review',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->deleteJson('/api/courses/' . $course->id);

        $response->assertStatus(409)->assertJson(['error' => 'conflict']);
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    public function test_staff_cannot_access_courses(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $response = $this->actingAs($staff)->getJson('/api/courses');

        $response->assertStatus(403);
    }
}
