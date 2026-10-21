<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use HughCube\Laravel\Knight\Http\Middleware\RequestSignatureValidate;

class SignatureValidate extends RequestSignatureValidate
{
    /**
     * @return string[]
     */
    protected function getOptional(): array
    {
        return array_merge(parent::getOptional(), [
            '/api/login/*',
            '/api/logon/*',
        ]);
    }
}
