<?php

declare(strict_types=1);

namespace App\Http\Controllers\DevOps;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class HealthcheckController extends Controller
{
    protected function action(): Response
    {
        return new Response('success');
    }
}
