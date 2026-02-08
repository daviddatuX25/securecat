<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin UI: rooms list/create/edit. Per 09-ui-routes-phase1, T2.3.3.
 */
class AdminRoomsUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_rooms_list(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/rooms');

        $response->assertStatus(200)->assertSee('Rooms');
    }

    public function test_admin_can_access_rooms_new(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/rooms/new');

        $response->assertStatus(200)->assertSee('New room');
    }

    public function test_admin_can_access_room_edit(): void
    {
        $admin = User::factory()->admin()->create();
        $room = Room::create(['name' => 'Room 101', 'capacity' => 40]);

        $response = $this->actingAs($admin)->get('/admin/rooms/' . $room->id . '/edit');

        $response->assertStatus(200)->assertSee('Edit room');
    }

    public function test_staff_cannot_access_rooms_list(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $response = $this->actingAs($staff)->get('/admin/rooms');

        $response->assertRedirect('/');
    }

    public function test_staff_cannot_access_rooms_new(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $response = $this->actingAs($staff)->get('/admin/rooms/new');

        $response->assertRedirect('/');
    }
}
