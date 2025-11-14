<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\DTO\Image;

use Modules\Core\Services\LmStudio\DTO\DataTransferObject;

/**
 * @phpstan-type ImageRaw array<string, mixed>
 */
final class ImageGenerationResponse extends DataTransferObject
{
    /**
     * @param  array<int, ImageData>  $data
     */
    public function __construct(
        public readonly int $created,
        public readonly array $data,
        public readonly array $raw = [],
    ) {
        self::assertArrayOf(ImageData::class, $data, 'data');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $data = array_map(
            static fn (array $image): ImageData => ImageData::fromArray($image),
            $payload['data'] ?? []
        );

        return new self(
            created: $payload['created'] ?? time(),
            data: $data,
            raw: $payload,
        );
    }

    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'data' => array_map(
                static fn (ImageData $image): array => $image->toArray(),
                $this->data
            ),
            'raw' => $this->raw,
        ];
    }
}
