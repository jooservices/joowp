<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\DTO;

use Illuminate\Contracts\Support\Arrayable;

final class ModelSummary implements Arrayable
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly string $id,
        public readonly string $ownedBy,
        public readonly ?int $created,
        public readonly string $status,
        public readonly array $metadata = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            id: $payload['id'],
            ownedBy: $payload['owned_by'] ?? 'lmstudio',
            created: $payload['created'] ?? null,
            status: $payload['status'] ?? 'unknown',
            metadata: $payload['metadata'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'owned_by' => $this->ownedBy,
            'created' => $this->created,
            'status' => $this->status,
            'metadata' => $this->metadata,
        ];
    }
}
