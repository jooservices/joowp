<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\DTO\Shared;

use Modules\Core\Services\LmStudio\DTO\DataTransferObject;

final class Usage extends DataTransferObject
{
    public function __construct(
        public readonly ?int $promptTokens = null,
        public readonly ?int $completionTokens = null,
        public readonly ?int $totalTokens = null,
    ) {
    }

    /**
     * @param  array<string, int>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            promptTokens: $payload['prompt_tokens'] ?? null,
            completionTokens: $payload['completion_tokens'] ?? null,
            totalTokens: $payload['total_tokens'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'prompt_tokens' => $this->promptTokens,
            'completion_tokens' => $this->completionTokens,
            'total_tokens' => $this->totalTokens,
        ];
    }
}
