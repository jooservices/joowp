<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\DTO\Embedding;

use Modules\Core\Services\LmStudio\DTO\DataTransferObject;

final class EmbeddingRequest extends DataTransferObject
{
    /**
     * @param  array<int, string>|string  $input
     */
    public function __construct(
        public readonly string $model,
        public readonly array|string $input,
        public readonly ?string $encodingFormat = null,
        public readonly ?string $user = null,
    ) {
        if (is_array($input)) {
            foreach ($input as $chunk) {
                if (! is_string($chunk)) {
                    throw new \InvalidArgumentException('Embedding input array must contain strings.');
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            model: $payload['model'],
            input: $payload['input'],
            encodingFormat: $payload['encoding_format'] ?? null,
            user: $payload['user'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'model' => $this->model,
            'input' => $this->input,
            'encoding_format' => $this->encodingFormat,
            'user' => $this->user,
        ], static fn ($value) => $value !== null);
    }
}
