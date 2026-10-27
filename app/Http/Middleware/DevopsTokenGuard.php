<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DevopsTokenGuard
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = getenv('KNIGHT_DEVOPS_TOKEN')
            ?: env('KNIGHT_DEVOPS_TOKEN')
            ?: env('KNIGHT_DEVOPS_SCHEDULE_TOKEN')
            ?: config('knight.devops.token');

        if (empty($expectedToken)) {
            throw new AccessDeniedHttpException('Forbidden: DevOps token is not configured.');
        }

        $givenToken = $request->header('X-Devops-Token')
            ?: $request->query('X-Devops-Token')
            ?: $request->query('token')
            ?: $request->bearerToken();

        if (empty($givenToken) || !hash_equals(strval($expectedToken), strval($givenToken))) {
            throw new AccessDeniedHttpException('Forbidden: Invalid DevOps token.');
        }

        return $next($request);
    }
}
