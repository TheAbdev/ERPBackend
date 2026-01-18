<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Standardized API error response formatter.
 */
class ApiErrorResponse
{
    /**
     * Create a standardized error response.
     *
     * @param  string  $message
     * @param  int  $statusCode
     * @param  array|null  $errors
     * @param  string|null  $errorCode
     * @param  array|null  $metadata
     * @return \Illuminate\Http\JsonResponse
     */
    public static function error(
        string $message,
        int $statusCode = 400,
        ?array $errors = null,
        ?string $errorCode = null,
        ?array $metadata = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode ?? self::getDefaultErrorCode($statusCode),
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        if ($metadata !== null) {
            $response['metadata'] = $metadata;
        }

        // Add request ID if available
        if ($requestId = request()->header('X-Request-ID')) {
            $response['request_id'] = $requestId;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Create validation error response.
     *
     * @param  array  $errors
     * @param  string  $message
     * @return \Illuminate\Http\JsonResponse
     */
    public static function validation(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return self::error($message, 422, $errors, 'VALIDATION_ERROR');
    }

    /**
     * Create not found error response.
     *
     * @param  string  $message
     * @return \Illuminate\Http\JsonResponse
     */
    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return self::error($message, 404, null, 'NOT_FOUND');
    }

    /**
     * Create unauthorized error response.
     *
     * @param  string  $message
     * @return \Illuminate\Http\JsonResponse
     */
    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return self::error($message, 401, null, 'UNAUTHORIZED');
    }

    /**
     * Create forbidden error response.
     *
     * @param  string  $message
     * @return \Illuminate\Http\JsonResponse
     */
    public static function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return self::error($message, 403, null, 'FORBIDDEN');
    }

    /**
     * Create server error response.
     *
     * @param  string  $message
     * @param  array|null  $metadata
     * @return \Illuminate\Http\JsonResponse
     */
    public static function serverError(string $message = 'Internal server error', ?array $metadata = null): JsonResponse
    {
        return self::error($message, 500, null, 'SERVER_ERROR', $metadata);
    }

    /**
     * Get default error code for status code.
     *
     * @param  int  $statusCode
     * @return string
     */
    protected static function getDefaultErrorCode(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            422 => 'VALIDATION_ERROR',
            429 => 'TOO_MANY_REQUESTS',
            500 => 'SERVER_ERROR',
            503 => 'SERVICE_UNAVAILABLE',
            default => 'ERROR',
        };
    }
}

