<?php
/**
 * Created by Ai.
 * Model: Claude 3.5 Sonnet
 * User: hugh.li
 * Date: 2025/7/30
 * Time: 18:30
 */

declare(strict_types=1);

namespace App\Http\Api\Controllers\Login;

use App\Enum\UserTypeEnum;
use App\Models\User;
use HughCube\Laravel\Knight\Exceptions\UserException;
use HughCube\Laravel\WeChat\WeChat;
use Illuminate\Support\Carbon;
use RuntimeException;
use Throwable;

class WeChatH5Controller extends AAAController
{
    protected function rules(): array
    {
        return [
            'code' => ['required', 'string', 'min:1'],
        ];
    }

    /**
     * 获取或创建微信H5用户
     * @throws Throwable
     */
    protected function getOrCreateUser(): User
    {
        $userInfo = $this->getUserInfo($this->getRequest()->getClientAppid(), $this->p('code'));

        /** @var null|User $user */
        $user = User::findByWechatOa($this->getRequest()->getClientAppid(), $userInfo['openid']);
        $user = $user ?? new User;
        $user->type = UserTypeEnum::WECHAT_OA;
        $user->appid = $this->getRequest()->getClientAppid();
        $user->openid = $userInfo['openid'];
        $user->sub_openid = '';
        $user->unionid = $userInfo['unionid'] ?? '';
        $user->sub_unionid = '';
        $user->last_login_at = Carbon::now();
        if (true !== $user->resetModelVersion()->save()) {
            throw new RuntimeException('用户保存失败, 请您稍后再试!');
        }

        return $user;
    }

    /**
     * 通过code获取用户信息
     * @throws UserException
     * @throws Throwable
     */
    protected function getUserInfo(string $appid, string $code): array
    {
        return $this->getOrSet([__METHOD__, $appid, $code], function () use ($code, $appid) {
            try {
                $app = WeChat::officialAccount($appid);

                $oauthUser = $app->getOAuth()->userFromCode($code);
                $userData = $oauthUser->getRaw();

                if (!isset($userData['openid'])) {
                    $userData['openid'] = $oauthUser->getId();
                }

                return $userData;
            } catch (Throwable $exception) {
                if (str_contains($exception->getMessage(), 'invalid code') || str_contains($exception->getMessage(), '40029')) {
                    throw new UserException('授权码已过期，请重新授权！');
                }

                throw $exception;
            }
        });
    }
}

