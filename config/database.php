<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for database operations. This is
    | the connection which will be utilized unless another connection
    | is explicitly specified when you execute a query / statement.
    |
    */

    'default' => env('DB_CONNECTION', 'sqlite'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Below are all of the database connections defined for your application.
    | An example configuration is provided for each database system which
    | is supported by Laravel. You're free to add / remove connections.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_MYSQL_URL'),
            // 主从读写分离保护：
            // 一旦在当前请求中执行过写操作，强制后续所有读操作也走主库，
            // 既最大化从库分流高并发读的吞吐优势，又杜绝主从毫秒级复制延迟导致的“刚写入却查不到”脏读问题。
            'sticky' => true,
            'host' => env('DB_MYSQL_HOST', '127.0.0.1'),
            'port' => env('DB_MYSQL_PORT', '3306'),
            'database' => env('DB_MYSQL_DATABASE', 'laravel'),
            'username' => env('DB_MYSQL_USERNAME', 'root'),
            'password' => env('DB_MYSQL_PASSWORD', ''),
            'unix_socket' => env('DB_MYSQL_SOCKET', ''),
            'charset' => env('DB_MYSQL_CHARSET', 'utf8mb4'),
            'collation' => env('DB_MYSQL_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => [
                // 复用底层的 TCP 连接池，避免每个请求重新建立连接的高额网络握手与身份认证延迟
                PDO::ATTR_PERSISTENT => boolval(env('DB_MYSQL_PERSISTENT', false)),

                // 本地模拟预处理：默认 false 会导致每次查询先发 PREPARE 再发 EXECUTE(2 次网络 RTT 往返)；
                // 开启后由客户端本地安全绑定参数单次发包执行，网络往返直接减半(1 次 RTT)，在高并发与跨机房场景收益显著
                PDO::ATTR_EMULATE_PREPARES => true,

                // 快速超时失败(默认 3 秒)，防止数据库网络抖动或负载过高时将所有常驻 Worker 进程堵死在连接阶段
                PDO::ATTR_TIMEOUT => intval(env('DB_MYSQL_CONNECT_TIMEOUT', 3)),
            ],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_PGSQL_URL'),
            'host' => env('DB_PGSQL_HOST', '127.0.0.1'),
            'port' => env('DB_PGSQL_PORT', '5432'),
            'database' => env('DB_PGSQL_DATABASE', 'laravel'),
            'username' => env('DB_PGSQL_USERNAME', 'root'),
            'password' => env('DB_PGSQL_PASSWORD', ''),
            'charset' => env('DB_PGSQL_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
            'options' => [
                // 跨请求复用底层数据库连接
                PDO::ATTR_PERSISTENT => boolval(env('DB_PGSQL_PERSISTENT', false)),

                // 本地模拟预处理：减少向数据库服务端发送预处理的网络往返(RTT 减半)
                PDO::ATTR_EMULATE_PREPARES => true,

                // 快速超时失败(默认 3 秒)，防止数据库连接卡死阻塞常驻进程
                PDO::ATTR_TIMEOUT => intval(env('DB_PGSQL_CONNECT_TIMEOUT', 3)),
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_') . '_database_'),
            // 在 Octane/常驻内存环境下复用底层的 TCP 长连接，减少网络握手开销
            'persistent' => env('REDIS_PERSISTENT', false),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
            // 设定连接与读取超时(默认 3 秒)，防止在 Redis 慢查询、网络抖动或网络分区时导致常驻 Worker 进程被永久挂起
            'read_timeout' => intval(env('REDIS_READ_TIMEOUT', 3)),
            'timeout' => intval(env('REDIS_TIMEOUT', 3)),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
            'read_timeout' => intval(env('REDIS_READ_TIMEOUT', 3)),
            'timeout' => intval(env('REDIS_TIMEOUT', 3)),
        ],

    ],

];
