<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\Exceptions;

/**
 * Thrown when requested feature is unavailable.
 *
 * Covers audio/image endpoints when LM Studio version lacks support
 * or feature flags are disabled.
 */
final class FeatureUnavailableException extends LmStudioException
{
}
