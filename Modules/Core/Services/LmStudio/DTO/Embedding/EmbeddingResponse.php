<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\DTO\Embedding;

use Modules\Core\Services\LmStudio\DTO\DataTransferObject;
use Modules\Core\Services\LmStudio\DTO\Shared\Usage;

/**
 * @phpstan-type EmbeddingRaw array<string, mixed>
 */
final class EmbeddingResponse extends DataTransferObject
{
    /**
     * @param  array<int, EmbeddingData>  $data
     */
    public function __construct(
        public readonly string $object,
        public readonly array $data,
        public readonly string $model,
        public readonly ?Usage $usage = null,
        public readonly array $raw = [],
    ) {
        self::assertArrayOf(EmbeddingData::class, $data, 'data');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $data = array_map(
            static fn (array $embedding): EmbeddingData => EmbeddingData::fromArray($embedding),
            $payload['data'] ?? []
        );

        return new self(
            object: $payload['object'] ?? 'list',
            data: $data,
            model: $payload['model'] ?? '',
            usage: isset($payload['usage']) ? Usage::fromArray($payload['usage']) : null,
            raw: $payload,
        );
    }

    public function toArray(): array
    {
        return [
            'object' => $this->object,
            'data' => array_map(
                static fn (EmbeddingData $embedding): array => $embedding->toArray(),
                $this->data
            ),
            'model' => $this->model,
            'usage' => $this->usage?->toArray(),
            'raw' => $this->raw,
        ];
    }
}
