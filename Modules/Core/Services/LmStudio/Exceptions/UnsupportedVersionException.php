<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\Exceptions;

/**
 * Thrown when LM Studio version is below minimum supported.
 *
 * SDK requires version >= 0.2.18 for OpenAI-compatible endpoints.
 */
final class UnsupportedVersionException extends LmStudioException
{
}
