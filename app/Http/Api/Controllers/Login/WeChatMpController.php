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

class WeChatMpController extends AAAController
{
    protected function rules(): array
    {
        return [
            'code' => ['required', 'string', 'min:1'],
        ];
    }

    /**
     * 获取或创建微信小程序用户
     * @throws Throwable
     */
    protected function getOrCreateUser(): User
    {
        $session = $this->code2session($this->getRequest()->getClientAppid(), $this->p('code'));

        /** @var null|User $user */
        $user = User::findByWechatMp($this->getRequest()->getClientAppid(), $session['openid']);
        $user = $user ?? new User;
        $user->type = UserTypeEnum::WECHAT_MP;
        $user->appid = $this->getRequest()->getClientAppid();
        $user->openid = $session['openid'];
        $user->sub_openid = '';
        $user->unionid = $session['unionid'] ?? '';
        $user->sub_unionid = '';
        $user->last_login_at = Carbon::now();
        if (true !== $user->resetModelVersion()->save()) {
            throw new RuntimeException('用户保存失败, 请您稍后再试!');
        }

        return $user;
    }

    /**
     * 通过code换取session_key
     * @throws Throwable
     */
    protected function code2session(string $appid, string $code): array
    {
        return $this->getOrSet([__METHOD__, $appid, $code], function () use ($code, $appid) {
            try {
                $app = WeChat::miniApp($appid);

                $response = $app->getUtils()->codeToSession($code);

                if (!isset($response['openid']) || !isset($response['session_key'])) {
                    $errorMsg = $response['errmsg'] ?? '获取session信息失败';
                    throw new UserException($errorMsg);
                }

                return $response;
            } catch (Throwable $exception) {
                if (str_contains($exception->getMessage(), 'invalid code') || str_contains($exception->getMessage(), '40029')) {
                    throw new UserException('授权码已过期, 请关闭小程序重新授权！');
                }

                throw $exception;
            }
        });
    }
}

