<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $item_id
 * @property string $action
 * @property string $ip
 * @property Carbon|null $created_at
 */
class BenchmarkLog extends AAAModel
{
    public const UPDATED_AT = null;

    protected $table = 'benchmark_logs';

    protected $fillable = [
        'item_id',
        'action',
        'ip',
    ];

    protected function casts(): array
    {
        return [
            'item_id' => 'integer',
        ];
    }
}
