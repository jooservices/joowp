<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\DTO\Audio;

use Modules\Core\Services\LmStudio\DTO\DataTransferObject;

final class TranslationRequest extends DataTransferObject
{
    public function __construct(
        public readonly string $model,
        public readonly string $filePath,
        public readonly ?string $prompt = null,
        public readonly ?string $responseFormat = 'json',
        public readonly ?float $temperature = null,
    ) {
    }

    public function toArray(): array
    {
        return array_filter([
            'model' => $this->model,
            'file' => $this->filePath,
            'prompt' => $this->prompt,
            'response_format' => $this->responseFormat,
            'temperature' => $this->temperature,
        ], static fn ($value) => $value !== null);
    }
}
