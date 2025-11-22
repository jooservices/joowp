<?php

declare(strict_types=1);

namespace Modules\Core\Services\WordPress\Exceptions;

use GuzzleHttp\Exception\RequestException;
use RuntimeException;
use Throwable;

final class WordPressRequestException extends RuntimeException
{
    private readonly ?int $sourceStatus;

    public function __construct(string $message, ?int $sourceStatus = null, ?Throwable $previous = null)
    {
        parent::__construct($message, $sourceStatus ?? 0, $previous);

        $this->sourceStatus = $sourceStatus;
    }

    public static function fromThrowable(string $method, string $path, Throwable $throwable): self
    {
        return new self(
            sprintf('WordPress API request [%s %s] failed: %s', strtoupper($method), $path, $throwable->getMessage()),
            self::extractStatusCode($throwable),
            $throwable
        );
    }

    public static function invalidJson(string $method, string $path, Throwable $throwable, int $status = 0): self
    {
        return new self(
            sprintf('WordPress API response [%s %s] is not valid JSON: %s', strtoupper($method), $path, $throwable->getMessage()),
            $status > 0 ? $status : null,
            $throwable
        );
    }

    public static function httpError(string $method, string $path, int $statusCode, string $message): self
    {
        return new self(
            sprintf('WordPress API request [%s %s] failed: HTTP %d: %s', strtoupper($method), $path, $statusCode, $message),
            $statusCode
        );
    }

    public function sourceStatus(): ?int
    {
        return $this->sourceStatus;
    }

    private static function extractStatusCode(Throwable $throwable): ?int
    {
        if ($throwable instanceof RequestException && $throwable->hasResponse()) {
            return $throwable->getResponse()?->getStatusCode();
        }

        $code = $throwable->getCode();

        return is_int($code) && $code > 0 ? $code : null;
    }
}
