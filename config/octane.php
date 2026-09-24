<?php

use Illuminate\Support\Collection;

return [

    /*
    |--------------------------------------------------------------------------
    | Octane Server
    |--------------------------------------------------------------------------
    |
    | This value determines the default "server" that will be used by Octane
    | when starting, restarting, or stopping your server via the CLI. You
    | are free to change this to the supported server of your choosing.
    |
    | Supported: "roadrunner", "swoole", "frankenphp"
    |
    */

    'server' => env('OCTANE_SERVER', 'swoole'),

    /*
    |--------------------------------------------------------------------------
    | Octane State File
    |--------------------------------------------------------------------------
    |
    | This value determines the path to the state file that Octane will use
    | to store the server state. This file is used to communicate between
    | the server and the CLI commands.
    |
    */

    'state_file' => env('OCTANE_STATE_FILE', storage_path('logs/octane-server-state.json')),

    /*
    |--------------------------------------------------------------------------
    | Force HTTPS
    |--------------------------------------------------------------------------
    |
    | When this configuration value is set to "true", Octane will inform the
    | framework that all absolute links must be generated using the HTTPS
    | protocol. Otherwise your links may be generated using plain HTTP.
    |
    */

    'https' => env('OCTANE_HTTPS', false),

    /*
    |--------------------------------------------------------------------------
    | Octane Listeners
    |--------------------------------------------------------------------------
    |
    | All of the event listeners for Octane's events are defined below. These
    | listeners are responsible for resetting your application's state for
    | the next request. You may even add your own listeners to the list.
    |
    */

    'listeners' => [
        \Laravel\Octane\Events\WorkerStarting::class => [
            \Laravel\Octane\Listeners\EnsureUploadedFilesAreValid::class,
            \Laravel\Octane\Listeners\EnsureUploadedFilesCanBeMoved::class,
        ],

        \Laravel\Octane\Events\RequestReceived::class => Collection::make([
            #\HughCube\Laravel\Octane\Listeners\PrepareServerVariables::class,
            ...\Laravel\Octane\Octane::prepareApplicationForNextOperation(),
            ...\Laravel\Octane\Octane::prepareApplicationForNextRequest(),
        ])->diff(Collection::make([
            // 对于高性能无状态 REST API 服务，精简掉 Laravel 官方默认针对传统 Web 场景的
            // Session/Cookie/多语言/沙箱/管理器的重置监听器，消除每次请求的反射实例化与 GC 损耗，
            // 使 Worker 维持微秒级事件循环吞吐。

            // 无状态 API 请求结束即回收短期变量，裁剪后省去每次请求遍历清空哈希表开销，
            // 并允许局部跨调用安全复用微缓存。
            \Laravel\Octane\Listeners\FlushArrayCache::class,

            // 通过请求头统一中间件按需设置语言，无需每次请求在全局事件中重复做默认值还原。
            \Laravel\Octane\Listeners\FlushLocaleState::class,

            // 全面基于 Token/JWT 鉴权，全局不启用 Web Session，无状态污染风险，
            // 彻底省去 Session 重置开销。
            \Laravel\Octane\Listeners\FlushSessionState::class,

            // API 交互全走 JSON 与 Header，不依赖也从不产生排队 Cookie，剔除无意义的队列排空动作。
            \Laravel\Octane\Listeners\FlushQueuedCookies::class,

            // 生产环境由前置 Caddy/ALB 网关完成 SSL 卸载并通过协议头透传，
            // 底层容器固定监听 HTTP，无需由每个 Worker 逐请求重复重写协议。
            \Laravel\Octane\Listeners\EnforceRequestScheme::class,

            // API 几乎不动态篡改路由基地址；沙箱克隆与销毁会增加 GC 垃圾回收负担，
            // 以单例常驻复用性能最优。
            \Laravel\Octane\Listeners\CreateUrlGeneratorSandbox::class,

            // 常驻内存严禁动态篡改 config() 是铁律，克隆整棵配置树开销巨大；
            // 裁剪后直接以只读单例复用构建期缓存好的单一数组，零内存克隆损耗。
            \Laravel\Octane\Listeners\CreateConfigurationSandbox::class,

            #\Laravel\Octane\Listeners\GiveNewRequestInstanceToPaginator::class,

            // 容器内固定单端口监听，外部端口由网关统一映射，业务无需依赖该环境变更，省去无谓计算。
            \Laravel\Octane\Listeners\EnsureRequestServerPortMatchesScheme::class,

            // API 业务邮件全走异步队列投递，无需在同步 HTTP 主循环中每次反射反弹容器实例。
            \Laravel\Octane\Listeners\GiveNewApplicationInstanceToMailManager::class,

            // 无状态 API 停用 Session，该管理器根本不被使用，重新注入纯属冗余。
            \Laravel\Octane\Listeners\GiveNewApplicationInstanceToSessionManager::class,

            // API 请求无同步实时广播需求，异步化场景无需在同步主循环重新绑定容器。
            \Laravel\Octane\Listeners\GiveNewApplicationInstanceToBroadcastManager::class,

            // 系统未启用数据库 Session 存储机制，完全无用。
            \Laravel\Octane\Listeners\GiveNewApplicationInstanceToDatabaseSessionHandler::class,

            // 通知统一通过队列解耦异步发送，同步请求中无需逐次绑定容器。
            \Laravel\Octane\Listeners\GiveNewApplicationInstanceToNotificationChannelManager::class,

            // 骨架未引入前端路由(Inertia)、搜索(Scout)、全栈组件(Livewire)或三方 OAuth(Socialite)，
            // 直接从事件链剔除，避免每次产生空判断与冗余反射。
            !class_exists(\Inertia\ResponseFactory::class) ? \Laravel\Octane\Listeners\PrepareInertiaForNextOperation::class : null,
            !class_exists(\Laravel\Scout\EngineManager::class) ? \Laravel\Octane\Listeners\PrepareScoutForNextOperation::class : null,
            !class_exists(\Livewire\LivewireManager::class) ? \Laravel\Octane\Listeners\PrepareLivewireForNextOperation::class : null,
            !class_exists(\Laravel\Socialite\Contracts\Factory::class) ? \Laravel\Octane\Listeners\PrepareSocialiteForNextOperation::class : null,
        ])->filter()->values())->values()->toArray(),

        \Laravel\Octane\Events\RequestHandled::class => [
            //
        ],

        \Laravel\Octane\Events\RequestTerminated::class => [
            \Laravel\Octane\Listeners\FlushUploadedFiles::class,
        ],

        \Laravel\Octane\Events\TaskReceived::class => [
            ...\Laravel\Octane\Octane::prepareApplicationForNextOperation(),
            //
        ],

        \Laravel\Octane\Events\TaskTerminated::class => [
            //
        ],

        \Laravel\Octane\Events\TickReceived::class => [
            ...\Laravel\Octane\Octane::prepareApplicationForNextOperation(),
            //
        ],

        \Laravel\Octane\Events\TickTerminated::class => [
            //
        ],

        \Laravel\Octane\Contracts\OperationTerminated::class => [
            \Laravel\Octane\Listeners\FlushOnce::class,
            \Laravel\Octane\Listeners\FlushTemporaryContainerInstances::class,

            // 保持常驻连接池复用：注释掉 DisconnectFromDatabases，避免每个 HTTP 请求结束都销毁并重建数据库连接；
            // 配合 PDO::ATTR_PERSISTENT 与 wait_timeout，实现近乎零握手延迟的极速查询
            #\Laravel\Octane\Listeners\DisconnectFromDatabases::class,

            \Laravel\Octane\Listeners\CollectGarbage::class,
        ],

        \Laravel\Octane\Events\WorkerErrorOccurred::class => [
            \Laravel\Octane\Listeners\ReportException::class,
            \Laravel\Octane\Listeners\StopWorkerIfNecessary::class,
        ],

        \Laravel\Octane\Events\WorkerStopping::class => [
            \Laravel\Octane\Listeners\CloseMonologHandlers::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Warm / Flush Bindings
    |--------------------------------------------------------------------------
    |
    | The bindings listed below will either be pre-warmed when a worker boots
    | or they will be flushed before every new request. Flushing a binding
    | will force the container to resolve that binding again when asked.
    |
    */

    'warm' => [
        ...\Laravel\Octane\Octane::defaultServicesToWarm(),
    ],

    'flush' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Octane Swoole Tables
    |--------------------------------------------------------------------------
    |
    | While using Swoole, you may define additional tables as required by the
    | application. These tables can be used to store data that needs to be
    | quickly accessed by other workers on the particular Swoole server.
    |
    */

    'tables' => [
    ],

    /*
    |--------------------------------------------------------------------------
    | Octane Swoole Cache Table
    |--------------------------------------------------------------------------
    |
    | While using Swoole, you may leverage the Octane cache, which is powered
    | by a Swoole table. You may set the maximum number of rows as well as
    | the number of bytes per row using the configuration options below.
    |
    */

    'cache' => [
        'rows' => 1000,
        'bytes' => 10000,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Watching
    |--------------------------------------------------------------------------
    |
    | The following list of files and directories will be watched when using
    | the --watch option offered by Octane. If any of the directories and
    | files are changed, Octane will automatically reload your workers.
    |
    */

    'watch' => [
        'app',
        'bootstrap',
        'config/**/*.php',
        'database/**/*.php',
        'public/**/*.php',
        'resources/**/*.php',
        'routes',
        'composer.lock',
        '.env',
    ],

    /*
    |--------------------------------------------------------------------------
    | Garbage Collection Threshold
    |--------------------------------------------------------------------------
    |
    | When executing long-lived PHP scripts such as Octane, memory can build
    | up before being cleared by PHP. You can force Octane to run garbage
    | collection if your application consumes this amount of megabytes.
    |
    */

    'garbage' => 50,

    /*
    |--------------------------------------------------------------------------
    | Maximum Execution Time
    |--------------------------------------------------------------------------
    |
    | The following setting configures the maximum execution time for requests
    | being handled by Octane. You may set this value to 0 to indicate that
    | there isn't a specific time limit on Octane request execution time.
    |
    */

    'max_execution_time' => intval(env('OCTANE_MAX_EXECUTION_TIME', 30)),

    'tick' => false,

    // 性能铁律（动静分离）：生产环境保持 false。
    // 静态文件应 100% 由前置网关(Caddy/Nginx)或 CDN/OSS 处理(零拷贝 sendfile)；
    // 严禁让静态文件请求打到 PHP Worker 占用有限的计算进程，必须把 Worker 算力 100% 留给核心 API 动态请求。
    'serve_static_files' => boolval(env('OCTANE_SERVE_STATIC_FILES', false)),

    'swoole' => [
        // 保持 false。镜像在构建期已通过 opcache:compile-files 与 opcache:create-preload
        // 完成了 AOT 全量字节码编译与常驻预热；清空会导致预热失效，严重劣化冷启动性能。
        'clear_opcache' => boolval(env('OCTANE_CLEAR_OPCACHE', false)),

        // 默认 SWOOLE_PROCESS (多进程模型)，由 Master + Manager + Worker 协作管理，
        // 支持独立热重载(Reload)与 Task 进程隔离，容器化下最稳定。
        #'mode' => SWOOLE_BASE,

        'options' => [
            // 由容器运行时或安全上下文(securityContext)统一约束，无需 Swoole 代码层重复 setuid。
            #'user' => 'www-data',
            #'group' => 'www-data',

            // 主进程内负责网络 I/O 多路复用(epoll)的线程数。建议与容器分配的 CPU 核心数保持 1:1，
            // 避免线程过多引发频繁的 CPU 上下文切换与锁开销。
            'reactor_num' => intval(env('OCTANE_REACTOR_NUM', 1)),

            // 抢占模式(SWOOLE_DISPATCH_PREEMPTIVE)，仅投递给当前空闲的 Worker。
            // 在同步阻塞式业务(SQL查询/外部调用)场景下，能杜绝因慢请求排队阻塞后序快请求，保障最低平均延迟。
            'dispatch_mode' => 3,

            // Task 进程间通信方式。默认 1 (Unix Socket) 内存管道传输性能最优，无需额外依赖系统内核 IPC 消息队列。
            #'task_ipc_mode' => 1,

            // Worker 平滑重启(Graceful Reload/Shutdown)时等待未完成请求的最大超时秒数。
            // 给予长业务事务充分的执行时间，防止容器滚动更新或缩容时粗暴 SIGKILL 导致请求中断报 502 或写数据不一致。
            'max_wait_time' => 30,

            // 异步平滑重启。Swoole 现代版本底层已默认开启异步安全退出机制。
            #'reload_async' => true,

            // 禁用 TCP Nagle 算法。前端有网关(Caddy/ALB)代理，HTTP 响应通常一次性通过网络缓冲区写出，开启收益微弱。
            #'open_tcp_nodelay' => true,

            // Laravel 本身为同步阻塞架构，多进程模式下单 Worker 串行处理单请求隔离性最好、稳定性最高，避免协程上下文混淆。
            #'enable_coroutine' => false,

            // Linux 内核级 SO_REUSEPORT。单 Pod 容器内仅单个 Swoole Server 监听端口，
            // 不存在单机多服务争抢 accept，流量分发由外部网关处理。
            #'enable_reuse_port' => true,

            // 必须为 false。Gzip 压缩属于高 CPU 消耗型操作，必须卸载给前置网关(Caddy/Nginx)或 CDN 处理，
            // 绝对不能挤占 PHP Worker 的 CPU 核心算力。
            'http_compression' => false,

            // TCP 监听队列最大长度(Accept 队列)。在突发高并发流量进入时防止队列溢出导致连接被丢弃或重试。
            // 需配合系统内核 net.core.somaxconn 同步调优使用。
            'backlog' => intval(env('OCTANE_BACKLOG', 2048)),

            // 延迟接收投递。在抢占模式(dispatch_mode=3)下，客户端仅建立 TCP 连接但不发数据时，
            // Reactor 不会将连接分发给 Worker；只有当首个 HTTP 请求数据包真正到达时才投递，
            // 杜绝空闲连接占用宝贵的 PHP Worker 进程。
            'enable_delay_receive' => true,

            // 启用 TCP 底层保活机制，由 Linux 内核主动探测死连接与异常中断，
            // 防止网关与 Swoole 之间的长连接静默断开导致 Worker 向已失效的 socket 写入产生阻塞或异常。
            'open_tcp_keepalive' => true,
            'tcp_keepidle' => 60,
            'tcp_keepinterval' => 10,
            'tcp_keepcount' => 3,

            // CPU 核心亲和性绑定。将 Worker 进程与 Reactor 线程绑定到固定物理核心，
            // 减少跨核心调度导致的 CPU L1/L2 高速缓存(Cache Line)失效，显著降低高并发下的 P99 尾部延迟。
            #'open_cpu_affinity' => true,

            // TCP Fast Open(TFO)。允许在三次握手阶段携带 SYN 数据包直接发起首包数据传输，消除 1 个握手 RTT 往返时延。
            // 需 Linux 内核 net.ipv4.tcp_fastopen 支持。
            #'tcp_fastopen' => true,

            // 网络读写与数据包缓冲区大小。针对大 JSON 列表或图片上传，避免分段多次系统调用或爆出缓冲区溢出错误。
            #'buffer_output_size' => 32 * 1024 * 1024,
            #'socket_buffer_size' => 64 * 1024 * 1024,
            #'package_max_length' => 20 * 1024 * 1024,

            // 生产环境(非Debug)仅记录严重系统异常，杜绝频繁打 trace 日志造成容器磁盘 I/O 竞争与日志刷屏。
            'log_level' => env('APP_DEBUG') ? 0 /** SWOOLE_LOG_DEBUG */ : 5 /** SWOOLE_LOG_ERROR */,

            // 每日自动切分归档(SWOOLE_LOG_ROTATION_DAILY)，防止单个日志文件无限膨胀撑爆容器只读根分区。
            'log_rotation' => 2, // SWOOLE_LOG_ROTATION_DAILY

            // Swoole 守护进程运行日志，统一收敛到 storage/run/，便于容器卷持久化与排查。
            'log_file' => storage_path('run/swoole_http.log'),
        ],
    ],

];
