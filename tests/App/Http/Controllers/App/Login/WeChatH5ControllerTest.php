<?php

declare(strict_types=1);

namespace Tests\App\Http\Controllers\App\Login;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * 微信 H5 授权登录测试
 *
 * 镜像控制器: App\Http\Controllers\App\Login\WeChatH5Controller
 */
class WeChatH5ControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_wechat_h5_login_validation(): void
    {
        // 缺少 code 时应返回验证错误
        $response = $this->postJson('/api/login/wechat-h5');

        $response->assertStatus(200)
            ->assertJsonPath('Code', 'ValidationFailed');
    }
}
