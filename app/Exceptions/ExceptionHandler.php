<?php

namespace App\Exceptions;

use App\Helpers\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class ExceptionHandler
{
    public function render(Throwable $e, Request $request): ?JsonResponse
    {
        if ($request->expectsJson()) {
            return $this->handleApiException($e);
        }

        return null;
    }

    protected function handleApiException(Throwable $e): JsonResponse
    {
        $statusCode = method_exists($e, 'getStatusCode')
            ? $e->getStatusCode()
            : 500;

        return match (true) {
            $e instanceof ValidationException =>
            ApiResponse::validationError($e->errors()),

            $e instanceof AuthenticationException =>
            ApiResponse::unauthorized(),

            $e instanceof ModelNotFoundException =>
            ApiResponse::notFound(class_basename($e->getModel())),

            $e instanceof HttpException =>
            ApiResponse::error($e->getStatusCode(), $e->getMessage()),

            default => ApiResponse::error(
                $statusCode,
                config('app.debug') ? $e->getMessage() : 'Server Error',
                config('app.debug') ? $this->getDebugData($e) : []
            )
        };
    }

    protected function getDebugData(Throwable $e): array
    {
        return [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTrace()
        ];
    }
}