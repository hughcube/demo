<?php

declare(strict_types=1);

namespace Tests\App\Http\Controllers\App\Login;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * 支付宝小程序授权登录测试
 *
 * 镜像控制器: App\Http\Controllers\App\Login\AliPayMpController
 */
class AliPayMpControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_alipay_mp_login_validation(): void
    {
        // 缺少 code 时应返回验证错误
        $response = $this->postJson('/api/login/alipay-mp');

        $response->assertStatus(200)
            ->assertJsonPath('Code', 'ValidationFailed');
    }
}
