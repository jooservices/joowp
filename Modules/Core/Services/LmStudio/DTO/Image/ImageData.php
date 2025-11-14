<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\DTO\Image;

use Modules\Core\Services\LmStudio\DTO\DataTransferObject;

final class ImageData extends DataTransferObject
{
    public function __construct(
        public readonly ?string $url = null,
        public readonly ?string $b64Json = null,
        public readonly ?string $revisedPrompt = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            url: $payload['url'] ?? null,
            b64Json: $payload['b64_json'] ?? null,
            revisedPrompt: $payload['revised_prompt'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'url' => $this->url,
            'b64_json' => $this->b64Json,
            'revised_prompt' => $this->revisedPrompt,
        ], static fn ($value) => $value !== null);
    }
}
