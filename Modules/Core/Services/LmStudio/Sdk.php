<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio;

use App\Logging\ActionLogger;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Core\Services\LmStudio\Contracts\SdkContract;
use Modules\Core\Services\LmStudio\DTO\Audio\SpeechRequest;
use Modules\Core\Services\LmStudio\DTO\Audio\SpeechResponse;
use Modules\Core\Services\LmStudio\DTO\Audio\TranscriptionRequest;
use Modules\Core\Services\LmStudio\DTO\Audio\TranscriptionResponse;
use Modules\Core\Services\LmStudio\DTO\Audio\TranslationRequest;
use Modules\Core\Services\LmStudio\DTO\Audio\TranslationResponse;
use Modules\Core\Services\LmStudio\DTO\Chat\ChatCompletionRequest;
use Modules\Core\Services\LmStudio\DTO\Chat\ChatCompletionResponse;
use Modules\Core\Services\LmStudio\DTO\Completion\CompletionRequest;
use Modules\Core\Services\LmStudio\DTO\Completion\CompletionResponse;
use Modules\Core\Services\LmStudio\DTO\Embedding\EmbeddingRequest;
use Modules\Core\Services\LmStudio\DTO\Embedding\EmbeddingResponse;
use Modules\Core\Services\LmStudio\DTO\HealthStatus;
use Modules\Core\Services\LmStudio\DTO\Image\ImageGenerationRequest;
use Modules\Core\Services\LmStudio\DTO\Image\ImageGenerationResponse;
use Modules\Core\Services\LmStudio\DTO\ListModelsFilter;
use Modules\Core\Services\LmStudio\DTO\ModelSummary;
use Modules\Core\Services\LmStudio\Exceptions\ConnectionException;
use Modules\Core\Services\LmStudio\Streaming\StreamObserver;

final class Sdk implements SdkContract
{
    public function __construct(
        private readonly ActionLogger $actionLogger,
        private readonly string $baseUrl,
        private readonly ?string $apiKey = null,
        private readonly int $timeout = 30,
        private readonly int $connectTimeout = 10,
        private readonly int $maxRetries = 2,
        private readonly bool $verifyTls = true,
        private readonly string $logChannel = 'external',
    ) {
    }

    /**
     * @return array<int, ModelSummary>
     */
    public function listModels(?ListModelsFilter $filter = null): array
    {
        try {
            $response = $this->makeRequest('GET', '/v1/models', $filter?->toArray() ?? []);

            $models = [];
            foreach ($response['data'] ?? [] as $model) {
                $models[] = ModelSummary::fromArray($model);
            }

            return $models;
        } catch (\Throwable $e) {
            throw new ConnectionException('Failed to list models: ' . $e->getMessage(), 0, $e, [
                'endpoint' => '/v1/models',
                'filter' => $filter?->toArray(),
            ]);
        }
    }

    public function healthCheck(): HealthStatus
    {
        try {
            $response = $this->makeRequest('GET', '/health');

            return HealthStatus::fromArray($response);
        } catch (\Throwable $e) {
            throw new ConnectionException('Health check failed: ' . $e->getMessage(), 0, $e, [
                'endpoint' => '/health',
            ]);
        }
    }

    // Stub methods for other contract methods
    public function createChatCompletion(ChatCompletionRequest $request, ?StreamObserver $observer = null): ChatCompletionResponse
    {
        try {
            $response = $this->makeRequest('POST', '/v1/chat/completions', $request->toArray());

            return ChatCompletionResponse::fromArray($response);
        } catch (\Throwable $e) {
            throw new ConnectionException('Failed to create chat completion: ' . $e->getMessage(), 0, $e, [
                'endpoint' => '/v1/chat/completions',
            ]);
        }
    }

    public function createCompletion(CompletionRequest $request, ?StreamObserver $observer = null): CompletionResponse
    {
        throw new \BadMethodCallException('Not implemented yet');
    }

    public function createEmbedding(EmbeddingRequest $request): EmbeddingResponse
    {
        throw new \BadMethodCallException('Not implemented yet');
    }

    public function createTranscription(TranscriptionRequest $request): TranscriptionResponse
    {
        throw new \BadMethodCallException('Not implemented yet');
    }

    public function createTranslation(TranslationRequest $request): TranslationResponse
    {
        throw new \BadMethodCallException('Not implemented yet');
    }

    public function createSpeech(SpeechRequest $request): SpeechResponse
    {
        throw new \BadMethodCallException('Not implemented yet');
    }

    public function createImage(ImageGenerationRequest $request): ImageGenerationResponse
    {
        throw new \BadMethodCallException('Not implemented yet');
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function makeRequest(string $method, string $endpoint, array $params = []): array
    {
        $startedAt = microtime(true);
        $request = Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->connectTimeout($this->connectTimeout)
            ->retry($this->maxRetries, 100);
        $response = null;

        if (! $this->verifyTls) {
            $request = $request->withOptions(['verify' => false]);
        }

        if ($this->apiKey) {
            $request = $request->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ]);
        }

        try {
            $response = match ($method) {
                'GET' => $request->get($endpoint, $params),
                'POST' => $request->post($endpoint, $params),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };

            if ($response->failed()) {
                throw new ConnectionException("HTTP {$response->status()}: {$response->body()}", context: [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                ]);
            }

            $decoded = $response->json() ?? [];

            $this->recordTelemetry(
                method: $method,
                endpoint: $endpoint,
                params: $params,
                startedAt: $startedAt,
                success: true,
                status: $response->status(),
            );

            return $decoded;
        } catch (\Throwable $exception) {
            $this->recordTelemetry(
                method: $method,
                endpoint: $endpoint,
                params: $params,
                startedAt: $startedAt,
                success: false,
                status: $response?->status(),
                exception: $exception,
            );

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function recordTelemetry(
        string $method,
        string $endpoint,
        array $params,
        float $startedAt,
        bool $success,
        ?int $status = null,
        ?\Throwable $exception = null,
    ): void {
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        $metadata = [
            'method' => $method,
            'endpoint' => $endpoint,
            'status' => $status,
            'duration_ms' => $durationMs,
            'success' => $success,
            'payload_keys' => array_keys($params),
        ];

        if ($exception !== null) {
            $metadata['error'] = $exception->getMessage();
        }

        Log::channel($this->logChannel)->info('LM Studio request', $metadata);

        $this->actionLogger->log(
            operation: 'lmstudio.request',
            actor: null,
            before: [
                'method' => $method,
                'endpoint' => $endpoint,
            ],
            after: [
                'status' => $status,
                'success' => $success,
            ],
            metadata: [
                'duration_ms' => $durationMs,
                'payload_keys' => $metadata['payload_keys'],
                'error' => $exception?->getMessage(),
            ],
        );
    }
}
