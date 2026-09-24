<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/7/26
 * Time: 19:48
 */

namespace App\Http\Api\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class Authenticate extends \HughCube\Laravel\Knight\Http\Middleware\Authenticate
{
    public function handle($request, Closure $next, ...$guards)
    {
        return parent::handle($request, $next, 'api');
    }

    protected function isOptional(Request $request): bool
    {
        if (null != $request->headers->get('Authorization')) {
            return false;
        }

        return parent::isOptional($request);
    }

    protected function unauthenticated($request, array $guards)
    {
        throw new AuthenticationException('Unauthenticated.', $guards);
    }

    protected function getOptional(): array
    {
        return [
            '/api/login/*',
            '/api/logon/*',
        ];
    }
}
