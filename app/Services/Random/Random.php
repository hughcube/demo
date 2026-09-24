<?php

declare(strict_types=1);

namespace App\Services\Random;

use Illuminate\Support\Str;

class Random
{
    public static function genToken(int $length = 32): string
    {
        return Str::random($length);
    }

    public static function randomString(int $length = 16): string
    {
        return Str::random($length);
    }
}
