<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Web login/logout — skeleton frontend.
 */
class WebLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_login_succeeds_and_redirects_admin(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'first_name' => 'Admin',
            'last_name' => 'User',
        ]);
        $user->password_hash = 'password';
        $user->save();

        $response = $this->post(route('login'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_web_login_succeeds_and_redirects_staff(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@example.com',
            'role' => User::ROLE_STAFF,
        ]);
        $user->password_hash = 'password';
        $user->save();

        $response = $this->post(route('login'), [
            'email' => 'staff@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/staff/home');
        $this->assertAuthenticatedAs($user);
    }

    public function test_web_login_succeeds_and_redirects_proctor(): void
    {
        $user = User::factory()->proctor()->create([
            'email' => 'proctor@example.com',
        ]);
        $user->password_hash = 'password';
        $user->save();

        $response = $this->post(route('login'), [
            'email' => 'proctor@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/proctor/sessions');
        $this->assertAuthenticatedAs($user);
    }

    public function test_web_logout_redirects_to_login(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_staff_cannot_access_admin_dashboard(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $response = $this->actingAs($staff)->get('/admin/dashboard');

        $response->assertRedirect('/');
    }
}
