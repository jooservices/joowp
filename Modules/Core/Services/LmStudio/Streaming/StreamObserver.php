<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\Streaming;

use Throwable;

interface StreamObserver
{
    /**
     * Receive a raw chunk from the LM Studio streaming endpoint.
     */
    public function onChunk(string $chunk): void;

    /**
     * Invoked once the stream finished successfully.
     */
    public function onCompleted(): void;

    /**
     * Invoked when the stream fails mid-flight.
     */
    public function onError(Throwable $throwable): void;
}
