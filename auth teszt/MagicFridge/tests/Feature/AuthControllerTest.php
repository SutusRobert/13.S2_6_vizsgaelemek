<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_redirects_to_login_when_not_logged_in(): void
    {
        $this->get(route('home'))
            ->assertRedirect(route('login.form'));
    }

    public function test_register_creates_user_and_redirects_to_login(): void
    {
        $response = $this->post(route('register.do'), [
            'full_name' => 'Teszt Elek',
            'email' => 'teszt@example.com',
            'password' => 'abcd',
            'password_confirmation' => 'abcd',
        ]);

        $response->assertRedirect(route('login.form'));
        $response->assertSessionHas('status');

        $user = DB::table('users')->where('email', 'teszt@example.com')->first();
        $this->assertNotNull($user);

        $this->assertEquals('Teszt Elek', $user->full_name);
        $this->assertTrue(Hash::check('abcd', $user->password));
    }

    public function test_register_fails_when_email_is_taken(): void
    {
        DB::table('users')->insert([
            'full_name' => 'Régi User',
            'email' => 'foglalt@example.com',
            'password' => Hash::make('abcd'),
        ]);

        $response = $this->from(route('register.form'))->post(route('register.do'), [
            'full_name' => 'Új User',
            'email' => 'foglalt@example.com',
            'password' => 'abcd',
            'password_confirmation' => 'abcd',
        ]);

        $response->assertRedirect(route('register.form'));
        $response->assertSessionHasErrors(['email']);

        $this->assertEquals(1, DB::table('users')->where('email', 'foglalt@example.com')->count());
    }

    public function test_register_fails_when_password_confirmation_does_not_match(): void
    {
        $response = $this->from(route('register.form'))->post(route('register.do'), [
            'full_name' => 'Teszt Elek',
            'email' => 'teszt2@example.com',
            'password' => 'abcd',
            'password_confirmation' => 'xxxx',
        ]);

        $response->assertRedirect(route('register.form'));
        $response->assertSessionHasErrors(['password']);
    }

    public function test_login_sets_session_and_redirects_to_dashboard(): void
    {
        DB::table('users')->insert([
            'full_name' => 'Teszt Elek',
            'email' => 'teszt@example.com',
            'password' => Hash::make('abcd'),
        ]);

        $response = $this->post(route('login.do'), [
            'email' => 'teszt@example.com',
            'password' => 'abcd',
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertNotNull(session('user_id'));
        $this->assertEquals('Teszt Elek', session('full_name'));
        $this->assertEquals('teszt@example.com', session('email'));
    }

    public function test_login_fails_with_wrong_password(): void
    {
        DB::table('users')->insert([
            'full_name' => 'Teszt Elek',
            'email' => 'teszt@example.com',
            'password' => Hash::make('abcd'),
        ]);

        $response = $this->from(route('login.form'))->post(route('login.do'), [
            'email' => 'teszt@example.com',
            'password' => 'rossz',
        ]);

        $response->assertRedirect(route('login.form'));
        $response->assertSessionHasErrors(['email']);
        $response->assertSessionHasInput('email', 'teszt@example.com');
    }

    public function test_logout_flushes_session(): void
    {
        $this->withSession([
            'user_id' => 1,
            'full_name' => 'Valaki',
            'email' => 'v@v.hu',
        ]);

        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login.form'));

        $this->assertNull(session('user_id'));
        $this->assertNull(session('full_name'));
        $this->assertNull(session('email'));
    }
}
