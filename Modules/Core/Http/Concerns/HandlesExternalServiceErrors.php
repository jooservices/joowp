<?php

declare(strict_types=1);

namespace Modules\Core\Http\Concerns;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Modules\Core\Exceptions\ExternalServiceException;
use Modules\Core\Exceptions\ServiceUnavailableException;
use Modules\Core\Support\ErrorHandler;
use Modules\Core\Services\WordPress\Exceptions\WordPressRequestException;

/**
 * Trait for controllers to handle external service errors consistently.
 *
 * Provides convenient methods for handling WordPress, LM Studio, and other
 * external service exceptions with proper logging and user-friendly responses.
 */
trait HandlesExternalServiceErrors
{
    /**
     * Handle external service error and return formatted response.
     *
     * @param  ExternalServiceException|WordPressRequestException  $exception
     * @param  string  $errorCode
     * @param  array<string, mixed>  $additionalMeta
     * @return JsonResponse
     */
    protected function handleServiceError(
        ExternalServiceException|WordPressRequestException $exception,
        string $errorCode,
        array $additionalMeta = []
    ): JsonResponse {
        // Convert WordPressRequestException to ExternalServiceException-compatible
        if ($exception instanceof WordPressRequestException) {
            return $this->handleWordPressException($exception, $errorCode, $additionalMeta);
        }

        // Use ErrorHandler for ExternalServiceException
        return ErrorHandler::handleExternalServiceError($exception, $errorCode, $additionalMeta);
    }

    /**
     * Handle circuit breaker open state.
     *
     * @param  string  $service
     * @param  int  $retryAfter
     * @return JsonResponse
     */
    protected function handleCircuitBreakerOpen(string $service, int $retryAfter = 60): JsonResponse
    {
        return ErrorHandler::handleCircuitBreakerOpen($service, $retryAfter);
    }

    /**
     * Handle WordPressRequestException with proper error response.
     *
     * @param  WordPressRequestException  $exception
     * @param  string  $errorCode
     * @param  array<string, mixed>  $additionalMeta
     * @return JsonResponse
     */
    private function handleWordPressException(
        WordPressRequestException $exception,
        string $errorCode,
        array $additionalMeta = []
    ): JsonResponse {
        $sourceStatus = $exception->sourceStatus();
        
        // Create a temporary ExternalServiceException for mapping
        $tempException = new \Modules\Core\Exceptions\ServiceUnavailableException(
            $exception->getMessage(),
            'wordpress',
            $exception,
            array_merge(['source_status' => $sourceStatus], $additionalMeta)
        );
        
        $mappedStatus = ErrorHandler::mapHttpStatus($tempException, $sourceStatus);
        $userMessage = ErrorHandler::getUserFriendlyMessage($tempException, $mappedStatus);

        $meta = array_merge([
            'source_status' => $sourceStatus,
            'service' => 'wordpress',
            'error_message' => $exception->getMessage(),
        ], $additionalMeta);

        // Log error
        $logLevel = match (true) {
            $sourceStatus >= 500 => 'error',
            $sourceStatus >= 400 => 'warning',
            default => 'error',
        };

        Log::channel('external')->{$logLevel}('WordPress API error', [
            'error_code' => $errorCode,
            'exception_type' => get_class($exception),
            'service' => 'wordpress',
        ] + $meta);

        return ApiResponse::error(
            code: $errorCode,
            message: $userMessage,
            meta: $meta,
            status: $mappedStatus
        );
    }
}

