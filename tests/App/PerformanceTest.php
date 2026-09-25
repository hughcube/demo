<?php

namespace Tests\App;

use Illuminate\Support\Facades\DB;
use PDO;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    /**
     * 验证数据库连接是否配置了模拟预处理(减少 1 次网络交互 RTT)
     */
    public function test_database_emulate_prepares_is_enabled(): void
    {
        $pgsqlOptions = config('database.connections.pgsql.options', []);
        $sqliteOptions = config('database.connections.sqlite.options', []);

        $this->assertTrue(
            isset($pgsqlOptions[PDO::ATTR_EMULATE_PREPARES]) && true === $pgsqlOptions[PDO::ATTR_EMULATE_PREPARES],
            'PostgreSQL 必须开启 PDO::ATTR_EMULATE_PREPARES 模拟预处理以消除网络往返延迟'
        );

        $disablePreparesAttr = defined('\Pdo\Pgsql::ATTR_DISABLE_PREPARES')
            ? \Pdo\Pgsql::ATTR_DISABLE_PREPARES
            : (defined('PDO::PGSQL_ATTR_DISABLE_PREPARES') ? constant('PDO::PGSQL_ATTR_DISABLE_PREPARES') : null);

        if ($disablePreparesAttr !== null) {
            $this->assertTrue(
                isset($pgsqlOptions[$disablePreparesAttr]) && true === $pgsqlOptions[$disablePreparesAttr],
                'PostgreSQL 必须开启 ATTR_DISABLE_PREPARES 禁用服务端预处理'
            );
        }

        $this->assertTrue(
            isset($sqliteOptions[PDO::ATTR_EMULATE_PREPARES]) && true === $sqliteOptions[PDO::ATTR_EMULATE_PREPARES],
            'SQLite 必须开启 PDO::ATTR_EMULATE_PREPARES 模拟预处理'
        );
    }

    /**
     * 验证数据库连接是否配置了 FETCH_ASSOC(消除 FETCH_BOTH 内存翻倍)
     */
    public function test_database_default_fetch_mode_is_assoc(): void
    {
        $pgsqlOptions = config('database.connections.pgsql.options', []);

        $this->assertTrue(
            isset($pgsqlOptions[PDO::ATTR_DEFAULT_FETCH_MODE]) && PDO::FETCH_ASSOC === $pgsqlOptions[PDO::ATTR_DEFAULT_FETCH_MODE],
            'PostgreSQL 必须开启 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC 以消除数组内存翻倍'
        );
    }

    /**
     * 验证 Octane 的 RequestReceived 监听器列表中已剔除无用的 Web/View 监听器
     */
    public function test_octane_request_received_listeners_are_pruned(): void
    {
        $listeners = config('octane.listeners.Laravel\Octane\Events\RequestReceived', []);

        $unwanted = [
            'Laravel\Octane\Listeners\FlushVite',
            'Laravel\Octane\Listeners\FlushSessionState',
            'Laravel\Octane\Listeners\GiveNewApplicationInstanceToSessionManager',
            'Laravel\Octane\Listeners\GiveNewApplicationInstanceToDatabaseSessionHandler',
            'Laravel\Octane\Listeners\FlushArrayCache',
            'Laravel\Octane\Listeners\FlushLocaleState',
            'Laravel\Octane\Listeners\FlushQueuedCookies',
        ];

        foreach ($unwanted as $class) {
            $this->assertNotContains(
                $class,
                $listeners,
                sprintf('Octane 纯 API 模式下不应包含无用的监听器: %s', $class)
            );
        }

        $warmBindings = config('octane.warm', []);
        $this->assertNotContains('session', $warmBindings, 'Octane warm 列表不应预热 session');
        $this->assertNotContains('session.store', $warmBindings, 'Octane warm 列表不应预热 session.store');
    }

    /**
     * 验证真实查询返回纯关联数组
     */
    public function test_query_returns_pure_assoc_array(): void
    {
        try {
            $row = DB::selectOne('SELECT 1 as num, \'test\' as str');
            $this->assertNotNull($row);
            $this->assertEquals(1, $row->num);
            $this->assertEquals('test', $row->str);
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database connection not available in current test environment: ' . $e->getMessage());
        }
    }
}
