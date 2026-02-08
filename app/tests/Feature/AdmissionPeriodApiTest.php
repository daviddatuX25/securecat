<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdmissionPeriod;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T2.1.2 — CRUD API: admission-periods + audit events. Per 08-api-spec-phase1 §3.
 */
class AdmissionPeriodApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_index_returns_empty_list_when_no_periods(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/admission-periods');

        $response->assertStatus(200)->assertJson(['data' => []]);
    }

    public function test_store_creates_period_and_audit(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/admission-periods', [
            'name' => '2nd Semester AY 2026-2027',
            'start_date' => '2026-01-15',
            'end_date' => '2026-06-15',
            'status' => 'draft',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', '2nd Semester AY 2026-2027')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.start_date', '2026-01-15')
            ->assertJsonPath('data.end_date', '2026-06-15');

        $this->assertDatabaseCount('admission_periods', 1);
        $this->assertDatabaseHas('audit_log', [
            'action' => 'admission_period.create',
            'entity_type' => 'AdmissionPeriod',
        ]);
    }

    public function test_store_validates_dates(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/admission-periods', [
            'name' => 'Test',
            'start_date' => '2026-06-15',
            'end_date' => '2026-01-15',
            'status' => 'draft',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['end_date']);
    }

    public function test_show_returns_period(): void
    {
        $period = AdmissionPeriod::create([
            'name' => 'Fall 2026',
            'start_date' => '2026-08-01',
            'end_date' => '2026-12-15',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/admission-periods/' . $period->id);

        $response->assertStatus(200)->assertJsonPath('data.id', $period->id)->assertJsonPath('data.name', 'Fall 2026');
    }

    public function test_update_modifies_period_and_audits(): void
    {
        $period = AdmissionPeriod::create([
            'name' => 'Original',
            'start_date' => '2026-01-01',
            'end_date' => '2026-05-31',
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->patchJson('/api/admission-periods/' . $period->id, [
            'name' => 'Updated Name',
            'status' => 'active',
        ]);

        $response->assertStatus(200)->assertJsonPath('data.name', 'Updated Name')->assertJsonPath('data.status', 'active');
        $period->refresh();
        $this->assertSame('Updated Name', $period->name);
        $this->assertDatabaseHas('audit_log', [
            'action' => 'admission_period.update',
            'entity_type' => 'AdmissionPeriod',
            'entity_id' => (string) $period->id,
        ]);
    }

    public function test_destroy_deletes_and_audits_when_no_courses(): void
    {
        $period = AdmissionPeriod::create([
            'name' => 'To Delete',
            'start_date' => '2026-01-01',
            'end_date' => '2026-05-31',
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->deleteJson('/api/admission-periods/' . $period->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('admission_periods', ['id' => $period->id]);
        $this->assertDatabaseHas('audit_log', [
            'action' => 'admission_period.delete',
            'entity_type' => 'AdmissionPeriod',
            'entity_id' => (string) $period->id,
        ]);
    }

    public function test_destroy_returns_409_when_has_courses(): void
    {
        $period = AdmissionPeriod::create([
            'name' => 'Has Courses',
            'start_date' => '2026-01-01',
            'end_date' => '2026-05-31',
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);
        \App\Models\Course::create([
            'admission_period_id' => $period->id,
            'name' => 'BSIT',
            'code' => 'BSIT',
        ]);

        $response = $this->actingAs($this->admin)->deleteJson('/api/admission-periods/' . $period->id);

        $response->assertStatus(409)->assertJson(['error' => 'conflict']);
        $this->assertDatabaseHas('admission_periods', ['id' => $period->id]);
    }

    public function test_staff_cannot_access_admission_periods(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $response = $this->actingAs($staff)->getJson('/api/admission-periods');

        $response->assertStatus(403);
    }
}
