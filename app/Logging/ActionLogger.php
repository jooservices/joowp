<?php

declare(strict_types=1);

namespace App\Logging;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ActionLogger
{
    public function log(
        string $operation,
        ?Authenticatable $actor,
        array $before = [],
        array $after = [],
        array $metadata = []
    ): void {
        Log::channel('action')->info('Domain action recorded', [
            'operation' => $operation,
            'actor' => $this->formatActor($actor),
            'occurred_at' => Carbon::now()->toIso8601String(),
            'before' => $before,
            'after' => $after,
            'metadata' => $metadata,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function formatActor(?Authenticatable $actor): ?array
    {
        if ($actor === null) {
            return null;
        }

        return [
            'id' => $actor->getAuthIdentifier(),
            'type' => $actor::class,
        ];
    }
}
