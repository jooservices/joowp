<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

use Exception;
use Throwable;

/**
 * Base exception for all external service errors.
 *
 * Provides structured error context for downstream handlers.
 * All service-specific exceptions (WordPress, LM Studio, etc.) should extend this.
 */
abstract class ExternalServiceException extends Exception
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        protected readonly array $context = [],
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get error context for structured logging.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get service name (e.g., 'wordpress', 'lmstudio').
     */
    abstract public function getService(): string;

    /**
     * Get HTTP status code from source, if available.
     */
    public function getSourceStatus(): ?int
    {
        $contextStatus = $this->context['status'] ?? $this->context['source_status'] ?? null;

        if (is_int($contextStatus) && $contextStatus > 0) {
            return $contextStatus;
        }

        // Try to extract from exception code if it's a valid HTTP status
        $code = $this->getCode();

        return is_int($code) && $code >= 400 && $code < 600 ? $code : null;
    }
}

