<?php

declare(strict_types=1);

namespace Tests\App\Http\Controllers\DevOps;

use Tests\TestCase;

/**
 * 部署版本接口测试
 *
 * 镜像控制器: App\Http\Controllers\DevOps\VersionController
 */
class VersionControllerTest extends TestCase
{
    public function test_returns_build_version_and_build_time(): void
    {
        $response = $this->get('/devops/version');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'build_version',
                'build_time',
            ]);
    }
}
