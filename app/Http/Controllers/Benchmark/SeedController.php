<?php

declare(strict_types=1);

namespace App\Http\Controllers\Benchmark;

use App\Http\Controllers\Controller;
use App\Models\BenchmarkItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SeedController extends Controller
{
    protected function action(): Response
    {
        $count = BenchmarkItem::query()->count();
        $target = 1000;

        if ($count < $target) {
            $batch = [];
            $now = Carbon::now();
            $needed = $target - $count;

            for ($i = 1; $i <= $needed; $i++) {
                $idx = $count + $i;
                $batch[] = [
                    'title' => "Benchmark Item #{$idx}",
                    'category' => 'cat_' . ($idx % 10),
                    'content' => "This is standard performance testing content for record #{$idx}.",
                    'view_count' => random_int(0, 100),
                    'extra_data' => json_encode(['level' => $idx % 5, 'tag' => 'perf']),
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($batch) >= 200) {
                    DB::table('benchmark_items')->insert($batch);
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                DB::table('benchmark_items')->insert($batch);
            }

            $count = BenchmarkItem::query()->count();
        }

        return $this->asResponse([
            'code' => 200,
            'message' => 'seed completed',
            'total_items' => $count,
        ]);
    }
}
