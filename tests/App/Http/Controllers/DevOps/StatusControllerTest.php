<?php

declare(strict_types=1);

namespace Tests\App\Http\Controllers\DevOps;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * 依赖健康状态看板与检查测试
 *
 * 镜像控制器: App\Http\Controllers\DevOps\StatusController
 */
class StatusControllerTest extends TestCase
{
    /**
     * 测试：直接访问返回 HTML 状态看板页面
     */
    public function test_returns_html_dashboard(): void
    {
        $response = $this->get('/devops/status');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/html', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('系统状态', (string) $response->getContent());
    }

    /**
     * 测试：携带 ?format=json 返回 JSON 格式探针数据
     */
    public function test_with_format_json_returns_checks(): void
    {
        $response = $this->get('/devops/status?format=json');

        $response->assertStatus(200)
            ->assertJsonPath('Code', 'Success')
            ->assertJsonStructure([
                'Code',
                'Data' => [
                    'healthy',
                    'checks' => [
                        'Database' => ['status'],
                        'Cache' => ['status'],
                    ],
                    'timestamp',
                ],
            ]);
    }

    /**
     * 测试：非内网 IP（外部公网 IP）被 OnlyPrivateIpGuard 拦截
     */
    #[DataProvider('provideExternalIps')]
    public function test_blocked_for_external_ips(string $ip): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->get('/devops/status');

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
            'Public IPv4 1' => ['8.8.8.8'],
            'Public IPv4 2' => ['114.114.114.114'],
        ];
    }
}
