<?php

declare(strict_types=1);

/**
 * DevOps 运维与监控路由
 */

use App\Http\Controllers\DevOps\ClientInfoController as DevOpsClientInfoController;
use App\Http\Controllers\DevOps\HealthcheckController as DevOpsHealthcheckController;
use App\Http\Controllers\DevOps\QueueDepthController as DevOpsQueueDepthController;
use App\Http\Controllers\DevOps\StatusController as DevOpsStatusController;
use App\Http\Controllers\DevOps\VersionController as DevOpsVersionController;
use App\Http\Middleware\OnlyLocalGuard;
use App\Http\Middleware\OnlyPrivateIpGuard;
use HughCube\Laravel\Knight\OPcache\Actions\ResetAction as OPcacheResetAction;
use Illuminate\Support\Facades\Route;

/** healthcheck (K8s/SLB 存活探针，零外部依赖，极轻量) */
Route::any('/healthcheck', DevOpsHealthcheckController::class);

/** version (部署版本与构建时间，流水线发布烟测校验) */
Route::any('/version', DevOpsVersionController::class);

/** status (依赖健康就绪检查看板，含 DB / Redis 连通性与延迟，防外部高频探测限内网) */
Route::any('/status', DevOpsStatusController::class)->middleware(OnlyPrivateIpGuard::class);

/** client-info (客户端网络与设备指纹环境识别，仅限内网排查) */
Route::any('/client-info', DevOpsClientInfoController::class)->middleware(OnlyPrivateIpGuard::class);

/** queue-depth (异步队列积压深度，用于 KEDA 自动弹性扩缩容指标，仅限内网) */
Route::any('/queue-depth', DevOpsQueueDepthController::class)->middleware(OnlyPrivateIpGuard::class);

/** opcache reset (PHP OPcache 字节码缓存重置，高危变更操作，仅限本地/容器内) */
Route::any('/opcache/reset', OPcacheResetAction::class)->middleware(OnlyLocalGuard::class);
