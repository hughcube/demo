<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use HughCube\Laravel\Knight\Http\Middleware\Authenticate as BaseAuthenticate;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class Authenticate extends BaseAuthenticate
{
    public function handle($request, Closure $next, ...$guards)
    {
        return parent::handle($request, $next, 'api');
    }

    protected function isOptional(Request $request): bool
    {
        if (null !== $request->headers->get('Authorization')) {
            return false;
        }

        return parent::isOptional($request);
    }

    protected function unauthenticated($request, array $guards)
    {
        throw new AuthenticationException('Unauthenticated.', $guards);
    }

    /**
     * @return string[]
     */
    protected function getOptional(): array
    {
        return [
            '/api/login/*',
            '/api/logon/*',
        ];
    }
}
