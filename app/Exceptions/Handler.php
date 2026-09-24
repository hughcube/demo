<?php

namespace App\Exceptions;

use HughCube\Laravel\Knight\Exceptions\Contracts\DataExceptionInterface;
use HughCube\Laravel\Knight\Exceptions\Contracts\ResponseExceptionInterface;
use HughCube\Laravel\Knight\Exceptions\DebugHandler as ExceptionHandler;
use HughCube\Laravel\Knight\Exceptions\UserException;
use HughCube\Laravel\Knight\Exceptions\ValidatePinCodeException;
use HughCube\Laravel\Knight\Exceptions\ValidateSignatureException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * @var string[]
     */
    protected $dontReport = [
        HttpException::class,
        UserException::class,
        DataExceptionInterface::class,
        ResponseExceptionInterface::class,
        ValidateSignatureException::class,
        ValidatePinCodeException::class,
        AuthenticationException::class,
        ValidationException::class,
    ];


    protected function context(): array
    {
        $context = parent::context();

        try {
            $context['userId'] = Auth::id();
            $context['uri'] = request()->getUri();
            $context['headers'] = request()->headers->all();
            $context['body'] = request()->getContent();
        } catch (Throwable) {
        }

        return $context;
    }

    protected function convertExceptionToResults(Throwable $e): ?array
    {
        return null;
    }

    protected function convertExceptionToDebugArray(Throwable $e): array
    {
        $array = [
            'code'        => $e->getCode(),
            'exception'   => get_class($e),
            'message'     => $e->getMessage(),
            'file'        => $e->getFile(),
            'line'        => $e->getLine(),
            'stack-trace' => explode("\n", $e->getTraceAsString()),
        ];

        if ($e instanceof ValidationException) {
            $array['errors'] = $e->errors();
        }

        if (($prev = $e->getPrevious()) !== null) {
            $array['previous'] = $this->convertExceptionToDebugArray($prev);
        }

        return $array;
    }
}
