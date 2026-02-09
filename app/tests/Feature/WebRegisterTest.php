<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_page_returns_200(): void
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
        $response->assertSee('Register', false);
    }

    public function test_register_creates_user_and_redirects_to_staff_home(): void
    {
        $response = $this->post(route('register'), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'password' => 'SecurePass1!',
            'password_confirmation' => 'SecurePass1!',
            '_token' => csrf_token(),
        ]);

        $response->assertRedirect('/staff/home');
        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'role' => User::ROLE_STAFF,
        ]);
    }

    public function test_register_validates_password_confirmation(): void
    {
        $response = $this->post(route('register'), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane2@example.com',
            'password' => 'SecurePass1!',
            'password_confirmation' => 'Different1!',
            '_token' => csrf_token(),
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'jane2@example.com']);
    }
}
