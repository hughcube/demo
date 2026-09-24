<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/devops/healthcheck');

        $response->assertStatus(200);
    }

    public function test_guest_login_returns_token(): void
    {
        $response = $this->postJson('/api/login/guest');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'Code',
                'Data' => [
                    'access_secret',
                    'access_token',
                ],
            ]);
    }
}
