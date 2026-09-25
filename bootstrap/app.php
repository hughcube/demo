<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        using: function () {
            Route::middleware('web')->group(base_path('routes/web.php'));

            Route::prefix('/api')->middleware('api')->group(base_path('routes/api.php'));

            Route::prefix('/devops')->middleware('devops')->group(base_path('routes/devops.php'));
        },
        commands: base_path('routes/console.php'),
    )
    ->withSchedule(function (Schedule $schedule) {
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([

        ]);

        /** global middleware */
        $middleware->use([
            // 阿里云函数计算(FC)/Serverless 环境下，HTTP 响应一旦输出给客户端，实例立刻被冻结(Freeze)，CPU 暂停调度。
            // 若在响应输出之后才清理超时定时器，会因实例已被冻结而来不及执行，导致下次解冻唤醒时定时器误判超时或发生泄漏。
            // 因此必须将该中间件置于最外层洋葱模型中：在响应真正发往客户端之前(finally 块)，提前从 timerTable 彻底移除 TimeOutTimer。
            \HughCube\Laravel\Octane\Middleware\ClearTimeOutTimerGuard::class,

            \HughCube\Profiler\Laravel\Middleware::class,
            #\HughCube\Laravel\Knight\Http\Middleware\TrustProxies::class,
            #\HughCube\Laravel\Knight\Http\Middleware\SetHstsHeaderIfHttps::class,
            \HughCube\Laravel\Knight\Http\Middleware\HandleAllPathCors::class,
        ]);

        /** web middleware group: 纯无状态 API 骨架不启用传统 Web 中间件栈，直接置空覆盖官方默认 Cookie/Session/CSRF 中间件 */
        $middleware->group('web', [
        ]);

        /** api middleware group */
        $middleware->group('api', [
            \App\Http\Middleware\Authenticate::class,
            \App\Http\Middleware\SignatureValidate::class,
        ]);

        /** devOps middleware group */
        $middleware->group('devops', [
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

    })
    ->withProviders([
        \App\Providers\AppServiceProvider::class,
    ])
    ->withSingletons([
        \Illuminate\Contracts\Debug\ExceptionHandler::class => \App\Exceptions\Handler::class
    ])
    ->create();
