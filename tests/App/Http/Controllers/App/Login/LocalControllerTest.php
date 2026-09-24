<?php

declare(strict_types=1);

namespace Tests\App\Http\Controllers\App\Login;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * 本地用户登录测试
 *
 * 镜像控制器: App\Http\Controllers\App\Login\LocalController
 */
class LocalControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_local_login_with_existing_user(): void
    {
        $user = User::query()->first();
        if (!$user instanceof User) {
            $user = new User();
            $user->type = 1;
            $user->appid = 'test';
            $user->openid = 'test_openid';
            $user->save();
        }

        $response = $this->postJson('/api/login/local', [
            'user' => $user->id,
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
