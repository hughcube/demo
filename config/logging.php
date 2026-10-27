<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that is utilized to write
    | messages to your logs. The value provided here should match one of
    | the channels present in the list of "channels" configured below.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Application Log Mode
    |--------------------------------------------------------------------------
    |
    */

    'path' => env('LOG_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'deprecations'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Laravel
    | utilizes the Monolog PHP logging library, which includes a variety
    | of powerful log handlers and formatters that you're free to use.
    |
    | Available drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog", "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_STACK', 'error,alarm,app')),
            'ignore_exceptions' => false,
        ],

        'error' => [
            'driver' => 'daily',
            'path' => log_path('laravel-error.log'),
            'level' => 'warning',
            'days' => 0,
        ],

        'app' => [
            'driver' => 'daily',
            'path' => log_path('laravel-app.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 0,
        ],

        'healthcheck' => [
            'driver' => 'daily',
            'path' => log_path('laravel-healthcheck.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 0,
        ],

        'deprecations' => [
            'driver' => 'daily',
            'path' => log_path('laravel-deprecation.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 0,
        ],

        'alarm' => [
            'driver' => 'monolog',
            'level' => 'warning',
            'handler' => \HughCube\Laravel\DingTalk\Log\Handler::class,
            'handler_with' => [
                'enabled' => filter_var(env('DING_TALK_ROBOT_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
                'robot' => null,
                'bubble' => true,
            ],
        ],

        'queue' => [
            'driver' => 'daily',
            'path' => log_path('laravel-queue.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 0,
            'bubble' => false,
        ],

        'schedule' => [
            'driver' => 'daily',
            'path' => log_path('laravel-schedule.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 0,
            'bubble' => false,
        ],

        'stdout' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => \Monolog\Handler\StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stdout',
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => \Monolog\Handler\StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => \Monolog\Handler\NullHandler::class,
        ],
    ],

];
