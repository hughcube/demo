<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/15
 * Time: 8:42 下午
 */

namespace App\Http\Api\Controllers\Login;

use App\Models\User;
use HughCube\Laravel\Knight\Database\DB as KnightDB;
use HughCube\Laravel\Knight\Exceptions\UserException;
use HughCube\Laravel\Knight\Support\Str;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Tymon\JWTAuth\JWT;
use Tymon\JWTAuth\JWTGuard;

abstract class AAAController extends \App\Http\Api\Controllers\AAAController
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

