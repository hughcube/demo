<?php

declare(strict_types=1);

namespace App\Http\Controllers\DevOps;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class VersionController extends Controller
{
    protected function action(): Response
    {
        $file = base_path('version.json');
        $version = [];

        if (is_file($file) && false !== ($content = @file_get_contents($file))) {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                $version = $decoded;
            }
        }

        return new JsonResponse([
            'build_version' => $version['build_version'] ?? env('APP_BUILD_VERSION', 'dev'),
            'build_time' => $version['build_time'] ?? null,
        ]);
    }
}
