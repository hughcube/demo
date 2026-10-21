<?php

declare(strict_types=1);

namespace App\Http\Controllers\Benchmark;

use App\Http\Controllers\Controller;
use App\Models\BenchmarkItem;
use Symfony\Component\HttpFoundation\Response;

class ItemDetailController extends Controller
{
    protected function action(): Response
    {
        $id = $this->getRequest()->query('id');
        if (empty($id)) {
            $id = random_int(1, 1000);
        } else {
            $id = intval($id);
        }

        $useCache = $this->getRequest()->boolean('cache', false);
        $item = $useCache ? BenchmarkItem::findById($id) : BenchmarkItem::find($id);
        if (!$item instanceof BenchmarkItem) {
            return $this->asResponse([
                'code' => 404,
                'message' => 'Record not found',
                'data' => null,
            ], 404);
        }

        return $this->asResponse([
            'code' => 200,
            'message' => 'ok',
            'data' => [
                'id' => $item->id,
                'title' => $item->title,
                'category' => $item->category,
                'view_count' => $item->view_count,
                'extra_data' => $item->extra_data,
                'created_at' => $item->created_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}
