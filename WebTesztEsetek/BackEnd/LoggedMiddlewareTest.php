<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoggedMiddlewareTest extends TestCase
{
    private array $protectedGet = [
        '/dashboard',
        '/messages',
        '/inventory',
        '/inventory/list',
        '/households',
    ];

    private array $protectedPost = [
        '/messages/read',
        '/messages/delete',
        '/messages/invite/respond',
        '/messages/respond',

        '/inventory',
        '/inventory/list',

        '/households/invite',
        '/households/toggle-role',
    ];

    /** @test */
    public function guest_is_redirected_to_login_on_protected_get_routes(): void
    {
        foreach ($this->protectedGet as $path) {
            $res = $this->get($path);

            $this->assertTrue(
                in_array($res->getStatusCode(), [302, 303]),
                "Expected redirect for guest GET {$path}, got {$res->getStatusCode()}"
            );

            $res->assertRedirect('/login');
        }
    }

    /** @test */
    public function guest_is_redirected_to_login_on_protected_post_routes(): void
    {
        foreach ($this->protectedPost as $path) {
            $res = $this->post($path, []);

            $this->assertTrue(
                in_array($res->getStatusCode(), [302, 303]),
                "Expected redirect for guest POST {$path}, got {$res->getStatusCode()}"
            );

            $res->assertRedirect('/login');
        }
    }
}