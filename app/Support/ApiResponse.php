<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Return a success JSON response.
     */
    public static function success(
        mixed $data = null,
        string $message = null,
        int $statusCode = 200
    ): JsonResponse {
        $response = [
            'status' => 'success',
            'message' => $message ?? __('messages.success'),
            'data' => $data,
            'errors' => null,
        ];

        return response()->json($response, $statusCode);
    }

    /**
     * Return an error JSON response.
     */
    public static function error(
        string $message = null,
        mixed $errors = null,
        int $statusCode = 400
    ): JsonResponse {
        $response = [
            'status' => 'error',
            'message' => $message ?? __('messages.errors.validation_failed'),
            'data' => null,
            'errors' => $errors,
        ];

        return response()->json($response, $statusCode);
    }

    /**
     * Return a validation error response.
     */
    public static function validationError(
        string $message = null,
        mixed $errors = null
    ): JsonResponse {
        return response()->json([
            'status' => 'error',
            'message' => $message ?? __('messages.errors.validation_failed'),
            'data' => null,
            'errors' => $errors,
        ], 422);
    }

    /**
     * Return an unauthorized response.
     */
    public static function unauthorized(
        string $message = null,
        mixed $errors = null
    ): JsonResponse {
        return response()->json([
            'status' => 'error',
            'message' => $message ?? __('messages.errors.unauthorized'),
            'data' => null,
            'errors' => $errors,
        ], 401);
    }

    /**
     * Return a forbidden response.
     */
    public static function forbidden(
        string $message = null,
        mixed $errors = null
    ): JsonResponse {
        return response()->json([
            'status' => 'error',
            'message' => $message ?? __('messages.errors.forbidden'),
            'data' => null,
            'errors' => $errors,
        ], 403);
    }

    /**
     * Return a not found response.
     */
    public static function notFound(
        string $message = null,
        mixed $errors = null
    ): JsonResponse {
        return response()->json([
            'status' => 'error',
            'message' => $message ?? __('messages.errors.not_found'),
            'data' => null,
            'errors' => $errors,
        ], 404);
    }

    /**
     * Return a server error response.
     */
    public static function serverError(
        string $message = null,
        mixed $errors = null
    ): JsonResponse {
        return response()->json([
            'status' => 'error',
            'message' => $message ?? __('messages.errors.server_error'),
            'data' => null,
            'errors' => $errors,
        ], 500);
    }

    /**
     * Return a no content response.
     */
    public static function noContent(): JsonResponse {
        return response()->json([
            'status' => 'success',
            'message' => __('messages.no_content'),
            'data' => null,
            'errors' => null,
        ], 204);
    }
}
