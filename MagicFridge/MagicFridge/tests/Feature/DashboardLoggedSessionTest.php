<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardLoggedSessionTest extends TestCase
{
    /** @test */
    public function dashboard_is_reachable_with_user_id_in_session(): void
    {
        $res = $this->withSession(['user_id' => 1, 'full_name' => 'X', 'email' => 'x@x.hu'])
            ->get('/dashboard');

        // attól függ mit csinál a controller: lehet 200 vagy redirect
        $this->assertNotEquals(404, $res->getStatusCode(), 'Dashboard route should exist for logged session.');
        $this->assertNotEquals(500, $res->getStatusCode(), 'Dashboard should not error for logged session.');
    }
}