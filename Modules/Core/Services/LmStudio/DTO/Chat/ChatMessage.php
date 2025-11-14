<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\DTO\Chat;

use Illuminate\Contracts\Support\Arrayable;

final class ChatMessage implements Arrayable
{
    /**
     * @param  array<int, array<string, mixed>>|null  $toolCalls
     */
    public function __construct(
        public readonly ChatRole $role,
        public readonly string $content,
        public readonly ?string $name = null,
        public readonly ?array $toolCalls = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            role: ChatRole::from($payload['role']),
            content: (string) ($payload['content'] ?? ''),
            name: $payload['name'] ?? null,
            toolCalls: $payload['tool_calls'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'role' => $this->role->value,
            'content' => $this->content,
            'name' => $this->name,
            'tool_calls' => $this->toolCalls,
        ], static fn ($value) => $value !== null);
    }
}
