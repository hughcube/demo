<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Demo'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'Asia/Shanghai'),

    'schedule_timezone' => env('APP_TIMEZONE', 'Asia/Shanghai'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'zh'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'zh'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'zh_CN'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    'providers' => [
        Illuminate\Auth\AuthServiceProvider::class,

        // 纯 RESTful API 无 WebSocket 双向广播需求，消除广播管理器初始化
        #Illuminate\Broadcasting\BroadcastServiceProvider::class,

        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,

        // 核心裁剪：HTTP 请求剔除数百个控制台命令反射与参数定义，仅在 artisan CLI 入口按需注册
        #Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,

        // 常驻内存使用 Swoole 原生并发机制，无需 Laravel 11+ 并发闭包多进程抽象
        #Illuminate\Concurrency\ConcurrencyServiceProvider::class,

        // 无状态 Token 鉴权，卸载 CookieJar 解析与加解密开销
        #Illuminate\Cookie\CookieServiceProvider::class,

        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,

        // 云原生架构由网关摘流，无需应用单机维护模式与旧式命令支持
        #Illuminate\Foundation\Providers\FoundationServiceProvider::class,

        // API 认证走第三方或原生 password_hash，无需顶层默认长驻 HashManager
        #Illuminate\Hashing\HashServiceProvider::class,

        // 发信与通知全量异步丢入队列处理，同步 HTTP 链路不加载庞大发信驱动栈
        #Illuminate\Mail\MailServiceProvider::class,
        #Illuminate\Notifications\NotificationServiceProvider::class,

        // API 返回纯 JSON 数据结构，无需服务端渲染 HTML Tailwind/Bootstrap 分页标签视图
        #Illuminate\Pagination\PaginationServiceProvider::class,

        // 密码重置走手机短信验证码或三方授权，无需 Web 找回密码邮件与 Token 链接支持
        #Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,

        // 洋葱中间件执行直接 new Pipeline，无需在容器中绑定全局单例 Hub
        #Illuminate\Pipeline\PipelineServiceProvider::class,

        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,

        // 全面基于 JWT/Token 无状态认证，彻底消除 Web Session 读写与锁 I/O
        #Illuminate\Session\SessionServiceProvider::class,

        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,

        // 框架控制台命令(ConsoleSupport)与核心异常处理链路底层对 'view' 存在隐式绑定依赖，必须保留
        Illuminate\View\ViewServiceProvider::class,

        // 当前应用无全局强制启动逻辑，省去空 AppServiceProvider 加载与 boot() 调用
        #App\Providers\AppServiceProvider::class,
    ],
];
