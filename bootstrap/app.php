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
            \HughCube\Profiler\Laravel\Middleware::class,
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
