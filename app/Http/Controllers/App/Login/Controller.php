<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Login;

use App\Http\Controllers\App\Controller as BaseController;
use App\Models\User;
use HughCube\Laravel\Knight\Database\DB as KnightDB;
use HughCube\Laravel\Knight\Exceptions\UserException;
use HughCube\Laravel\Knight\Support\Str;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Tymon\JWTAuth\JWT;
use Tymon\JWTAuth\JWTGuard;

abstract class Controller extends BaseController
{
    /**
     * @throws Throwable
     */
    protected function action(): Response
    {
        /** @var null|User $user */
        $user = KnightDB::retryOnQueryException(function () {
            $user = $this->getOrCreateUser();
            if (null === $user) {
                return null;
            }

            $user->withAccessSecret(Str::random(32));
            $user->resetModelVersion()->save();

            return $user;
        });

        if (null === $user) {
            throw new UserException('用户不存在!');
        }

        /** @var JWTGuard|JWT $auth */
        $auth = $this->getContainer()->make(AuthFactory::class);

        return $this->asSuccess([
            'access_secret' => $user->getUserLoginAccessSecret(),
            'access_token' => sprintf('%s', $auth->login($user)),
        ]);
    }

    abstract protected function getOrCreateUser(): null|User;
}
