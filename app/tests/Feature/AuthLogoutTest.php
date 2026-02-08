<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * POST /api/auth/logout — per 08-api-spec-phase1 §2.
 */
class AuthLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_succeeds_when_authenticated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/auth/logout');

        $response->assertStatus(204)
            ->assertNoContent();

        $this->assertGuest();
    }

    public function test_logout_returns_401_when_unauthenticated(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }
}
