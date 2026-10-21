<?php

declare(strict_types=1);

use App\Http\Controllers\App\Login\AliPayMpController;
use App\Http\Controllers\App\Login\GuestController;
use App\Http\Controllers\App\Login\LocalController;
use App\Http\Controllers\App\Login\PostmenController;
use App\Http\Controllers\App\Login\WeChatH5Controller;
use App\Http\Controllers\App\Login\WeChatMpController;
use Illuminate\Support\Facades\Route;

Route::prefix('login')->group(function () {
    Route::any('guest', GuestController::class);
    Route::any('local', LocalController::class);
    Route::any('postmen', PostmenController::class);
    Route::any('wechat-mp', WeChatMpController::class);
    Route::any('wechat-h5', WeChatH5Controller::class);
    Route::any('alipay-mp', AliPayMpController::class);
});

Route::prefix('benchmark')
    ->withoutMiddleware([
        \App\Http\Middleware\Authenticate::class,
        \App\Http\Middleware\SignatureValidate::class,
    ])
    ->group(function () {
    Route::any('ping', \App\Http\Controllers\Benchmark\PingController::class);
    Route::get('item', \App\Http\Controllers\Benchmark\ItemDetailController::class);
    Route::post('item', \App\Http\Controllers\Benchmark\ItemCreateController::class);
    Route::any('biz-action', \App\Http\Controllers\Benchmark\BizActionController::class);
    Route::any('seed', \App\Http\Controllers\Benchmark\SeedController::class);

    // 贴合真实生产业务场景的高拟真压测接口
    Route::any('user/login-check', \App\Http\Controllers\Benchmark\UserLoginCheckController::class);
    Route::get('item/list', \App\Http\Controllers\Benchmark\ItemListController::class);
    Route::get('item/detail-full', \App\Http\Controllers\Benchmark\ItemDetailFullController::class);
    Route::any('item/interact', \App\Http\Controllers\Benchmark\ItemInteractController::class);
});
