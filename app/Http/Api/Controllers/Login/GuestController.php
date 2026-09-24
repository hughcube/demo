<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/15
 * Time: 8:42 下午
 */

namespace App\Http\Api\Controllers\Login;

use App\Enum\UserTypeEnum;
use App\Models\User;
use App\Services\Random\Random;
use Exception;
use Illuminate\Support\Carbon;
use Throwable;

class GuestController extends AAAController
{
    protected function rules(): array
    {
        return [
        ];
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    protected function getOrCreateUser(): User
    {
        if ($this->isContainerDebug() && $this->isContainerLocalEnv()) {
            $user = User::findById(1);
            if ($user instanceof User) {
                return $user;
            }
        }

        /** @var null|User $user */
        $user = User::findByPostmen($openid = Random::genToken());

        $user = $user ?? new User;
        $user->type = UserTypeEnum::ALIPAY_GUEST;
        $user->appid = 'guest';
        $user->openid = $openid;
        $user->sub_openid = '';

        $user->last_login_at = Carbon::now();
        if (true !== $user->resetModelVersion()->save()) {
            throw new Exception('用户保存失败, 请您稍后再试!');
        }

        return $user;
    }
}

