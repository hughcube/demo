<?php

declare(strict_types=1);

namespace App\Http\Controllers\Benchmark;

use App\Http\Controllers\Controller;
use App\Models\BenchmarkItem;
use App\Models\BenchmarkLog;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class BizActionController extends Controller
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

        $actionType = $request->input('action', 'view');

        $result = DB::transaction(function () use ($id, $actionType, $request) {
            $item = BenchmarkItem::findById($id);
            if (!$item instanceof BenchmarkItem) {
                return null;
            }

            $item->view_count = ($item->view_count ?? 0) + 1;
            $item->save();

            $log = new BenchmarkLog();
            $log->item_id = $item->id;
            $log->action = (string) $actionType;
            $log->ip = (string) ($request->ip() ?: '127.0.0.1');
            $log->save();

            return [
                'id' => $item->id,
                'title' => $item->title,
                'view_count' => $item->view_count,
                'log_id' => $log->id,
            ];
        });

        if ($result === null) {
            return $this->asResponse([
                'code' => 404,
                'message' => 'Record not found for transaction',
            ], 404);
        }

        return $this->asResponse([
            'code' => 200,
            'message' => 'success',
            'data' => $result,
        ]);
    }
}
