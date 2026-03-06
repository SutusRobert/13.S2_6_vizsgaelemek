<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthPagesTest extends TestCase
{
    /** @test */
    public function home_redirects_guest_to_login(): void
    {
        $res = $this->get('/');

        // home route: guest -> login.form
        $res->assertRedirect('/login');
    }

    /** @test */
    public function login_form_is_reachable(): void
    {
        $res = $this->get('/login');

        // tipikusan 200, de ha valamiért redirectelsz, az se 404 legyen
        $this->assertTrue(in_array($res->getStatusCode(), [200, 302]), 'Login route should be reachable (200/302).');
    }

    /** @test */
    public function register_form_is_reachable(): void
    {
        $res = $this->get('/register');

        $this->assertTrue(in_array($res->getStatusCode(), [200, 302]), 'Register route should be reachable (200/302).');
    }

    /** @test */
    public function verify_email_route_exists(): void
    {
        $res = $this->get('/verify-email');

        $this->assertTrue(in_array($res->getStatusCode(), [200, 302, 400, 422]), 'Verify-email should exist (not 404).');
        $this->assertNotEquals(404, $res->getStatusCode(), 'Verify-email route must not be 404.');
    }
}