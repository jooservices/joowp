<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\DTO\Audio;

use Modules\Core\Services\LmStudio\DTO\DataTransferObject;

final class SpeechResponse extends DataTransferObject
{
    public function __construct(
        public readonly string $binary,
        public readonly string $mimeType = 'audio/mpeg',
        public readonly array $rawHeaders = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'mime_type' => $this->mimeType,
            'raw_headers' => $this->rawHeaders,
            'binary_length' => strlen($this->binary),
        ];
    }
}
