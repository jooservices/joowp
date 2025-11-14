<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\DTO\Audio;

use Modules\Core\Services\LmStudio\DTO\DataTransferObject;

final class TranscriptionResponse extends DataTransferObject
{
    public function __construct(
        public readonly string $text,
        public readonly ?string $language = null,
        public readonly ?float $duration = null,
        public readonly array $segments = [],
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
            language: $payload['language'] ?? null,
            duration: isset($payload['duration']) ? (float) $payload['duration'] : null,
            segments: $payload['segments'] ?? [],
            raw: $payload,
        );
    }

    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'language' => $this->language,
            'duration' => $this->duration,
            'segments' => $this->segments,
            'raw' => $this->raw,
        ];
    }
}
