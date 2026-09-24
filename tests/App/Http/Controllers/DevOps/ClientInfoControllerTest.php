<?php

declare(strict_types=1);

namespace Tests\App\Http\Controllers\DevOps;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * 客户端环境与指纹识别测试
 *
 * 镜像控制器: App\Http\Controllers\DevOps\ClientInfoController
 */
class ClientInfoControllerTest extends TestCase
{
    /**
     * 测试：默认访问返回 HTML 可视化报告
     */
    public function test_returns_html_view(): void
    {
        $response = $this->get('/devops/client-info');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/html', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('客户端信息识别', (string) $response->getContent());
    }

    /**
     * 测试：带 ?format=json 返回结构化指纹数据
     */
    public function test_with_format_json_returns_fingerprints(): void
    {
        $response = $this->get('/devops/client-info?format=json');

        $response->assertStatus(200)
            ->assertJsonPath('Code', 'Success')
            ->assertJsonStructure([
                'Code',
                'Data' => [
                    '网络与请求',
                    '客户端类型识别',
                    '操作系统与设备',
                    '浏览器与内核',
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
            ->get('/devops/client-info');

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
