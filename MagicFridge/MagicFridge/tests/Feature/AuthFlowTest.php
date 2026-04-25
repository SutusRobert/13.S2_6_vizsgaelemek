<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    public function test_home_redirects_guest_to_login(): void
    {
        $this->get('/')
            ->assertRedirect('/login');
    }

    public function test_home_redirects_logged_user_to_dashboard(): void
    {
        $userId = $this->createUser();

        $this->withSession($this->loggedSession($userId))
            ->get('/')
            ->assertRedirect('/dashboard');
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Login');
    }

    public function test_register_page_renders(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('Registration');
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $this->createUser([
            'email' => 'anna@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $this->from('/login')
            ->post('/login', [
                'email' => 'anna@example.com',
                'password' => 'wrong-password',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');
    }

    public function test_login_rejects_unverified_user(): void
    {
        $this->createUser([
            'email' => 'anna@example.com',
            'password' => bcrypt('secret123'),
            'email_verified_at' => null,
        ]);

        $this->from('/login')
            ->post('/login', [
                'email' => 'anna@example.com',
                'password' => 'secret123',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');
    }

    public function test_login_allows_verified_user(): void
    {
        $this->createUser([
            'email' => 'anna@example.com',
            'full_name' => 'Anna Apple',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'anna@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertSame(1, session('user_id'));
        $this->assertSame('Anna Apple', session('user_name'));
    }

    public function test_logout_get_redirects_to_login_and_clears_session(): void
    {
        $userId = $this->createUser();

        $this->withSession($this->loggedSession($userId))
            ->get('/logout')
            ->assertRedirect('/login');

        $this->assertNull(session('user_id'));
    }

    public function test_verify_email_with_invalid_token_shows_invalid_status(): void
    {
        $this->get('/verify-email/not-a-real-token')
            ->assertOk()
            ->assertSee('invalid', false);
    }

    public function test_dashboard_renders_for_logged_session(): void
    {
        $userId = $this->createUser(['full_name' => 'Anna Apple']);

        DB::table('messages')->insert([
            'user_id' => $userId,
            'title' => 'Welcome',
            'body' => 'Hello there',
            'is_read' => 0,
            'created_at' => now(),
        ]);

        $this->withSession($this->loggedSession($userId, 'Anna Apple'))
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Apple');
    }
}
