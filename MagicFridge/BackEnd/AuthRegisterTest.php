<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthRegisterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function register_creates_user_and_redirects_to_login_with_status(): void
    {
        // ✅ ne próbáljon valódi emailt küldeni
        config(['mail.default' => 'array']);

        $res = $this->post('/register', [
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'pass1234',
            'password_confirmation' => 'pass1234',
        ]);

        $res->assertStatus(302);
        $res->assertRedirect('/login');
        $res->assertSessionHas('status');

        $user = DB::table('users')->where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Test User', $user->full_name);
        $this->assertNotEmpty($user->password);
        $this->assertNotEmpty($user->email_verify_token);
        $this->assertNull($user->email_verified_at);
    }

    /** @test */
    public function register_fails_if_email_already_exists(): void
    {
        config(['mail.default' => 'array']);

        DB::table('users')->insert([
            'full_name' => 'Existing',
            'email' => 'dup@example.com',
            'password' => bcrypt('x'),
            'email_verify_token' => null,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $res = $this->from('/register')->post('/register', [
            'full_name' => 'Another',
            'email' => 'dup@example.com',
            'password' => 'pass1234',
            'password_confirmation' => 'pass1234',
        ]);

        $res->assertStatus(302);
        $res->assertRedirect('/register');
        $res->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function register_fails_if_password_confirmation_mismatch(): void
    {
        config(['mail.default' => 'array']);

        $res = $this->from('/register')->post('/register', [
            'full_name' => 'Test User',
            'email' => 'x@example.com',
            'password' => 'pass1234',
            'password_confirmation' => 'DIFFERENT',
        ]);

        $res->assertStatus(302);
        $res->assertRedirect('/register');
        $res->assertSessionHasErrors(['password']);
    }
}