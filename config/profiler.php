<?php

use GuzzleHttp\RequestOptions;
use HughCube\GuzzleHttp\Middleware\UseHostResolveMiddleware;
use HughCube\Profiler\Profiler;
use HughCube\Profiler\ProfilingFlags;

return [
    'save.handler' => Profiler::SAVER_UPLOAD,

    'save.handler.file' => [
        'file' => storage_path('xhprof/profiler.data'),
    ],

    'save.handler.upload' => [
        'base_uri' => env('PROFILER_HTTP_BASE_URI', 'http://127.0.0.1:9092/api/upload'),
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

    // 1%的采样率(max: 1000000)
    'enable.probability' => intval(floatval(env('PROFILER_ENABLE_PROBABILITY', 0.0001)) * 1000000),
    'enable' => [],
];
