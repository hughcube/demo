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
