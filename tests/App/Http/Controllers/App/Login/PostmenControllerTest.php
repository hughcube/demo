<?php

declare(strict_types=1);

namespace Tests\App\Http\Controllers\App\Login;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Postman 测试登录测试
 *
 * 镜像控制器: App\Http\Controllers\App\Login\PostmenController
 */
class PostmenControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_postmen_login_returns_token(): void
    {
        $response = $this->postJson('/api/login/postmen', [
            'openid' => 'postmen',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('Code', 'Success')
            ->assertJsonStructure([
                'Code',
                'Data' => [
                    'access_secret',
                    'access_token',
                ],
            ]);
    }
}
