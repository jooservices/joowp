<?php

declare(strict_types=1);

namespace Modules\Core\Services\WordPress\Exceptions;

use RuntimeException;
use Throwable;

final class WordPressRequestException extends RuntimeException
{
    public static function fromThrowable(string $method, string $path, Throwable $throwable): self
    {
        return new self(
            sprintf('WordPress API request [%s %s] failed: %s', strtoupper($method), $path, $throwable->getMessage()),
            previous: $throwable
        );
    }

    public static function invalidJson(string $method, string $path, Throwable $throwable): self
    {
        return new self(
            sprintf('WordPress API response [%s %s] is not valid JSON: %s', strtoupper($method), $path, $throwable->getMessage()),
            previous: $throwable
        );
    }
}

