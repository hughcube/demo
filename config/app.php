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
        // 核心认证鉴权：承载 AuthManager、用户上下文与 Gate 权限门面
        Illuminate\Auth\AuthServiceProvider::class,

        // 核心裁剪：纯 RESTful API 无 WebSocket 双向广播需求，消除广播管理器与路由初始化
        #Illuminate\Broadcasting\BroadcastServiceProvider::class,

        // 异步分发总线：承载 Dispatcher 与命令分发
        Illuminate\Bus\BusServiceProvider::class,

        // 缓存管理器：承载 Cache 门面与多种 Cache Store 驱动适配
        Illuminate\Cache\CacheServiceProvider::class,

        // 核心裁剪：HTTP 常驻 Worker 彻底剔除数百个控制台命令反射与参数定义，仅在 artisan CLI 入口与测试按需注册
        #Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,

        // 核心裁剪：常驻内存使用 Swoole 原生协程并发机制，无需 Laravel 11+ 基于多进程/Fork 的 Concurrency 抽象
        #Illuminate\Concurrency\ConcurrencyServiceProvider::class,

        // 核心裁剪：全面基于 Header Token/JWT 无状态鉴权，卸载 CookieJar 解析、签名校验与加解密开销
        #Illuminate\Cookie\CookieServiceProvider::class,

        // 数据库持久层：承载 DatabaseManager、Connection 注册与 Eloquent ORM
        Illuminate\Database\DatabaseServiceProvider::class,

        // 加解密核心：承载 Encrypter(AES-256) 与加解密门面
        Illuminate\Encryption\EncryptionServiceProvider::class,

        // 文件系统驱动：承载 FilesystemManager(本地/云存储驱动)
        Illuminate\Filesystem\FilesystemServiceProvider::class,

        // 框架核心基石服务：严禁裁剪误杀！
        // 1. 维护模式开销源自 HTTP 中间件(PreventRequestsDuringMaintenance)，应用全局中间件早已彻底剔除，HTTP 请求链路耗时与 I/O 为纯零开销；
        // 2. 控制台命令(artisan about、schedule:run)与调度守护进程强依赖其注册的 MaintenanceMode 契约，裁剪会导致 Target not instantiable 崩溃；
        // 3. 同时承载 HttpFactory(HTTP Client)、FormRequest 自动验证及 Request 宏等关键底座，必须保留。
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,

        // 密码哈希服务：必须保留！承载 Model 属性 password=>hashed 转换、Hash 门面及密码校验规则；为 Deferrable 惰性单例，不被调用时零运行时损耗
        Illuminate\Hashing\HashServiceProvider::class,

        // 核心裁剪：业务完全不使用 Laravel Mail 发信系统，告警与通知走专属通道，彻底剔除发信驱动栈
        #Illuminate\Mail\MailServiceProvider::class,

        // 核心裁剪：业务完全不使用 Laravel Notification 通知渠道，消息通知统一走钉钉/企微等专属通道，彻底剔除通知管理器
        #Illuminate\Notifications\NotificationServiceProvider::class,

        // 核心裁剪：团队规范统一由控制器/Repository 显式处理分页参数(page/limit)，禁止使用框架默认 $query->paginate()，消除 Paginator 请求解析器与 Blade HTML 视图加载开销
        #Illuminate\Pagination\PaginationServiceProvider::class,

        // 管道服务：必须保留！承载 Pipeline 门面调用契约(Pipeline::send)；原生实现 DeferrableProvider 按需延迟解析，零常驻损耗
        Illuminate\Pipeline\PipelineServiceProvider::class,

        // 消息队列系统：承载 QueueManager、队列任务分发与监听
        Illuminate\Queue\QueueServiceProvider::class,

        // Redis 驱动服务：必须保留！实现 DeferrableProvider 惰性连接，仅在显式调用 Redis 门面或配置 Redis 驱动时按需初始化
        Illuminate\Redis\RedisServiceProvider::class,

        // 核心裁剪：无状态 API 走手机短信验证码或三方授权，无需 Web 找回密码邮件与 Password Broker 支持
        #Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,

        // 核心裁剪：全面基于 JWT/Token 无状态认证，彻底消除 Web Session 读写与 Session 锁 I/O
        #Illuminate\Session\SessionServiceProvider::class,

        // 多语言本地化：承载 Translator 与验证提示语翻译字典
        Illuminate\Translation\TranslationServiceProvider::class,

        // 核心表单验证：承载 ValidationFactory 与全部内置校验规则
        Illuminate\Validation\ValidationServiceProvider::class,

        // 视图渲染引擎：必须保留！控制台命令渲染(Termwind/Table)与核心异常处理渲染链路底层对 'view' 存在隐式绑定依赖
        Illuminate\View\ViewServiceProvider::class,

        // 当前应用无全局强制启动逻辑，省去空 AppServiceProvider 加载与 boot() 调用
        #App\Providers\AppServiceProvider::class,
    ],
];
