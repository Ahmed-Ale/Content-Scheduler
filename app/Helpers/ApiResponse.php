<?php

namespace App\Helpers;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    public static function success(
        int $status = Response::HTTP_OK,
        string $message = 'Success',
        $data = []
    ): JsonResponse {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data instanceof Jsonable ? json_decode($data->toJson(), true) : $data,
        ], $status);
    }

    public static function error(
        int $status = Response::HTTP_BAD_REQUEST,
        string $message = 'Error',
        array $errors = []
    ): JsonResponse {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    public static function notFound(string $resource = 'Resource'): JsonResponse
    {
        return self::error(
            status: Response::HTTP_NOT_FOUND,
            message: "$resource not found"
        );
    }

    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return self::error(
            status: Response::HTTP_UNAUTHORIZED,
            message: $message
        );
    }

    public static function validationError(array $errors): JsonResponse
    {
        return self::error(
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
            message: 'Validation failed',
            errors: $errors
        );
    }
}
