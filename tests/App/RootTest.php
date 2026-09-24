<?php

declare(strict_types=1);

namespace Tests\App;

use Tests\TestCase;

/**
 * 根路由与健康探针测试
 */
class RootTest extends TestCase
{
    /**
     * 根路径返回应用信息与状态
     */
    public function test_root_returns_status(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertJson([
                'name' => config('app.name'),
                'status' => 'up',
            ]);
    }
}
