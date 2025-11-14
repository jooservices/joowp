<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\DTO\Audio;

use Modules\Core\Services\LmStudio\DTO\DataTransferObject;

final class SpeechRequest extends DataTransferObject
{
    public function __construct(
        public readonly string $model,
        public readonly string $voice,
        public readonly string $input,
        public readonly string $format = 'mp3',
        public readonly array $metadata = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'model' => $this->model,
            'voice' => $this->voice,
            'input' => $this->input,
            'format' => $this->format,
            'metadata' => $this->metadata,
        ];
    }
}
