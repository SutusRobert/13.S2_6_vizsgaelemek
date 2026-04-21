<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_application_redirects_from_home(): void
    {
        $response = $this->get('/');

        // nálad a home redirectel loginra vagy dashboardra
        $response->assertStatus(302);
    }
}