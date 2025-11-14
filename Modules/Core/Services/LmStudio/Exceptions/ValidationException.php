<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\Exceptions;

/**
 * Thrown when request/response validation fails.
 *
 * Covers invalid payloads, schema mismatches, type violations.
 */
final class ValidationException extends LmStudioException
{
}
