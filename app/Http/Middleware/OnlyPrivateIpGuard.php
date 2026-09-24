<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use HughCube\Laravel\Knight\Support\Str;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class OnlyPrivateIpGuard
{
    /**
     * 内网私有网段与回环网段列表
     *
     * @var string[]
     */
    protected array $privateRanges = [
        '127.0.0.1/8',
        '::1',
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
    ];

    public function handle(Request $request, Closure $next): mixed
    {
        $clientIp = $request->getClientIp();

        // 判定 clientIp 是否属于本地回环或 RFC 1918 私网网段
        $isPrivate = !empty($clientIp) && (
            IpUtils::checkIp($clientIp, $this->privateRanges) || Str::isPrivateIp($clientIp)
        );

        if (!$isPrivate) {
            throw new AccessDeniedHttpException('Only allow specified intranet ip access!');
        }

        return $next($request);
    }
}
