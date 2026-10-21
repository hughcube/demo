<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string $category
 * @property string $content
 * @property int $view_count
 * @property array|null $extra_data
 * @property int $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class BenchmarkItem extends AAAModel
{
    protected $table = 'benchmark_items';

    protected $fillable = [
        'title',
        'category',
        'content',
        'view_count',
        'extra_data',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'view_count' => 'integer',
            'status' => 'integer',
            'extra_data' => 'array',
        ];
    }
}
