<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\DTO;

use Illuminate\Contracts\Support\Arrayable;

final class ListModelsFilter implements Arrayable
{
    public function __construct(
        public readonly ?string $ownedBy = null,
        public readonly ?string $status = null,
        public readonly ?int $limit = null,
        public readonly ?string $cursor = null,
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->ownedBy === null
            && $this->status === null
            && $this->limit === null
            && $this->cursor === null;
    }

    public function toArray(): array
    {
        return array_filter([
            'owned_by' => $this->ownedBy,
            'status' => $this->status,
            'limit' => $this->limit,
            'cursor' => $this->cursor,
        ], static fn ($value) => $value !== null && $value !== '');
    }
}
