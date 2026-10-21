<?php

declare(strict_types=1);

namespace App\Http\Controllers\Benchmark;

use App\Http\Controllers\Controller;
use App\Models\BenchmarkItem;
use App\Models\BenchmarkLog;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * 场景 D: 贴合真实生产的强一致性事务互动/下单写业务
 * - 涵盖: 事务边界 (DB::transaction)、主表行状态检查、计数递增、流水生成、双写落盘
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

        $result = DB::transaction(function () use ($id, $actionType, $ip) {
            /** @var null|BenchmarkItem $item */
            $item = BenchmarkItem::find($id);
            if (!$item instanceof BenchmarkItem || $item->status !== 1) {
                return null;
            }

            // 真实业务更新与状态累加
            $item->view_count = ($item->view_count ?? 0) + 1;
            $item->save();

            // 真实关联流水写入
            $log = new BenchmarkLog();
            $log->item_id = $item->id;
            $log->action = $actionType;
            $log->ip = $ip;
            $log->save();

            return [
                'item_id' => $item->id,
                'view_count' => $item->view_count,
                'log_id' => $log->id,
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
