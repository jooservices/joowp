<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\Exceptions;

use Exception;

/**
 * Base exception for all LM Studio SDK errors.
 *
 * Provides structured error context for downstream handlers.
 */
class LmStudioException extends Exception
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        private readonly array $context = [],
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
