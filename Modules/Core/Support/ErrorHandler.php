<?php

declare(strict_types=1);

namespace Modules\Core\Support;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Modules\Core\Exceptions\ExternalServiceException;
use Modules\Core\Exceptions\ServiceUnavailableException;
use Modules\Core\Exceptions\TimeoutException;

/**
 * Centralized error handler for external service errors.
 *
 * Provides consistent error handling, logging, and response formatting
 * across all external service integrations.
 */
final class ErrorHandler
{
    /**
     * Handle external service exception and return formatted error response.
     *
     * @param  ExternalServiceException  $exception
     * @param  string  $errorCode
     * @param  array<string, mixed>  $additionalMeta
     * @return JsonResponse
     */
    public static function handleExternalServiceError(
        ExternalServiceException $exception,
        string $errorCode,
        array $additionalMeta = []
    ): JsonResponse {
        $sourceStatus = $exception->getSourceStatus();
        $mappedStatus = self::mapHttpStatus($exception, $sourceStatus);
        $userMessage = self::getUserFriendlyMessage($exception, $mappedStatus);

        $meta = array_merge([
            'source_status' => $sourceStatus,
            'service' => $exception->getService(),
            'error_message' => $exception->getMessage(),
        ], $exception->getContext(), $additionalMeta);

        // Log error with structured context
        self::logError($exception, $errorCode, $meta);

        return ApiResponse::error(
            code: $errorCode,
            message: $userMessage,
            meta: $meta,
            status: $mappedStatus
        );
    }

    /**
     * Handle circuit breaker open state.
     *
     * @param  string  $service
     * @param  int  $retryAfter
     * @return JsonResponse
     */
    public static function handleCircuitBreakerOpen(string $service, int $retryAfter = 60): JsonResponse
    {
        Log::channel('external')->warning('Circuit breaker open', [
            'service' => $service,
            'retry_after' => $retryAfter,
        ]);

        return ApiResponse::error(
            code: "{$service}.circuit_breaker_open",
            message: 'Service is temporarily unavailable. Please try again later.',
            meta: [
                'service' => $service,
                'circuit_state' => 'open',
                'retry_after' => $retryAfter,
            ],
            status: 503
        );
    }

    /**
     * Map external service error to appropriate HTTP status code.
     *
     * @param  ExternalServiceException  $exception
     * @param  int|null  $sourceStatus
     * @return int
     */
    public static function mapHttpStatus(ExternalServiceException $exception, ?int $sourceStatus): int
    {
        // Handle specific exception types first
        if ($exception instanceof TimeoutException) {
            return 504; // Gateway Timeout
        }

        if ($exception instanceof ServiceUnavailableException) {
            return 503; // Service Unavailable
        }

        // Map source HTTP status codes
        if ($sourceStatus !== null) {
            return match (true) {
                $sourceStatus >= 500 && $sourceStatus < 600 => 502, // Bad Gateway (external server error)
                $sourceStatus === 503 => 503, // Service Unavailable (pass through)
                $sourceStatus === 401 => 401, // Unauthorized (pass through)
                $sourceStatus === 403 => 403, // Forbidden (pass through)
                $sourceStatus === 404 => 404, // Not Found (pass through)
                $sourceStatus >= 400 && $sourceStatus < 500 => 400, // Bad Request (client errors)
                default => 502, // Default to Bad Gateway for unknown 5xx
            };
        }

        // Default mapping based on exception
        return 502; // Bad Gateway
    }

    /**
     * Get user-friendly error message based on exception type and status.
     *
     * @param  ExternalServiceException  $exception
     * @param  int  $statusCode
     * @return string
     */
    public static function getUserFriendlyMessage(ExternalServiceException $exception, int $statusCode): string
    {
        // Specific messages for common scenarios
        if ($exception instanceof TimeoutException) {
            return 'The request took too long to complete. Please try again.';
        }

        if ($exception instanceof ServiceUnavailableException) {
            return 'Service is temporarily unavailable. Please try again later.';
        }

        // Generic messages based on HTTP status
        return match ($statusCode) {
            502 => 'Unable to complete operation. The service may be temporarily unavailable.',
            503 => 'Service is temporarily unavailable. Please try again later.',
            504 => 'The request took too long to complete. Please try again.',
            401 => 'Authentication required. Please log in and try again.',
            403 => 'You do not have permission to perform this action.',
            404 => 'The requested resource was not found.',
            400 => 'Invalid request. Please check your input and try again.',
            default => 'An error occurred while processing your request. Please try again.',
        };
    }

    /**
     * Log error with structured context.
     *
     * @param  ExternalServiceException  $exception
     * @param  string  $errorCode
     * @param  array<string, mixed>  $meta
     * @return void
     */
    private static function logError(ExternalServiceException $exception, string $errorCode, array $meta): void
    {
        $logLevel = match (true) {
            $exception instanceof TimeoutException => 'warning',
            $exception instanceof ServiceUnavailableException => 'warning',
            ($meta['source_status'] ?? null) >= 500 => 'error',
            ($meta['source_status'] ?? null) >= 400 => 'warning',
            default => 'error',
        };

        $context = [
            'error_code' => $errorCode,
            'exception_type' => get_class($exception),
            'service' => $exception->getService(),
        ] + $meta;

        Log::channel('external')->{$logLevel}('External service error', $context);
    }
}

