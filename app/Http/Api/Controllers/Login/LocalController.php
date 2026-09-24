<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/15
 * Time: 8:42 下午
 */

namespace App\Http\Api\Controllers\Login;

use App\Models\User;
use HughCube\Laravel\Knight\Exceptions\Exception;
use HughCube\Laravel\Knight\Exceptions\UserException;
use Throwable;

class LocalController extends AAAController
{
    protected function rules(): array
    {
        return [
            'user' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    protected function getOrCreateUser(): User
    {
        /** @var User|null $user */
        $user = User::findById($this->p('user'));

        if (true !== $user?->isAvailable()) {
            throw new UserException('用户不存在!');
        }

        return $user;
    }
}

