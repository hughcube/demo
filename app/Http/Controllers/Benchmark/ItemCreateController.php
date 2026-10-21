<?php

declare(strict_types=1);

namespace App\Http\Controllers\Benchmark;

use App\Http\Controllers\Controller;
use App\Models\BenchmarkItem;
use Symfony\Component\HttpFoundation\Response;

class ItemCreateController extends Controller
{
    protected function action(): Response
    {
        $request = $this->getRequest();
        $title = $request->input('title', 'Benchmark Record ' . microtime(true));
        $category = $request->input('category', 'stress_test');
        $content = $request->input('content', 'Simulated payload for benchmark testing');
        $extra = $request->input('extra', ['trace_id' => uniqid('tr_', true)]);

        $item = new BenchmarkItem();
        $item->title = (string) $title;
        $item->category = (string) $category;
        $item->content = (string) $content;
        $item->view_count = 0;
        $item->extra_data = is_array($extra) ? $extra : ['raw' => $extra];
        $item->status = 1;
        $item->save();

        return $this->asResponse([
            'code' => 200,
            'message' => 'created',
            'data' => [
                'id' => $item->id,
                'title' => $item->title,
                'category' => $item->category,
                'created_at' => $item->created_at?->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }
}
