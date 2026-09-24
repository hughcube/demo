<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/7/26
 * Time: 20:02
 */

namespace App\Http\Api\Middleware;

use HughCube\Laravel\Knight\Http\Middleware\RequestSignatureValidate;

class SignatureValidate extends RequestSignatureValidate
{
    protected function getOptional(): array
    {
        return array_merge(parent::getOptional(), [
            '/api/login/*',
            '/api/logon/*',
        ]);
    }
}
