<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

use Throwable;

/**
 * Thrown when external service request times out.
 *
 * Covers connection timeouts and request timeouts.
 */
final class TimeoutException extends ExternalServiceException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message = '',
        string $service = 'external',
        ?Throwable $previous = null,
        array $context = [],
    ) {
        $context['service'] = $service;
        $context['timeout_type'] = $context['timeout_type'] ?? 'request'; // 'connection' or 'request'
        parent::__construct($message, 504, $previous, $context);
    }

    public function getService(): string
    {
        $context = $this->getContext();

        return $context['service'] ?? 'external';
    }

    /**
     * Get timeout type: 'connection' or 'request'.
     */
    public function getTimeoutType(): string
    {
        $context = $this->getContext();

        return $context['timeout_type'] ?? 'request';
    }
}

