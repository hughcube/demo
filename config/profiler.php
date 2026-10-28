<?php

use GuzzleHttp\RequestOptions;
use HughCube\Profiler\Profiler;
use HughCube\Profiler\ProfilingFlags;

return [
    /**
     * 默认上报处理器：若未配置 PROFILER_HTTP_BASE_URI，默认降级为 file 处理器（保存至 storage/xhprof/），
     * 避免因未配远程端点而在强开采样时发生网络连接崩溃。
     */
    'save.handler' => empty(env('PROFILER_HTTP_BASE_URI'))
        ? Profiler::SAVER_FILE
        : env('PROFILER_SAVE_HANDLER', Profiler::SAVER_UPLOAD),

    'save.handler.file' => [
        'file' => storage_path('xhprof/profiler.data'),
    ],

    'save.handler.upload' => [
        'base_uri' => env('PROFILER_HTTP_BASE_URI', null),
        RequestOptions::TIMEOUT => 30.0,
        RequestOptions::READ_TIMEOUT => 30.0,
        RequestOptions::CONNECT_TIMEOUT => 30.0,
        RequestOptions::VERIFY => false,
        RequestOptions::ALLOW_REDIRECTS => ['max' => 5],
        RequestOptions::HTTP_ERRORS => env('PROFILER_HTTP_ERRORS', true),
        RequestOptions::PROXY => env('PROFILER_HTTP_PROXY'),
        RequestOptions::HEADERS => [
            'User-Agent' => null,
            'Authentication' => env('PROFILER_HTTP_AUTHENTICATION'),
        ],
        'middlewares' => [
            \HughCube\Profiler\Saver\Middleware\ContentEncodingGzipMiddleware::class,
            \HughCube\Profiler\Saver\Middleware\AliyunFcAsyncMiddleware::class,
        ],
    ],

    'profiler.flags' => [
        ProfilingFlags::CPU,
        ProfilingFlags::MEMORY,
        #ProfilingFlags::NO_BUILTINS,
        #ProfilingFlags::NO_SPANS,
    ],
    'profiler.options' => [],

    'profiler.exclude-env' => [],

    'profiler.exclude-query' => [],

    'profiler.exclude-server' => array_values(array_map(fn($name) => strtolower($name), [
        'HTTP_X_FC_ACCESS_KEY_ID',
        'HTTP_X_FC_ACCESS_KEY_SECRET',
        'HTTP_X_FC_ACCOUNT_ID',
        'HTTP_X_FC_SECURITY_TOKEN',
    ])),

    /**
     * 采样率控制 (默认必须为 0，彻底关闭随机采样)：
     * 1. 性能分析仅在需要时显式开启，默认开启会在高并发或未配收集端时造成不必要的 CPU 开销与网络异常；
     * 2. 只有在配置了有效上报地址且显式指定 PROFILER_ENABLE_PROBABILITY 时才按概率采样 (最大值 1,000,000 代表 100%)。
     */
    'enable.probability' => empty(env('PROFILER_HTTP_BASE_URI'))
        ? 0
        : intval(floatval(env('PROFILER_ENABLE_PROBABILITY', 0)) * 1000000),

    'enable' => [],
];
