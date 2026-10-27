<?php

declare(strict_types=1);

namespace Tests\App\Http\Controllers\DevOps;

use Tests\TestCase;

class PhpInfoActionTest extends TestCase
{
    protected string $token = '8f3d9b1a7c2e4f0d6b8a2c4e6f1a3b5c7d9e0f2a4b6c8d1e3f5a7b9c1d3e5f7a';

    protected function setUp(): void
    {
        parent::setUp();
        putenv("KNIGHT_DEVOPS_TOKEN={$this->token}");
    }

    public function test_phpinfo_without_token_is_forbidden(): void
    {
        $response = $this->get('/devops/phpinfo');
        $this->assertSame('HttpError', $response->json('Code'));
        $this->assertSame('Forbidden', $response->json('Message'));
    }

    public function test_phpinfo_with_invalid_token_is_forbidden(): void
    {
        $response = $this->get('/devops/phpinfo?X-Devops-Token=invalid_token');
        $this->assertSame('HttpError', $response->json('Code'));
        $this->assertSame('Forbidden', $response->json('Message'));
    }

    public function test_phpinfo_with_query_token_returns_html(): void
    {
        $response = $this->get("/devops/phpinfo?X-Devops-Token={$this->token}");
        $response->assertStatus(200);
        $this->assertStringContainsString('phpinfo()', strval($response->getContent()));
    }

    public function test_phpinfo_with_header_token_returns_html(): void
    {
        $response = $this->withHeader('X-Devops-Token', $this->token)->get('/devops/phpinfo');
        $response->assertStatus(200);
        $this->assertStringContainsString('phpinfo()', strval($response->getContent()));
    }

    public function test_healthcheck_returns_success(): void
    {
        $response = $this->get('/devops/healthcheck');
        $response->assertStatus(200);
        $this->assertSame('success', trim(strval($response->getContent())));
    }
}
