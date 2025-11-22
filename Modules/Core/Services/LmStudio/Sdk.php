<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio;

use App\Logging\ActionLogger;
use Illuminate\Support\Facades\Log;
use JOOservices\Client\Contracts\HttpClientContract;
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
        private readonly HttpClientContract $httpClient,
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
        $uri = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        $options = [
            'timeout' => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
        ];

        if (! $this->verifyTls) {
            $options['verify'] = false;
        }

        if ($this->apiKey) {
            $options['headers'] = [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ];
        }

        try {
            // Use get()/post() to get ResponseWrapper, then extract JSON content
            $responseWrapper = match ($method) {
                'GET' => $this->httpClient->get($uri, array_merge($options, ['query' => $params])),
                'POST' => $this->httpClient->post($uri, array_merge($options, ['json' => $params])),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };

            if (! $responseWrapper->isSuccess()) {
                $errorMessage = $responseWrapper->getErrorMessage();
                $statusCode = $responseWrapper->getStatusCode();

                throw new ConnectionException(
                    "HTTP {$statusCode}: " . ($errorMessage ?? 'Request failed'),
                    0,
                    null,
                    [
                        'endpoint' => $endpoint,
                        'status' => $statusCode,
                    ]
                );
            }

            // Get JSON content from ResponseWrapper
            $content = $responseWrapper->getContent();

            if (! is_array($content)) {
                // Response is successful but not JSON array
                throw new ConnectionException(
                    'Response is not a valid JSON array',
                    0,
                    null,
                    [
                        'endpoint' => $endpoint,
                        'status' => $responseWrapper->getStatusCode(),
                    ]
                );
            }

            /** @var array<string, mixed> $content */
            $result = $content;

            $this->recordTelemetry(
                method: $method,
                endpoint: $endpoint,
                params: $params,
                startedAt: $startedAt,
                success: true,
                status: 200, // getJson/postJson only returns array on success (2xx)
            );

            return $result;
        } catch (ConnectionException $exception) {
            // Re-throw ConnectionException as-is
            $this->recordTelemetry(
                method: $method,
                endpoint: $endpoint,
                params: $params,
                startedAt: $startedAt,
                success: false,
                status: $exception->getContext()['status'] ?? null,
                exception: $exception,
            );

            throw $exception;
        } catch (\Throwable $exception) {
            $this->recordTelemetry(
                method: $method,
                endpoint: $endpoint,
                params: $params,
                startedAt: $startedAt,
                success: false,
                status: null,
                exception: $exception,
            );

            throw new ConnectionException(
                'Request failed: ' . $exception->getMessage(),
                0,
                $exception,
                [
                    'endpoint' => $endpoint,
                ]
            );
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
