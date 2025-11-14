<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\DTO\Image;

use Modules\Core\Services\LmStudio\DTO\DataTransferObject;

final class ImageGenerationRequest extends DataTransferObject
{
    public function __construct(
        public readonly string $model,
        public readonly string $prompt,
        public readonly int $n = 1,
        public readonly string $size = '1024x1024',
        public readonly string $responseFormat = 'b64_json',
        public readonly string $quality = 'standard',
        public readonly ?string $user = null,
    ) {
        if ($n < 1) {
            throw new \InvalidArgumentException('Image generation requires at least one output.');
        }
    }

    /**
     * @return array<string, string|int|null>
     */
    public function toArray(): array
    {
        return [
            'model' => $this->model,
            'prompt' => $this->prompt,
            'n' => $this->n,
            'size' => $this->size,
            'response_format' => $this->responseFormat,
            'quality' => $this->quality,
            'user' => $this->user,
        ];
    }
}
