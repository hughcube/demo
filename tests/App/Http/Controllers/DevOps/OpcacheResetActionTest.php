<?php

declare(strict_types=1);

namespace Tests\App\Http\Controllers\DevOps;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * OPcache 重置测试
 *
 * 镜像 Action: HughCube\Laravel\Knight\OPcache\Actions\ResetAction
 */
class OpcacheResetActionTest extends TestCase
{
    /**
     * 测试：非 Localhost IP 访问被 OnlyLocalGuard 阻断
     */
    #[DataProvider('provideNonLocalhostIps')]
    public function test_blocked_for_non_local_ips(string $ip): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->post('/devops/opcache/reset');

        $response->assertStatus(200)
            ->assertJson([
                'Code' => 'HttpError',
            ]);
    }

    /**
     * 测试：本地 127.0.0.1 访问被放行
     */
    public function test_allowed_for_localhost(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post('/devops/opcache/reset');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'Code',
                'Message',
                'Data',
            ]);
    }

    /**
     * @return array<string, array<string>>
     */
    public static function provideNonLocalhostIps(): array
    {
        return [
            'Public IPv4' => ['8.8.8.8'],
            'LAN IPv4' => ['192.168.1.50'],
        ];
    }
}
