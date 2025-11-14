<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\DTO\Completion;

use Modules\Core\Services\LmStudio\DTO\DataTransferObject;

final class CompletionChoice extends DataTransferObject
{
    public function __construct(
        public readonly int $index,
        public readonly string $text,
        public readonly ?string $finishReason = null,
        public readonly array $logprobs = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            index: $payload['index'] ?? 0,
            text: (string) ($payload['text'] ?? ''),
            finishReason: $payload['finish_reason'] ?? null,
            logprobs: $payload['logprobs'] ?? [],
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'index' => $this->index,
            'text' => $this->text,
            'finish_reason' => $this->finishReason,
            'logprobs' => $this->logprobs,
        ], static fn ($value) => $value !== null);
    }
}
