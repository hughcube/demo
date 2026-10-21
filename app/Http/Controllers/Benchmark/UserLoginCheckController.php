<?php

declare(strict_types=1);

namespace App\Http\Controllers\Benchmark;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * 场景 A: 贴合真实生产的用户身份鉴权与活跃状态流转业务
 * - 涵盖: 索引查询、新老用户分流、活跃时间更新、业务 Token 计算
 */
class UserLoginCheckController extends Controller
{
    protected function action(): Response
    {
        $request = $this->getRequest();
        $openid = $request->input('openid');
        if (empty($openid)) {
            $openid = 'user_openid_' . random_int(1, 500);
        } else {
            $openid = (string) $openid;
        }

        /** @var null|User $user */
        $user = User::query()->where('openid', $openid)->first();

        $isNew = false;
        if (!$user instanceof User) {
            $isNew = true;
            $user = new User();
            $user->type = 1;
            $user->appid = 'benchmark_app';
            $user->openid = $openid;
            $user->name = 'User_' . substr(md5($openid), 0, 8);
            $user->email = $openid . '@demo.local';
            $user->password = '';
            $user->access_secret = Str::random(32);
        }

        $user->last_login_at = Carbon::now();
        $user->save();

        // 模拟真实业务签发轻量业务访问凭证
        $sign = hash('sha256', "{$user->id}:{$user->openid}:{$user->last_login_at->timestamp}");

        return $this->asResponse([
            'code' => 200,
            'message' => 'ok',
            'data' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'is_new' => $isNew,
                'last_login_at' => $user->last_login_at->toDateTimeString(),
                'token' => substr($sign, 0, 32),
            ],
        ]);
    }
}
