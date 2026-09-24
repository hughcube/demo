<?php

declare(strict_types=1);

namespace App\Http\Controllers\DevOps;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpFoundation\Response;

class QueueDepthController extends Controller
{
    /**
     * @return array<string, array<string>>
     */
    protected function rules(): array
    {
        return [
            'connection' => ['nullable', 'string'],
            'queues' => ['nullable', 'string'],
        ];
    }

    protected function action(): Response
    {
        $connection = $this->p('connection') ?: config('queue.default');
        $queues = array_filter(array_map('trim', explode(',', strval($this->p('queues') ?: 'default'))));

        $sizes = [];
        foreach ($queues as $queue) {
            $sizes[$queue] = Queue::connection($connection)->size($queue);
        }

        $response = $this->asSuccess([
            'value' => array_sum($sizes),
            'queues' => $sizes,
        ]);

        Log::info('QueueDepth', [
            'ip' => $this->getRequest()->ip(),
            'ua' => $this->getRequest()->userAgent(),
            'connection' => $connection,
            'queues' => $this->p('queues'),
            'status' => $response->getStatusCode(),
        ]);

        return $response;
    }
}
