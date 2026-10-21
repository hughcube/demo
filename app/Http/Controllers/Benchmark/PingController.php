<?php

declare(strict_types=1);

namespace App\Http\Controllers\Benchmark;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class PingController extends Controller
{
    protected function action(): Response
    {
        $timestamp = $this->getRequest()->query('t', (string) microtime(true));
        $echo = $this->getRequest()->query('echo', 'pong');
        $calc = hash('sha256', $timestamp . $echo);

        return $this->asResponse([
            'message' => 'pong',
            'echo' => $echo,
            'timestamp' => $timestamp,
            'token' => substr($calc, 0, 16),
            'server_time' => date('Y-m-d H:i:s'),
        ]);
    }
}
