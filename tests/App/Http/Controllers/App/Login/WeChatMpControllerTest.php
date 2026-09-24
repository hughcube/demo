<?php

declare(strict_types=1);

namespace Tests\App\Http\Controllers\App\Login;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * 微信小程序授权登录测试
 *
 * 镜像控制器: App\Http\Controllers\App\Login\WeChatMpController
 */
class WeChatMpControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_wechat_mp_login_validation(): void
    {
        // 缺少 code 时应返回验证错误
        $response = $this->postJson('/api/login/wechat-mp');

        $response->assertStatus(200)
            ->assertJsonPath('Code', 'ValidationFailed');
    }
}
