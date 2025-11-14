<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Services\LmStudio\Exceptions;

use Modules\Core\Services\LmStudio\Exceptions\ConnectionException;
use Modules\Core\Services\LmStudio\Exceptions\FeatureUnavailableException;
use Modules\Core\Services\LmStudio\Exceptions\LmStudioException;
use Modules\Core\Services\LmStudio\Exceptions\StreamingException;
use Modules\Core\Services\LmStudio\Exceptions\UnsupportedVersionException;
use Modules\Core\Services\LmStudio\Exceptions\ValidationException;
use Tests\TestCase;

final class LmStudioExceptionTest extends TestCase
{
    public function test_base_exception_stores_message_and_code(): void
    {
        $exception = new LmStudioException('Test error', 500);

        $this->assertSame('Test error', $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
    }

    public function test_base_exception_stores_context(): void
    {
        $context = ['endpoint' => '/v1/models', 'status' => 404];
        $exception = new LmStudioException('Not found', 404, null, $context);

        $this->assertSame($context, $exception->getContext());
    }

    public function test_base_exception_context_defaults_to_empty_array(): void
    {
        $exception = new LmStudioException('Error');

        $this->assertSame([], $exception->getContext());
    }

    public function test_base_exception_preserves_previous_exception(): void
    {
        $previous = new \RuntimeException('Original error');
        $exception = new LmStudioException('Wrapped error', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_connection_exception_extends_base(): void
    {
        $exception = new ConnectionException('Connection failed');

        $this->assertInstanceOf(LmStudioException::class, $exception);
        $this->assertSame('Connection failed', $exception->getMessage());
    }

    public function test_validation_exception_extends_base(): void
    {
        $exception = new ValidationException('Invalid payload');

        $this->assertInstanceOf(LmStudioException::class, $exception);
        $this->assertSame('Invalid payload', $exception->getMessage());
    }

    public function test_streaming_exception_extends_base(): void
    {
        $exception = new StreamingException('Stream interrupted');

        $this->assertInstanceOf(LmStudioException::class, $exception);
        $this->assertSame('Stream interrupted', $exception->getMessage());
    }

    public function test_feature_unavailable_exception_extends_base(): void
    {
        $exception = new FeatureUnavailableException('Audio not supported');

        $this->assertInstanceOf(LmStudioException::class, $exception);
        $this->assertSame('Audio not supported', $exception->getMessage());
    }

    public function test_unsupported_version_exception_extends_base(): void
    {
        $exception = new UnsupportedVersionException('Version 0.2.17 unsupported');

        $this->assertInstanceOf(LmStudioException::class, $exception);
        $this->assertSame('Version 0.2.17 unsupported', $exception->getMessage());
    }

    public function test_all_exceptions_support_context(): void
    {
        $context = ['key' => 'value'];

        $exceptions = [
            new ConnectionException('msg', 0, null, $context),
            new ValidationException('msg', 0, null, $context),
            new StreamingException('msg', 0, null, $context),
            new FeatureUnavailableException('msg', 0, null, $context),
            new UnsupportedVersionException('msg', 0, null, $context),
        ];

        foreach ($exceptions as $exception) {
            $this->assertSame($context, $exception->getContext());
        }
    }
}
