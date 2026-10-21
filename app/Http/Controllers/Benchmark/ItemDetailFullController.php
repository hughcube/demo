<?php

declare(strict_types=1);

namespace App\Http\Controllers\Benchmark;

use App\Http\Controllers\Controller;
use App\Models\BenchmarkItem;
use App\Models\BenchmarkLog;
use Symfony\Component\HttpFoundation\Response;

/**
 * 场景 C: 贴合真实生产的单实体详情与关联流水复合读取业务
 * - 涵盖: 主键快速命中、子表关联多条检索 (item_id 索引)、聚合组装
 */
class ItemDetailFullController extends Controller
{
    protected function action(): Response
    {
        $id = $this->getRequest()->query('id');
        if (empty($id)) {
            $id = random_int(1, 1000);
        } else {
            $id = intval($id);
        }

        /** @var null|BenchmarkItem $item */
        $item = BenchmarkItem::find($id);
        if (!$item instanceof BenchmarkItem || $item->status !== 1) {
            return $this->asResponse([
                'code' => 404,
                'message' => 'Record not found or inactive',
            ], 404);
        }

        // 真实业务：查出该实体最近的 3 条交互流水
        $recentLogs = BenchmarkLog::query()
            ->where('item_id', $item->id)
            ->orderByDesc('id')
            ->limit(3)
            ->get(['id', 'action', 'ip', 'created_at']);

        return $this->asResponse([
            'code' => 200,
            'message' => 'ok',
            'data' => [
                'id' => $item->id,
                'title' => $item->title,
                'category' => $item->category,
                'content' => $item->content,
                'view_count' => $item->view_count,
                'extra_data' => $item->extra_data,
                'recent_activities' => $recentLogs->map(fn($log) => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'at' => $log->created_at?->format('Y-m-d H:i:s'),
                ]),
                'created_at' => $item->created_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}
