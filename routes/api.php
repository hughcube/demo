<?php

use App\Http\Api\Controllers\Login\AliPayMpController;
use App\Http\Api\Controllers\Login\GuestController;
use App\Http\Api\Controllers\Login\LocalController;
use App\Http\Api\Controllers\Login\PostmenController;
use App\Http\Api\Controllers\Login\WeChatH5Controller;
use App\Http\Api\Controllers\Login\WeChatMpController;
use Illuminate\Support\Facades\Route;

Route::prefix('login')->group(function () {
    Route::any('guest', GuestController::class);
    Route::any('local', LocalController::class);
    Route::any('postmen', PostmenController::class);
    Route::any('wechat-mp', WeChatMpController::class);
    Route::any('wechat-h5', WeChatH5Controller::class);
    Route::any('alipay-mp', AliPayMpController::class);
});
