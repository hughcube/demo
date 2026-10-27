<?php

declare(strict_types=1);

/**
 * DevOps 运维与监控路由
 */

use HughCube\Laravel\Knight\Http\Actions\Devops\HealthcheckAction;
use HughCube\Laravel\Knight\Http\Actions\Devops\PhpInfoAction;
use HughCube\Laravel\Knight\Http\Actions\Scheduling\ScheduleAction;
use HughCube\Laravel\Knight\Http\Middleware\VerifyDevopsToken;
use HughCube\Laravel\Knight\Http\Middleware\VerifyScheduleToken;
use Illuminate\Support\Facades\Route;

/** healthcheck: 统一存活探针，零外部依赖，返回 200 success */
Route::any('/healthcheck', HealthcheckAction::class);

/** phpinfo: 必须使用 token 鉴权，1:1 原生 HTML 渲染 */
Route::any('/phpinfo', PhpInfoAction::class)->middleware(VerifyDevopsToken::class);

/** schedule: 统一调度触发网关，必须使用 token 鉴权，供外部拨测心跳调用 */
Route::any('/schedule', ScheduleAction::class)->middleware(VerifyScheduleToken::class);
