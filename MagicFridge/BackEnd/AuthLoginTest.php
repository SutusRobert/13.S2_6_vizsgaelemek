<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function login_sets_session_and_redirects_to_dashboard_on_success(): void
    {
        DB::table('users')->insert([
            'full_name' => 'Login User',
            'email' => 'login@example.com',
            'password' => bcrypt('pass1234'),
            'email_verify_token' => null,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $res = $this->post('/login', [
            'email' => 'login@example.com',
            'password' => 'pass1234',
        ]);

        $res->assertStatus(302);
        $res->assertRedirect('/dashboard');

        $res->assertSessionHas('user_id');
        $res->assertSessionHas('full_name', 'Login User');
        $res->assertSessionHas('email', 'login@example.com');
    }

    /** @test */
    public function login_fails_with_wrong_password(): void
    {
        DB::table('users')->insert([
            'full_name' => 'Login User',
            'email' => 'login@example.com',
            'password' => bcrypt('pass1234'),
            'email_verify_token' => null,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $res = $this->from('/login')->post('/login', [
            'email' => 'login@example.com',
            'password' => 'WRONG',
        ]);

        $res->assertStatus(302);
        $res->assertRedirect('/login');
        $res->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function login_validates_required_fields(): void
    {
        $res = $this->from('/login')->post('/login', [
            'email' => '',
            'password' => '',
        ]);

        $res->assertStatus(302);
        $res->assertRedirect('/login');
        $res->assertSessionHasErrors(['email', 'password']);
    }
}