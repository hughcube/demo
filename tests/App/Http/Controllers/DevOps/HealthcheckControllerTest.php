<?php

declare(strict_types=1);

namespace Tests\App\Http\Controllers\DevOps;

use Tests\TestCase;

/**
 * 存活探针接口测试
 *
 * 镜像控制器: App\Http\Controllers\DevOps\HealthcheckController
 */
class HealthcheckControllerTest extends TestCase
{
    public function test_returns_200_and_success_string(): void
    {
        $response = $this->get('/devops/healthcheck');

        $response->assertStatus(200);
        $this->assertSame('success', $response->getContent());
    }
}
