<?php

declare(strict_types=1);

namespace App\Http\Controllers\Benchmark;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * 场景 D: 贴合真实生产的强一致性高并发互动/下单写业务
 * - 极致优化: 折叠 5 次网络 RTT 为单事务原生原子操作，大幅压缩锁持有时间与网络延迟
 */
class ItemInteractController extends Controller
{
    protected function action(): Response
    {
        $request = $this->getRequest();
        $id = $request->input('id');
        if (empty($id)) {
            $id = random_int(1, 1000);
        } else {
            $id = intval($id);
        }

        $actionType = (string) $request->input('action', 'interact');
        $ip = (string) ($request->ip() ?: '127.0.0.1');

        $driver = DB::getDriverName();

        // 针对 PostgreSQL 采用极致的单往返 CTE 原子查询
        if ($driver === 'pgsql') {
            $sql = <<<'SQL'
                WITH updated AS (
                    UPDATE benchmark_items
                    SET view_count = view_count + 1, updated_at = NOW()
                    WHERE id = :id AND status = 1
                    RETURNING id, view_count
                ), inserted_log AS (
                    INSERT INTO benchmark_logs (item_id, action, ip, created_at, updated_at)
                    SELECT id, :action, :ip, NOW(), NOW() FROM updated
                    RETURNING id AS log_id
                )
                SELECT (SELECT id FROM updated) AS item_id,
                       (SELECT view_count FROM updated) AS view_count,
                       (SELECT log_id FROM inserted_log) AS log_id;
            SQL;

            $rows = DB::select($sql, [
                'id' => $id,
                'action' => $actionType,
                'ip' => $ip,
            ]);

            if (empty($rows) || empty($rows[0]->item_id)) {
                return $this->asResponse([
                    'code' => 404,
                    'message' => 'Target entity inactive or not found',
                ], 404);
            }

            return $this->asResponse([
                'code' => 200,
                'message' => 'interaction committed',
                'data' => [
                    'item_id' => $rows[0]->item_id,
                    'view_count' => $rows[0]->view_count,
                    'log_id' => $rows[0]->log_id,
                    'action' => $actionType,
                ],
            ]);
        }

        // 针对 SQLite 等其他驱动兼容事务
        $result = DB::transaction(function () use ($id, $actionType, $ip) {
            $affected = DB::table('benchmark_items')
                ->where('id', $id)
                ->where('status', 1)
                ->increment('view_count');

            if ($affected === 0) {
                return null;
            }

            $logId = DB::table('benchmark_logs')->insertGetId([
                'item_id' => $id,
                'action' => $actionType,
                'ip' => $ip,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'item_id' => $id,
                'log_id' => $logId,
                'action' => $actionType,
            ];
        });

        if ($result === null) {
            return $this->asResponse([
                'code' => 404,
                'message' => 'Target entity inactive or not found',
            ], 404);
        }

        return $this->asResponse([
            'code' => 200,
            'message' => 'interaction committed',
            'data' => $result,
        ]);
    }
}
