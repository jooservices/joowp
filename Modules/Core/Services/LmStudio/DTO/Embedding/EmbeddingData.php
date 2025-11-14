<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\DTO\Embedding;

use Modules\Core\Services\LmStudio\DTO\DataTransferObject;

final class EmbeddingData extends DataTransferObject
{
    /**
     * @param  array<int, float>  $embedding
     */
    public function __construct(
        public readonly int $index,
        public readonly array $embedding,
        public readonly string $object = 'embedding',
    ) {
        foreach ($embedding as $dimension) {
            if (! is_float($dimension) && ! is_int($dimension)) {
                throw new \InvalidArgumentException('Embedding vector must contain numeric values.');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            index: $payload['index'] ?? 0,
            embedding: $payload['embedding'] ?? [],
            object: $payload['object'] ?? 'embedding',
        );
    }

    public function toArray(): array
    {
        return [
            'index' => $this->index,
            'embedding' => $this->embedding,
            'object' => $this->object,
        ];
    }
}
