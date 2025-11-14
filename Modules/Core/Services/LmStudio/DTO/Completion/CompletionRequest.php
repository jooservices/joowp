<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\DTO\Completion;

use Modules\Core\Services\LmStudio\DTO\DataTransferObject;

final class CompletionRequest extends DataTransferObject
{
    /**
     * @param  array<int, string>|string  $prompt
     * @param  array<int, string>|null  $stop
     */
    public function __construct(
        public readonly string $model,
        public readonly array|string $prompt,
        public readonly ?string $suffix = null,
        public readonly ?int $maxTokens = null,
        public readonly ?float $temperature = null,
        public readonly ?float $topP = null,
        public readonly ?float $presencePenalty = null,
        public readonly ?float $frequencyPenalty = null,
        public readonly ?array $stop = null,
        public readonly bool $stream = false,
        public readonly array $metadata = [],
    ) {
        if (is_array($this->prompt)) {
            foreach ($this->prompt as $chunk) {
                if (! is_string($chunk)) {
                    throw new \InvalidArgumentException('Prompt array must contain strings.');
                }
            }
        }
        if ($stop !== null) {
            foreach ($stop as $sequence) {
                if (! is_string($sequence)) {
                    throw new \InvalidArgumentException('Stop sequences must be strings.');
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
            prompt: $payload['prompt'] ?? '',
            suffix: $payload['suffix'] ?? null,
            maxTokens: $payload['max_tokens'] ?? null,
            temperature: $payload['temperature'] ?? null,
            topP: $payload['top_p'] ?? null,
            presencePenalty: $payload['presence_penalty'] ?? null,
            frequencyPenalty: $payload['frequency_penalty'] ?? null,
            stop: $payload['stop'] ?? null,
            stream: (bool) ($payload['stream'] ?? false),
            metadata: $payload['metadata'] ?? [],
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'model' => $this->model,
            'prompt' => $this->prompt,
            'suffix' => $this->suffix,
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'top_p' => $this->topP,
            'presence_penalty' => $this->presencePenalty,
            'frequency_penalty' => $this->frequencyPenalty,
            'stop' => $this->stop,
            'stream' => $this->stream,
            'metadata' => $this->metadata,
        ], static fn ($value) => $value !== null);
    }
}
