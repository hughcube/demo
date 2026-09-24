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
use HughCube\Laravel\Knight\Exceptions\UserException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use Yansongda\LaravelPay\Facades\Pay;
use Yansongda\Pay\Exception\Exception as PayException;
use Yansongda\Pay\Plugin\Alipay\V2\Member\Authorization\TokenPlugin as SystemOauthTokenPlugin;
use Yansongda\Supports\Collection as PayCollection;

class AliPayMpController extends AAAController
{
    protected function rules(): array
    {
        return [
            'code' => ['required', 'string', 'min:1'],
        ];
    }

    /**
     * 获取或创建支付宝小程序用户
     * @throws Throwable
     */
    protected function getOrCreateUser(): User
    {
        $session = $this->code2session($this->getRequest()->getClientAppid(), $this->p('code'));

        /** @var null|User $user */
        $user = User::findByAlipayMp($this->getRequest()->getClientAppid(), $session->get('open_id'));
        $user = $user ?? new User;
        $user->type = UserTypeEnum::ALIPAY_MP;
        $user->appid = $this->getRequest()->getClientAppid();
        $user->openid = $session->get('open_id');
        $user->sub_openid = '';
        $user->unionid = $session->get('union_id');
        $user->sub_unionid = '';
        $user->last_login_at = Carbon::now();
        if (true !== $user->resetModelVersion()->save()) {
            throw new RuntimeException('用户保存失败, 请您稍后再试!');
        }

        return $user;
    }

    protected function code2session($appid, $code): PayCollection
    {
        return $this->getOrSet([__METHOD__, $appid, $code], function () use ($code, $appid) {
            try {
                $alipay = Pay::alipay();
                $plugins = $alipay->mergeCommonPlugins([SystemOauthTokenPlugin::class]);
                return $alipay->pay($plugins, [
                    'code' => $code,
                    'grant_type' => 'authorization_code',
                    '_config' => $appid,
                ]);
            } catch (PayException $exception) {
                if (
                    is_array($exception->extra)
                    && Collection::make($exception->extra)
                        ->filter(fn($v) => is_string($v) && Str::contains($v, 'isv.code-invalid'))
                        ->isNotEmpty()
                ) {
                    throw new UserException('授权码已过期, 请关闭小程序重新授权！');
                }

                throw $exception;
            }
        });
    }
}

