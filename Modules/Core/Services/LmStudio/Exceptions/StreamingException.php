<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\Exceptions;

/**
 * Thrown when SSE streaming fails or downgrades.
 *
 * Covers stream interruption, reconnect failures, chunk parsing errors.
 */
final class StreamingException extends LmStudioException
{
}
