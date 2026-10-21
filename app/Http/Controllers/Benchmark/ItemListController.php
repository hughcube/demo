<?php

declare(strict_types=1);

namespace App\Http\Controllers\Benchmark;

use App\Http\Controllers\Controller;
use App\Models\BenchmarkItem;
use Symfony\Component\HttpFoundation\Response;

/**
 * 场景 B: 贴合真实生产的高频列表查询与分页过滤业务
 * - 涵盖: 复合索引查询 (category + status)、排序、字段投影、分页切片
 */
class ItemListController extends Controller
{
    protected function action(): Response
    {
        $request = $this->getRequest();
        $category = $request->query('category');
        if (empty($category)) {
            $category = 'cat_' . random_int(0, 9);
        } else {
            $category = (string) $category;
        }

        $page = max(1, intval($request->query('page', 1)));
        $pageSize = min(50, max(1, intval($request->query('page_size', 10))));

        $query = BenchmarkItem::query()
            ->where('category', $category)
            ->where('status', 1)
            ->orderByDesc('id')
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize + 1);

        $rows = $query->get(['id', 'title', 'category', 'view_count', 'extra_data', 'created_at']);

        $hasMore = $rows->count() > $pageSize;
        $items = $rows->take($pageSize)->map(function (BenchmarkItem $item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'category' => $item->category,
                'view_count' => $item->view_count,
                'extra' => $item->extra_data,
                'published_at' => $item->created_at?->format('Y-m-d H:i:s'),
            ];
        })->values();

        return $this->asResponse([
            'code' => 200,
            'message' => 'ok',
            'data' => [
                'list' => $items,
                'page' => $page,
                'page_size' => $pageSize,
                'has_more' => $hasMore,
                'filter' => ['category' => $category],
            ],
        ]);
    }
}
