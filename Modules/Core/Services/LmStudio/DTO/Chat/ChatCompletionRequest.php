<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\DTO\Chat;

use Modules\Core\Services\LmStudio\DTO\DataTransferObject;

/**
 * @phpstan-type StopSequences array<int, string>
 */
final class ChatCompletionRequest extends DataTransferObject
{
    /**
     * @param  array<int, ChatMessage>  $messages
     * @param  StopSequences|null  $stop
     */
    public function __construct(
        public readonly string $model,
        public readonly array $messages,
        public readonly ?float $temperature = null,
        public readonly ?float $topP = null,
        public readonly ?int $maxTokens = null,
        public readonly ?float $presencePenalty = null,
        public readonly ?float $frequencyPenalty = null,
        public readonly ?int $seed = null,
        public readonly bool $stream = true,
        public readonly ?array $stop = null,
        public readonly array $metadata = [],
    ) {
        self::assertArrayOf(ChatMessage::class, $messages, 'messages');
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
        $messages = array_map(
            static fn (array $message): ChatMessage => ChatMessage::fromArray($message),
            $payload['messages'] ?? []
        );

        return new self(
            model: $payload['model'],
            messages: $messages,
            temperature: $payload['temperature'] ?? null,
            topP: $payload['top_p'] ?? null,
            maxTokens: $payload['max_tokens'] ?? null,
            presencePenalty: $payload['presence_penalty'] ?? null,
            frequencyPenalty: $payload['frequency_penalty'] ?? null,
            seed: $payload['seed'] ?? null,
            stream: (bool) ($payload['stream'] ?? true),
            stop: $payload['stop'] ?? null,
            metadata: $payload['metadata'] ?? [],
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'model' => $this->model,
            'messages' => array_map(
                static fn (ChatMessage $message): array => $message->toArray(),
                $this->messages
            ),
            'temperature' => $this->temperature,
            'top_p' => $this->topP,
            'max_tokens' => $this->maxTokens,
            'presence_penalty' => $this->presencePenalty,
            'frequency_penalty' => $this->frequencyPenalty,
            'seed' => $this->seed,
            'stream' => $this->stream,
            'stop' => $this->stop,
            'metadata' => $this->metadata,
        ], static fn ($value) => $value !== null);
    }
}
