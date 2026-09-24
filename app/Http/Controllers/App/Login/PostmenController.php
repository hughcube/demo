<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Login;

use App\Enum\UserTypeEnum;
use App\Models\User;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Throwable;

class PostmenController extends Controller
{
    /**
     * @return array<string, array<mixed>>
     */
    protected function rules(): array
    {
        return [
            'openid' => ['required', 'string', Rule::in(['postmen'])],
        ];
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    protected function getOrCreateUser(): User
    {
        /** @var null|User $user */
        $user = User::findByPostmen($openid = strval($this->p('openid')));

        $user = $user ?? new User();
        $user->type = UserTypeEnum::POSTMEN;
        $user->appid = 'postmen';
        $user->openid = $openid;
        $user->sub_openid = '';

        $user->last_login_at = Carbon::now();
        if (true !== $user->resetModelVersion()->save()) {
            throw new Exception('用户保存失败, 请您稍后再试!');
        }

        return $user;
    }
}
