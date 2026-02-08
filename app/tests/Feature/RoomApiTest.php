<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T2.3.2 — CRUD API: rooms + audit events. Per 08-api-spec-phase1 §3.
 */
class RoomApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_index_returns_empty_list_when_no_rooms(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/rooms');

        $response->assertStatus(200)->assertJson(['data' => []]);
    }

    public function test_index_returns_rooms_ordered_by_name(): void
    {
        Room::create(['name' => 'Room B', 'capacity' => 30]);
        Room::create(['name' => 'Room A', 'capacity' => 40]);

        $response = $this->actingAs($this->admin)->getJson('/api/rooms');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
        $this->assertSame('Room A', $data[0]['name']);
        $this->assertSame('Room B', $data[1]['name']);
    }

    public function test_store_creates_room_and_audit(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/rooms', [
            'name' => 'Room 101',
            'capacity' => 40,
            'location_notes' => 'Building A, 1st floor',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Room 101')
            ->assertJsonPath('data.capacity', 40)
            ->assertJsonPath('data.location_notes', 'Building A, 1st floor');

        $this->assertDatabaseCount('rooms', 1);
        $this->assertDatabaseHas('audit_log', [
            'action' => 'room.create',
            'entity_type' => 'Room',
        ]);
    }

    public function test_store_validates_name_required(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/rooms', [
            'capacity' => 40,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_store_validates_name_max_100(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/rooms', [
            'name' => str_repeat('x', 101),
            'capacity' => 40,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_store_validates_capacity_min_one(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/rooms', [
            'name' => 'Room 1',
            'capacity' => 0,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['capacity']);
    }

    public function test_store_accepts_null_location_notes(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/rooms', [
            'name' => 'Room 1',
            'capacity' => 25,
        ]);

        $response->assertStatus(201)->assertJsonPath('data.location_notes', null);
    }

    public function test_show_returns_room(): void
    {
        $room = Room::create(['name' => 'Room 101', 'capacity' => 40]);

        $response = $this->actingAs($this->admin)->getJson('/api/rooms/' . $room->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $room->id)
            ->assertJsonPath('data.name', 'Room 101')
            ->assertJsonPath('data.capacity', 40);
    }

    public function test_update_modifies_room_and_audits(): void
    {
        $room = Room::create(['name' => 'Original', 'capacity' => 30]);

        $response = $this->actingAs($this->admin)->patchJson('/api/rooms/' . $room->id, [
            'name' => 'Updated Room',
            'capacity' => 50,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Room')
            ->assertJsonPath('data.capacity', 50);
        $room->refresh();
        $this->assertSame('Updated Room', $room->name);
        $this->assertDatabaseHas('audit_log', [
            'action' => 'room.update',
            'entity_type' => 'Room',
            'entity_id' => (string) $room->id,
        ]);
    }

    public function test_destroy_deletes_and_audits_when_no_sessions(): void
    {
        $room = Room::create(['name' => 'To Delete', 'capacity' => 20]);

        $response = $this->actingAs($this->admin)->deleteJson('/api/rooms/' . $room->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
        $this->assertDatabaseHas('audit_log', [
            'action' => 'room.delete',
            'entity_type' => 'Room',
            'entity_id' => (string) $room->id,
        ]);
    }

    public function test_destroy_returns_409_when_has_exam_sessions(): void
    {
        $room = Room::create(['name' => 'Room With Session', 'capacity' => 40]);
        $period = \App\Models\AdmissionPeriod::create([
            'name' => 'AY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-01',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);
        $course = \App\Models\Course::create([
            'admission_period_id' => $period->id,
            'name' => 'BSIT',
            'code' => 'BSIT',
        ]);
        DB::table('exam_sessions')->insert([
            'course_id' => $course->id,
            'room_id' => $room->id,
            'proctor_id' => $this->admin->id,
            'date' => '2026-03-15',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'scheduled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->deleteJson('/api/rooms/' . $room->id);

        $response->assertStatus(409)->assertJson(['error' => 'conflict']);
        $this->assertDatabaseHas('rooms', ['id' => $room->id]);
    }

    public function test_staff_cannot_access_rooms(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $response = $this->actingAs($staff)->getJson('/api/rooms');

        $response->assertStatus(403);
    }
}
