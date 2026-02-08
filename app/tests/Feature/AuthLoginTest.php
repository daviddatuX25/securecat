<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * POST /api/auth/login — per 08-api-spec-phase1 §2.
 */
class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_succeeds_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.edu',
            'first_name' => 'Jane',
            'last_name' => 'Admin',
            'role' => User::ROLE_ADMIN,
        ]);
        $user->password_hash = 'secret'; // hashed cast hashes it
        $user->save();

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.edu',
            'password' => 'secret',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'email',
                    'role',
                    'first_name',
                    'last_name',
                ],
                'session_token',
            ])
            ->assertJson([
                'user' => [
                    'email' => 'admin@example.edu',
                    'role' => 'admin',
                    'first_name' => 'Jane',
                    'last_name' => 'Admin',
                ],
            ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        $u = User::factory()->create(['email' => 'admin@example.edu']);
        $u->password_hash = 'secret';
        $u->save();

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.edu',
            'password' => 'wrong',
        ]);

        $response->assertStatus(401)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_with_unknown_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nobody@example.edu',
            'password' => 'secret',
        ]);

        $response->assertStatus(401)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_when_user_inactive(): void
    {
        $u = User::factory()->create([
            'email' => 'admin@example.edu',
            'is_active' => false,
        ]);
        $u->password_hash = 'secret';
        $u->save();

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.edu',
            'password' => 'secret',
        ]);

        $response->assertStatus(401)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_validates_required_fields(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_validates_email_format(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'not-an-email',
            'password' => 'secret',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
