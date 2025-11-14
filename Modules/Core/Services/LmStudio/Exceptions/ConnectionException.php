<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\Exceptions;

/**
 * Thrown when connection to LM Studio fails.
 *
 * Covers network timeouts, refused connections, DNS failures.
 */
final class ConnectionException extends LmStudioException
{
}
