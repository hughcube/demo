<?php

use Composer\Autoload\ClassLoader;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;

$basePath = getcwd();
$preloadFile = getenv('OUTPUT_PATH') ?: $basePath.'/preload.php';
$skipBootstrap = '1' === getenv('SKIP_BOOTSTRAP');

// 1. 预加载配置定义
$config = [
    /**
     * 需显式预加载的命名空间白名单
     */
    'include_namespaces' => [
        'App\\',
        'HughCube\\Laravel\\Knight\\',
        'HughCube\\Laravel\\Octane\\',
        'Illuminate\\Contracts\\',
        'Illuminate\\Container\\',
        'Illuminate\\Routing\\',
        'Illuminate\\Http\\',
        'Illuminate\\Pipeline\\',
        'Illuminate\\Support\\',
        'Illuminate\\Database\\Eloquent\\',
        'Illuminate\\Database\\Query\\',
        'Illuminate\\Events\\',
        'Illuminate\\Cache\\',
        'Illuminate\\Validation\\',
        'Symfony\\Component\\HttpFoundation\\',
    ],

    /**
     * 需额外显式预加载的具体类名清单
     */
    'include_classes' => [],

    /**
     * 严禁常驻共享内存的命名空间黑名单（开发、测试、调试与迁移）
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
     * 排除的具体类/接口/Trait 黑名单
     */
    'exclude_classes' => [],
];

// 记录初始系统类，后续用于 diff 提取新声明类
$initialClasses = array_merge(
    get_declared_classes(),
    get_declared_interfaces(),
    get_declared_traits()
);

// 2. 模拟标准环境上下文，避免引导过程因缺失请求环境产生副作用
call_user_func(function () {
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';
});

// 3. 引导 Laravel 基础应用 (Bootstrap Application)
call_user_func(function () use ($basePath, $skipBootstrap) {
    if ($skipBootstrap) {
        echo 'Skipping Laravel application bootstrap...', PHP_EOL;
        return;
    }

    echo 'Bootstrapping Laravel application...', PHP_EOL;

    $indexFile = $basePath.'/public/index.php';
    if (!is_file($indexFile)) {
        $indexFile = $basePath.'/index.php';
    }

    if (is_file($indexFile)) {
        ob_start();
        require $indexFile;
        ob_clean();
        echo 'Application bootstrapped successfully.', PHP_EOL;
    } else {
        fwrite(STDERR, 'Warning: index.php not found, skipping bootstrap.'.PHP_EOL);
    }
});

// 4. 深度安全发现与类预热 (Deep Safe Discovery & Class Warmup)
echo 'Discovering routes, middlewares, and application classes...', PHP_EOL;

$app = Container::getInstance();
$discoveredClasses = [];

// 4.1 遍历已注册路由及其关联控制器与中间件
if ($app && $app->bound('router')) {
    try {
        $routes = $app->make('router')->getRoutes();
        foreach ($routes as $route) {
            $action = $route->getActionName();
            if ($action && !str_contains($action, '@') && $action !== 'Closure') {
                $discoveredClasses[] = $action;
            } elseif ($action && str_contains($action, '@')) {
                [$ctrl] = explode('@', $action);
                $discoveredClasses[] = $ctrl;
            }

            foreach ($route->gatherMiddleware() as $middleware) {
                if (is_string($middleware) && !str_contains($middleware, ':')) {
                    $discoveredClasses[] = $middleware;
                }
            }
        }
    } catch (Throwable $e) {
        fwrite(STDERR, 'Warning during route discovery: '.$e->getMessage().PHP_EOL);
    }
}

// 4.2 从 Composer ClassMap 安全扫描业务命名空间与白名单核心库
$loaders = ClassLoader::getRegisteredLoaders();
$loader = reset($loaders);
$classMap = $loader ? $loader->getClassMap() : [];

$loadedCount = 0;
foreach ($classMap as $class => $file) {
    if (!is_file($file)) {
        continue;
    }

    // 检查黑名单
    foreach ($config['exclude_namespaces'] as $bl) {
        if (str_starts_with($class, $bl)) {
            continue 2;
        }
    }

    // 检查白名单命中
    $shouldLoad = in_array($class, $config['include_classes'], true);
    if (!$shouldLoad) {
        foreach ($config['include_namespaces'] as $ns) {
            if (str_starts_with($class, $ns)) {
                $shouldLoad = true;
                break;
            }
        }
    }

    if ($shouldLoad) {
        try {
            if (class_exists($class, true) || interface_exists($class, true) || trait_exists($class, true)) {
                $loadedCount++;
            }
        } catch (Throwable $e) {
            // 忽略单一类解析异常，保障整体收集进程不受单点故障影响
        }
    }
}

// 4.3 预加载路由提取的控制器与动作类
foreach ($discoveredClasses as $class) {
    try {
        if (class_exists($class, true) || interface_exists($class, true) || trait_exists($class, true)) {
            $loadedCount++;
        }
    } catch (Throwable $e) {
    }
}

echo sprintf('  Successfully scanned and loaded %d classes/interfaces/traits.', $loadedCount), PHP_EOL;

// 5. 过滤与排序 (Filtering & Topological Sorting)
$loads = Collection::empty()
    ->merge(get_declared_classes())
    ->merge(get_declared_interfaces())
    ->merge(get_declared_traits())
    // 剔除初始系统类
    ->diff($initialClasses)
    // 剔除用户显式排除类
    ->diff($config['exclude_classes'])
    // 安全过滤器
    ->filter(function ($class) use ($config) {
        // 排除全局无命名空间别名类
        if (strripos($class, '\\') <= 0) {
            return false;
        }
        // 排除 PhpParser 语法分析器自身类
        if (str_starts_with($class, 'PhpParser\\')) {
            return false;
        }
        // 排除黑名单命名空间
        foreach ($config['exclude_namespaces'] as $bl) {
            if (str_starts_with($class, $bl)) {
                return false;
            }
        }
        return true;
    })
    ->values()
    ->map(function ($class) {
        $reflection = new ReflectionClass($class);
        $file = $reflection->getFileName();
        $relativeFile = $file ? str_replace(base_path().'/', '', $file) : 'unknown';

        return [
            'class'        => $class,
            'file'         => $relativeFile,
            'is_interface' => $reflection->isInterface(),
            'is_trait'     => $reflection->isTrait(),
        ];
    })
    // 严格按 Interface (1) -> Trait (2) -> Class (3) 拓扑排序，确保继承关系绝对安全
    ->sort(function ($a, $b) {
        $getPriority = function ($item) {
            if ($item['is_interface']) {
                return 1;
            } elseif ($item['is_trait']) {
                return 2;
            }
            return 3;
        };
        return $getPriority($a) <=> $getPriority($b);
    })
    ->values();

// 统计
$stats = [
    'interfaces' => $loads->filter(fn ($i) => $i['is_interface'])->count(),
    'traits'     => $loads->filter(fn ($i) => $i['is_trait'])->count(),
    'classes'    => $loads->filter(fn ($i) => !$i['is_interface'] && !$i['is_trait'])->count(),
];

echo sprintf('Collected %d items: %d interfaces, %d traits, %d classes', $loads->count(), $stats['interfaces'], $stats['traits'], $stats['classes']), PHP_EOL;

// 6. 生成 preload.php 文件
$contents = "<?php\n";
$contents .= "/**\n";
$contents .= " * OPcache Preload File (Optimized)\n";
$contents .= ' * Generated at: '.date('Y-m-d H:i:s')."\n";
$contents .= ' * Total items: '.$loads->count()."\n";
$contents .= ' *   - Interfaces: '.$stats['interfaces']."\n";
$contents .= ' *   - Traits: '.$stats['traits']."\n";
$contents .= ' *   - Classes: '.$stats['classes']."\n";
$contents .= " */\n\n";
$contents .= "require_once __DIR__.'/vendor/autoload.php';\n\n";

// 生成预加载语句
foreach ($loads as $item) {
    $class = $item['class'];
    if ($item['is_interface']) {
        $contents .= "interface_exists('{$class}', true);\n";
    } elseif ($item['is_trait']) {
        $contents .= "trait_exists('{$class}', true);\n";
    } else {
        $contents .= "class_exists('{$class}', true);\n";
    }
}

if (file_put_contents($preloadFile, $contents) <= 0) {
    throw new Exception('Unable to write preload file: '.$preloadFile);
}

echo sprintf('Preload file created: %s', $preloadFile), PHP_EOL;
echo sprintf('Total preloaded items: %d', $loads->count()), PHP_EOL;
echo PHP_EOL;
echo 'To use this preload file, add the following to your php.ini:', PHP_EOL;
echo sprintf('  opcache.preload=%s', $preloadFile), PHP_EOL;
