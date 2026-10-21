<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\BenchmarkItem;
use App\Models\BenchmarkLog;
use App\Models\User;
use Laravel\Octane\Events\WorkerStarting;

class WarmModelsListener
{
    /**
     * 在 Octane Worker 进程启动时提前预热核心模型元数据与 Traits，
     * 消除运行时逐请求通过反射扫描 Traits、解析 Casts 与属性映射的毫秒级 CPU 损耗。
     */
    public function handle(WorkerStarting $event): void
    {
        BenchmarkItem::bootIfNotBooted();
        BenchmarkLog::bootIfNotBooted();
        User::bootIfNotBooted();

        // 预热实例以固化 casts 与属性表
        (new BenchmarkItem())->getCasts();
        (new BenchmarkLog())->getCasts();
        (new User())->getCasts();
    }
}
