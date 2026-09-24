<?php

declare(strict_types=1);

namespace Tests\App\Http\Controllers\DevOps;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * 队列深度监控接口测试
 *
 * 镜像控制器: App\Http\Controllers\DevOps\QueueDepthController
 */
class QueueDepthControllerTest extends TestCase
{
    /**
     * 测试：获取默认队列深度
     */
    public function test_returns_queue_sizes(): void
    {
        $response = $this->get('/devops/queue-depth');

        $response->assertStatus(200)
            ->assertJsonPath('Code', 'Success')
            ->assertJsonStructure([
                'Code',
                'Data' => [
                    'value',
                    'queues',
                ],
            ]);
    }

    /**
     * 测试：指定队列名
     */
    public function test_with_custom_queues(): void
    {
        $response = $this->get('/devops/queue-depth?queues=default');

        $response->assertStatus(200)
            ->assertJsonPath('Code', 'Success')
            ->assertJsonStructure([
                'Code',
                'Data' => [
                    'value',
                    'queues' => [
                        'default',
                    ],
                ],
            ]);
    }

    /**
     * 测试：公网 IP 访问被阻断
     */
    #[DataProvider('provideExternalIps')]
    public function test_blocked_for_external_ips(string $ip): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->get('/devops/queue-depth');

        $response->assertStatus(200)
            ->assertJson([
                'Code' => 'HttpError',
            ]);
    }

    /**
     * @return array<string, array<string>>
     */
    public static function provideExternalIps(): array
    {
        return [
            'Public IPv4' => ['8.8.8.8'],
        ];
    }
}
