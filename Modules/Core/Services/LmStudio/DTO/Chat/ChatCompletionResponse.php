<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\DTO\Chat;

use Modules\Core\Services\LmStudio\DTO\DataTransferObject;
use Modules\Core\Services\LmStudio\DTO\Shared\Usage;

/**
 * @phpstan-type RawChoice array<string, mixed>
 */
final class ChatCompletionResponse extends DataTransferObject
{
    /**
     * @param  array<int, ChatChoice>  $choices
     */
    public function __construct(
        public readonly string $id,
        public readonly string $object,
        public readonly int $created,
        public readonly string $model,
        public readonly array $choices,
        public readonly ?Usage $usage = null,
        public readonly array $raw = [],
    ) {
        self::assertArrayOf(ChatChoice::class, $choices, 'choices');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $choices = array_map(
            static fn (array $choice): ChatChoice => ChatChoice::fromArray($choice),
            $payload['choices'] ?? []
        );

        return new self(
            id: $payload['id'],
            object: $payload['object'] ?? 'chat.completion',
            created: $payload['created'] ?? time(),
            model: $payload['model'] ?? '',
            choices: $choices,
            usage: isset($payload['usage']) ? Usage::fromArray($payload['usage']) : null,
            raw: $payload,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'object' => $this->object,
            'created' => $this->created,
            'model' => $this->model,
            'choices' => array_map(
                static fn (ChatChoice $choice): array => $choice->toArray(),
                $this->choices
            ),
            'usage' => $this->usage?->toArray(),
            'raw' => $this->raw,
        ];
    }
}
