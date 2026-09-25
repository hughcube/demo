<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\Response;

class IndexController extends Controller
{
    protected function action(): Response
    {
        return $this->asResponse([
            'name' => config('app.name'),
            'status' => 'up',
        ]);
    }
}
