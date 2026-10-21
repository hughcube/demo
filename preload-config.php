<?php

/**
 * OPcache Preload 配置文件 (OPcache Preload Configuration)
 *
 * 用于定制 php artisan opcache:create-preload 收集规则。
 */

return [
    /**
     * 需显式预加载的命名空间白名单（自动通过 ClassMap 安全扫描）
     */
    'include_namespaces' => [
        'App\\',
        'HughCube\\Laravel\\Knight\\',
        'HughCube\\Laravel\\Octane\\',
        'Illuminate\\Routing\\',
        'Illuminate\\Http\\',
        'Illuminate\\Pipeline\\',
        'Illuminate\\Support\\',
        'Illuminate\\Database\\Eloquent\\',
        'Illuminate\\Database\\Query\\',
        'Illuminate\\Events\\',
        'Illuminate\\Cache\\',
        'Illuminate\\Validation\\',
    ],

    /**
     * 需额外显式预加载的具体类名清单
     */
    'include_classes' => [
        // 例如指定核心服务类
    ],

    /**
     * 排除的命名空间黑名单（严禁常驻生产共享内存）
     */
    'exclude_namespaces' => [
        'Tests\\',
        'PHPUnit\\',
        'Mockery\\',
        'Faker\\',
        'Spatie\\LaravelIgnition\\',
        'Spatie\\Ignition\\',
        'Symfony\\Component\\VarDumper\\',
        'Laravel\\Telescope\\',
        'Clockwork\\',
        'Illuminate\\Testing\\',
        'Database\\Migrations\\',
        'Barryvdh\\LaravelIdeHelper\\',
        'Laravel\\Sail\\',
        'Laravel\\Pail\\',
        'NunoMaduro\\Collision\\',
    ],

    /**
     * 排除特定的具体类/接口/Trait
     */
    'exclude_classes' => [
        // 例如排除某些带有动态环境依赖的特定类
    ],
];
