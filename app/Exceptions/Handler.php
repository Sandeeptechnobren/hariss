<?php

namespace App\Exceptions;

use App\Traits\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    use ApiResponse;

    public function render($request, Throwable $e)
    {
        if ($e instanceof ValidationException) {
            return $this->fail('Validation failed', 422, $e->errors());
        }
        if ($e instanceof AuthenticationException) {
            return $this->fail('Unauthenticated', 401);
        }
        if ($e instanceof NotFoundHttpException) {
            return $this->fail('Route not found', 404);
        }
        if ($e instanceof HttpException) {
            return $this->fail($e->getMessage(), $e->getStatusCode());
        }
        if (app()->environment('local')) {
            return $this->fail($e->getMessage(), 500, [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
        return $this->fail('Something went wrong', 500);
    }
}
