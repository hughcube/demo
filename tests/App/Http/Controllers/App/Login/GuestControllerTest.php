<?php

declare(strict_types=1);

namespace Tests\App\Http\Controllers\App\Login;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * 游客登录接口测试
 *
 * 镜像控制器: App\Http\Controllers\App\Login\GuestController
 */
class GuestControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_login_returns_token(): void
    {
        $response = $this->postJson('/api/login/guest');

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
