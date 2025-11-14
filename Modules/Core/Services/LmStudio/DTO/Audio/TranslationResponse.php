<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\DTO\Audio;

use Modules\Core\Services\LmStudio\DTO\DataTransferObject;

final class TranslationResponse extends DataTransferObject
{
    public function __construct(
        public readonly string $text,
        public readonly array $raw = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            text: (string) ($payload['text'] ?? ''),
            raw: $payload,
        );
    }

    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'raw' => $this->raw,
        ];
    }
}
