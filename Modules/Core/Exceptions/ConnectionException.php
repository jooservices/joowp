<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

use Throwable;

/**
 * Thrown when connection to external service fails.
 *
 * Covers network timeouts, refused connections, DNS failures.
 */
final class ConnectionException extends ExternalServiceException
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
        parent::__construct($message, 0, $previous, $context);
    }

    public function getService(): string
    {
        $context = $this->getContext();

        return $context['service'] ?? 'external';
    }
}

