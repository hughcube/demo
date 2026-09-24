<?php

declare(strict_types=1);

namespace App\Http\Controllers\DevOps;

use App\Http\Controllers\Controller;
use HughCube\Laravel\Knight\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ClientInfoController extends Controller
{
    /**
     * @return array<string, array<string>>
     */
    protected function rules(): array
    {
        return [
            'format' => ['nullable', 'string'],
        ];
    }

    protected function action(): Response
    {
        $request = $this->getRequest();
        $agent = $request->getUserAgentDetect();

        $platform = $agent->platform();
        $osVersion = $platform ? $agent->version($platform) : null;

        $browser = $agent->browser();
        $browserVersion = $browser ? $agent->version($browser) : null;

        $items = [
            '网络与请求' => [
                '客户端 IP' => $request->ip(),
                'User-Agent' => $request->userAgent(),
                '是否安全传输 (HTTPS)' => $request->isSecure() ? '是' : '否',
                '是否内网访问' => Str::isPrivateIp($request->getClientIp()) ? '是' : '否',
            ],
            '客户端类型识别' => [
                '是否移动端' => $agent->isMobile() ? '是' : '否',
                '是否平板' => $agent->isTablet() ? '是' : '否',
                '是否爬虫机器人' => $agent->isRobot() ? '是' : '否',
                '是否微信' => $request->isWeChat() ? '是' : '否',
                '是否微信小程序' => $request->isWeChatMiniProgram() ? '是' : '否',
                '是否企业微信' => $request->isWeCom() ? '是' : '否',
                '是否钉钉' => $request->isDingTalk() ? '是' : '否',
                '是否飞书' => $request->isFeishu() ? '是' : '否',
                '是否支付宝' => $request->isAlipay() ? '是' : '否',
                '是否 API 调试工具' => $request->isApiDebugTool() ? '是' : '否',
            ],
            '操作系统与设备' => [
                '操作系统' => $platform ?: null,
                '系统版本' => $osVersion !== false && $osVersion !== null ? strval($osVersion) : null,
                '设备型号' => $agent->device() ?: null,
                '机器人标识' => $agent->robot() ?: null,
            ],
            '浏览器与内核' => [
                '浏览器' => $browser ?: null,
                '浏览器版本' => $browserVersion !== false && $browserVersion !== null ? strval($browserVersion) : null,
            ],
        ];

        if ('json' === $this->p('format')) {
            return $this->asSuccess($items);
        }

        return new Response($this->renderHtml($items), 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * @param array<string, array<string, string|null>> $items
     */
    protected function renderHtml(array $items): string
    {
        $body = '';
        foreach ($items as $group => $fields) {
            $body .= '<h3>' . htmlspecialchars($group, ENT_QUOTES, 'UTF-8') . '</h3><table>';
            foreach ($fields as $label => $value) {
                $displayValue = null !== $value && '' !== $value
                    ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
                    : '<span style="color:#94a3b8">-</span>';
                $body .= '<tr><td class="label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</td><td>' . $displayValue . '</td></tr>';
            }
            $body .= '</table>';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>客户端信息检测</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #e2e8f0; padding: 24px 16px; min-height: 100vh; }
        .container { max-width: 820px; margin: 0 auto; background: #1e293b; border-radius: 12px; padding: 28px; box-shadow: 0 4px 24px rgba(0,0,0,.3); }
        h2 { color: #f8fafc; font-size: 20px; border-bottom: 2px solid #38bdf8; padding-bottom: 12px; margin-bottom: 20px; }
        h3 { color: #38bdf8; margin: 20px 0 10px; font-size: 15px; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; background: #0f172a; border-radius: 8px; overflow: hidden; margin-bottom: 16px; border: 1px solid #334155; }
        td { padding: 10px 16px; font-size: 13px; border-bottom: 1px solid #1e293b; word-break: break-all; }
        td.label { width: 180px; color: #94a3b8; font-weight: 500; background: #182234; border-right: 1px solid #334155; }
        tr:last-child td { border-bottom: none; }
    </style>
</head>
<body>
    <div class="container">
        <h2>客户端信息识别</h2>
        {$body}
    </div>
</body>
</html>
HTML;
    }
}
