<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\DTO;

use Illuminate\Contracts\Support\Arrayable;

final class HealthStatus implements Arrayable
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $lmStudioVersion = null,
        public readonly ?string $apiVersion = null,
        public readonly ?int $modelsLoaded = null,
        public readonly ?int $uptimeMs = null,
        public readonly array $raw = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            status: $payload['status'] ?? 'unknown',
            lmStudioVersion: $payload['lmstudio_version'] ?? null,
            apiVersion: $payload['api_version'] ?? null,
            modelsLoaded: $payload['models_loaded'] ?? null,
            uptimeMs: $payload['uptime_ms'] ?? null,
            raw: $payload,
        );
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'lmstudio_version' => $this->lmStudioVersion,
            'api_version' => $this->apiVersion,
            'models_loaded' => $this->modelsLoaded,
            'uptime_ms' => $this->uptimeMs,
            'raw' => $this->raw,
        ];
    }
}
