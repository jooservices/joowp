<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\DTO\Chat;

use Modules\Core\Services\LmStudio\DTO\DataTransferObject;

final class ChatChoice extends DataTransferObject
{
    public function __construct(
        public readonly int $index,
        public readonly ?ChatMessage $message = null,
        public readonly ?array $delta = null,
        public readonly ?string $finishReason = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            index: $payload['index'],
            message: isset($payload['message']) ? ChatMessage::fromArray($payload['message']) : null,
            delta: $payload['delta'] ?? null,
            finishReason: $payload['finish_reason'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'index' => $this->index,
            'message' => $this->message?->toArray(),
            'delta' => $this->delta,
            'finish_reason' => $this->finishReason,
        ], static fn ($value) => $value !== null);
    }
}
