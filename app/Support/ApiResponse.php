<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Return a successful JSON response.
     */
    public static function success(
        mixed $data = null,
        string $message = 'Request successful.',
        int $statusCode = 200
    ): JsonResponse {
        $response = [
            'status' => 'success',
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return an error JSON response.
     */
    public static function error(
        string $message = 'Request failed.',
        mixed $errors = null,
        int $statusCode = 400
    ): JsonResponse {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a validation error response.
     */
    public static function validationError(
        array $errors,
        string $message = 'Validation failed.'
    ): JsonResponse {
        return self::error($message, $errors, 422);
    }

    /**
     * Return an unauthorised response.
     */
    public static function unauthorized(
        string $message = 'Unauthorized.'
    ): JsonResponse {
        return self::error($message, null, 401);
    }

    /**
     * Return a forbidden response.
     */
    public static function forbidden(
        string $message = 'Forbidden.'
    ): JsonResponse {
        return self::error($message, null, 403);
    }

    /**
     * Return a not found response.
     */
    public static function notFound(
        string $message = 'Resource not found.'
    ): JsonResponse {
        return self::error($message, null, 404);
    }

    /**
     * Return a server error response.
     */
    public static function serverError(
        string $message = 'Internal server error.'
    ): JsonResponse {
        return self::error($message, null, 500);
    }

    /**
     * Return a no content response.
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
