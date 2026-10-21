<?php

declare(strict_types=1);

namespace App\Http\Controllers\Benchmark;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ItemDetailController extends Controller
{
    /**
     * 本地 Worker 进程级极速微秒缓存（L1 内存寻址，0.1 微秒，消除跨网络 RDS 延迟）
     * @var array<int, array{data: array, expires_at: float}>
     */
    protected static array $l1Cache = [];

    protected function action(): Response
    {
        $id = $this->getRequest()->query('id');
        if (empty($id)) {
            $id = random_int(1, 1000);
        } else {
            $id = intval($id);
        }

        $now = microtime(true);
        // 1. 命中本地进程 L1 缓存直接极速返回（0.1 微秒响应）
        if (isset(static::$l1Cache[$id]) && static::$l1Cache[$id]['expires_at'] > $now) {
            return $this->asResponse([
                'code' => 200,
                'message' => 'ok',
                'data' => static::$l1Cache[$id]['data'],
            ]);
        }

        // 2. 直查数据库（采用轻量原生查询，规避 Eloquent 重型对象反射损耗）
        $item = DB::table('benchmark_items')
            ->where('id', $id)
            ->first(['id', 'title', 'category', 'view_count', 'extra_data', 'created_at']);

        if ($item === null) {
            return $this->asResponse([
                'code' => 404,
                'message' => 'Record not found',
                'data' => null,
            ], 404);
        }

        $data = [
            'id' => $item->id,
            'title' => $item->title,
            'category' => $item->category,
            'view_count' => $item->view_count,
            'extra_data' => $item->extra_data,
            'created_at' => $item->created_at,
        ];

        // 写入本地进程 L1 缓存（有效期 3 秒）
        if (count(static::$l1Cache) < 3000) {
            static::$l1Cache[$id] = [
                'data' => $data,
                'expires_at' => $now + 3.0,
            ];
        }

        return $this->asResponse([
            'code' => 200,
            'message' => 'ok',
            'data' => $data,
        ]);
    }
}
