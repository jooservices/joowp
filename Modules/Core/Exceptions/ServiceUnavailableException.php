<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

use Throwable;

/**
 * Thrown when external service returns 503 Service Unavailable.
 *
 * Indicates the service is temporarily unavailable.
 */
final class ServiceUnavailableException extends ExternalServiceException
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
        parent::__construct($message, 503, $previous, $context);
    }

    public function getService(): string
    {
        $context = $this->getContext();

        return $context['service'] ?? 'external';
    }
}

