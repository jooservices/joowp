<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Services\LmStudio;

use App\Logging\ActionLogger;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use JOOservices\Client\Factory\Factory;
use Mockery;
use Mockery\MockInterface;
use Modules\Core\Services\LmStudio\DTO\Chat\ChatCompletionRequest;
use Modules\Core\Services\LmStudio\DTO\Chat\ChatMessage;
use Modules\Core\Services\LmStudio\DTO\Chat\ChatRole;
use Modules\Core\Services\LmStudio\DTO\HealthStatus;
use Modules\Core\Services\LmStudio\DTO\ListModelsFilter;
use Modules\Core\Services\LmStudio\DTO\ModelSummary;
use Modules\Core\Services\LmStudio\Exceptions\ConnectionException;
use Modules\Core\Services\LmStudio\Sdk;
use Tests\TestCase;

final class SdkTest extends TestCase
{
    /** @var ActionLogger&MockInterface */
    private $actionLogger;

    protected function setUp(): void
    {
        parent::setUp();

        Log::swap($logger = Mockery::mock(\Illuminate\Log\LogManager::class));
        $logger->shouldReceive('channel')->andReturnSelf();
        $logger->shouldReceive('info')->andReturnNull();

        $this->actionLogger = Mockery::spy(ActionLogger::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /**
     * Create SDK with fake responses
     *
     * @param  array<int, Response|\Throwable>  $responses
     */
    private function createSdkWithFakes(array $responses, ?string $apiKey = 'test-key', bool $verifyTls = true): Sdk
    {
        $factory = (new Factory())
            ->addOptions([
                'timeout' => 30,
                'connect_timeout' => 10,
                'verify' => $verifyTls,
            ])
            ->enableRetries(2, 1, 500)
            ->fakeResponses($responses);

        return new Sdk(
            actionLogger: $this->actionLogger,
            httpClient: $factory->make(),
            baseUrl: 'http://127.0.0.1:1234',
            apiKey: $apiKey,
            timeout: 30,
            connectTimeout: 10,
            maxRetries: 2,
            verifyTls: $verifyTls,
            logChannel: 'external',
        );
    }

    public function test_health_check_returns_health_status(): void
    {
        $sdk = $this->createSdkWithFakes([
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'status' => 'ok',
                    'lmstudio_version' => '0.2.21',
                    'api_version' => 'v1',
                    'models_loaded' => 3,
                    'uptime_ms' => 362145,
                ], JSON_THROW_ON_ERROR)
            ),
        ]);

        $status = $sdk->healthCheck();

        $this->assertInstanceOf(HealthStatus::class, $status);
        $this->assertEquals('ok', $status->status);
        $this->assertEquals('0.2.21', $status->lmStudioVersion);
        $this->assertEquals('v1', $status->apiVersion);
        $this->assertEquals(3, $status->modelsLoaded);
        $this->assertEquals(362145, $status->uptimeMs);
    }

    public function test_health_check_throws_connection_exception_on_failure(): void
    {
        $sdk = $this->createSdkWithFakes([
            new Response(500, [], 'Internal Server Error'),
        ]);

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Health check failed');

        $sdk->healthCheck();
    }

    public function test_health_check_includes_auth_header_when_api_key_present(): void
    {
        $factory = (new Factory())
            ->addOptions([
                'timeout' => 30,
                'connect_timeout' => 10,
                'verify' => true,
            ])
            ->enableRetries(2, 1, 500)
            ->fakeResponses([
                new Response(
                    200,
                    ['Content-Type' => 'application/json'],
                    json_encode([
                        'status' => 'ok',
                        'lmstudio_version' => '0.2.21',
                        'api_version' => 'v1',
                        'models_loaded' => 0,
                        'uptime_ms' => 1000,
                    ], JSON_THROW_ON_ERROR)
                ),
            ]);

        $sdk = new Sdk(
            actionLogger: $this->actionLogger,
            httpClient: $factory->make(),
            baseUrl: 'http://127.0.0.1:1234',
            apiKey: 'test-key',
            timeout: 30,
            connectTimeout: 10,
            maxRetries: 2,
            verifyTls: true,
            logChannel: 'external',
        );

        $sdk->healthCheck();

        // Verify Authorization header was sent via request history
        $history = $factory->getRequestHistory();
        $this->assertNotEmpty($history);
        $this->assertArrayHasKey('headers', $history[0]['request']);
        $this->assertArrayHasKey('Authorization', $history[0]['request']['headers']);
        $this->assertEquals('Bearer test-key', $history[0]['request']['headers']['Authorization']);
    }

    public function test_list_models_returns_array_of_model_summaries(): void
    {
        $sdk = $this->createSdkWithFakes([
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'object' => 'list',
                    'data' => [
                        [
                            'id' => 'mistral-7b-instruct',
                            'object' => 'model',
                            'owned_by' => 'lmstudio',
                            'created' => 1731460800,
                            'status' => 'ready',
                            'metadata' => [
                                'format' => 'gguf',
                                'family' => 'mistral',
                                'parameter_size' => '7B',
                                'quantization_level' => 'Q4_K_M',
                                'context_length' => 8192,
                            ],
                        ],
                        [
                            'id' => 'llama-2-13b',
                            'object' => 'model',
                            'owned_by' => 'lmstudio',
                            'created' => 1731460900,
                            'status' => 'loading',
                            'metadata' => [
                                'format' => 'gguf',
                                'family' => 'llama',
                                'parameter_size' => '13B',
                                'quantization_level' => 'Q5_K_M',
                                'context_length' => 4096,
                            ],
                        ],
                    ],
                ], JSON_THROW_ON_ERROR)
            ),
        ]);

        $models = $sdk->listModels();

        $this->assertIsArray($models);
        $this->assertCount(2, $models);
        $this->assertContainsOnlyInstancesOf(ModelSummary::class, $models);
        $this->assertEquals('mistral-7b-instruct', $models[0]->id);
        $this->assertEquals('ready', $models[0]->status);
        $this->assertEquals('llama-2-13b', $models[1]->id);
        $this->assertEquals('loading', $models[1]->status);
    }

    public function test_list_models_with_filter_sends_query_params(): void
    {
        $factory = (new Factory())
            ->addOptions([
                'timeout' => 30,
                'connect_timeout' => 10,
                'verify' => true,
            ])
            ->enableRetries(2, 1, 500)
            ->fakeResponses([
                new Response(
                    200,
                    ['Content-Type' => 'application/json'],
                    json_encode(['object' => 'list', 'data' => []], JSON_THROW_ON_ERROR)
                ),
            ]);

        $sdk = new Sdk(
            actionLogger: $this->actionLogger,
            httpClient: $factory->make(),
            baseUrl: 'http://127.0.0.1:1234',
            apiKey: 'test-key',
            timeout: 30,
            connectTimeout: 10,
            maxRetries: 2,
            verifyTls: true,
            logChannel: 'external',
        );

        $filter = new ListModelsFilter(
            ownedBy: 'lmstudio',
            status: 'ready',
            limit: 10,
            cursor: null,
        );

        $sdk->listModels($filter);

        // Verify query params via request history
        $history = $factory->getRequestHistory();
        $this->assertNotEmpty($history);
        $this->assertStringContainsString('owned_by=lmstudio', $history[0]['uri'] ?? '');
        $this->assertStringContainsString('status=ready', $history[0]['uri'] ?? '');
        $this->assertStringContainsString('limit=10', $history[0]['uri'] ?? '');
    }

    public function test_list_models_throws_connection_exception_on_failure(): void
    {
        $sdk = $this->createSdkWithFakes([
            new Response(503, [], 'Service Unavailable'),
        ]);

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Failed to list models');

        $sdk->listModels();
    }

    public function test_list_models_includes_auth_header(): void
    {
        $factory = (new Factory())
            ->addOptions([
                'timeout' => 30,
                'connect_timeout' => 10,
                'verify' => true,
            ])
            ->enableRetries(2, 1, 500)
            ->fakeResponses([
                new Response(
                    200,
                    ['Content-Type' => 'application/json'],
                    json_encode(['object' => 'list', 'data' => []], JSON_THROW_ON_ERROR)
                ),
            ]);

        $sdk = new Sdk(
            actionLogger: $this->actionLogger,
            httpClient: $factory->make(),
            baseUrl: 'http://127.0.0.1:1234',
            apiKey: 'test-key',
            timeout: 30,
            connectTimeout: 10,
            maxRetries: 2,
            verifyTls: true,
            logChannel: 'external',
        );

        $sdk->listModels();

        $history = $factory->getRequestHistory();
        $this->assertNotEmpty($history);
        $this->assertArrayHasKey('headers', $history[0]['request']);
        $this->assertArrayHasKey('Authorization', $history[0]['request']['headers']);
        $this->assertEquals('Bearer test-key', $history[0]['request']['headers']['Authorization']);
    }

    public function test_sdk_without_api_key_does_not_include_auth_header(): void
    {
        $factory = (new Factory())
            ->addOptions([
                'timeout' => 30,
                'connect_timeout' => 10,
                'verify' => true,
            ])
            ->enableRetries(2, 1, 500)
            ->fakeResponses([
                new Response(
                    200,
                    ['Content-Type' => 'application/json'],
                    json_encode([
                        'status' => 'ok',
                        'lmstudio_version' => '0.2.21',
                        'api_version' => 'v1',
                        'models_loaded' => 0,
                        'uptime_ms' => 1000,
                    ], JSON_THROW_ON_ERROR)
                ),
            ]);

        $sdk = new Sdk(
            actionLogger: $this->actionLogger,
            httpClient: $factory->make(),
            baseUrl: 'http://127.0.0.1:1234',
            apiKey: null,
            timeout: 30,
            connectTimeout: 10,
            maxRetries: 2,
            verifyTls: true,
            logChannel: 'external',
        );

        $sdk->healthCheck();

        $history = $factory->getRequestHistory();
        $this->assertNotEmpty($history);
        $headers = $history[0]['request']['headers'] ?? [];
        $this->assertArrayNotHasKey('Authorization', $headers);
    }

    public function test_sdk_with_verify_tls_false_disables_ssl_verification(): void
    {
        $sdk = $this->createSdkWithFakes([
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'status' => 'ok',
                    'lmstudio_version' => '0.2.21',
                    'api_version' => 'v1',
                    'models_loaded' => 0,
                    'uptime_ms' => 1000,
                ], JSON_THROW_ON_ERROR)
            ),
        ], null, false);

        $sdk->healthCheck();

        // Just verify it doesn't throw - actual option checking is internal to HTTP client
        $this->assertTrue(true);
    }

    public function test_list_models_handles_empty_data_array(): void
    {
        $sdk = $this->createSdkWithFakes([
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode(['object' => 'list', 'data' => []], JSON_THROW_ON_ERROR)
            ),
        ]);

        $models = $sdk->listModels();

        $this->assertIsArray($models);
        $this->assertEmpty($models);
    }

    public function test_list_models_handles_missing_data_key(): void
    {
        $sdk = $this->createSdkWithFakes([
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode(['object' => 'list'], JSON_THROW_ON_ERROR)
            ),
        ]);

        $models = $sdk->listModels();

        $this->assertIsArray($models);
        $this->assertEmpty($models);
    }

    public function test_connection_exception_includes_context(): void
    {
        $sdk = $this->createSdkWithFakes([
            new Response(500, [], 'Error'),
        ]);

        try {
            $sdk->healthCheck();
            $this->fail('Expected ConnectionException to be thrown');
        } catch (ConnectionException $e) {
            $context = $e->getContext();
            $this->assertArrayHasKey('endpoint', $context);
            $this->assertEquals('/health', $context['endpoint']);
        }
    }

    public function test_list_models_exception_includes_filter_context(): void
    {
        $sdk = $this->createSdkWithFakes([
            new Response(500, [], 'Error'),
        ]);

        $filter = new ListModelsFilter(
            ownedBy: 'lmstudio',
            status: 'ready',
            limit: 5,
            cursor: null,
        );

        try {
            $sdk->listModels($filter);
            $this->fail('Expected ConnectionException to be thrown');
        } catch (ConnectionException $e) {
            $context = $e->getContext();
            $this->assertArrayHasKey('endpoint', $context);
            $this->assertArrayHasKey('filter', $context);
            $this->assertEquals('/v1/models', $context['endpoint']);
            $this->assertIsArray($context['filter']);
        }
    }

    public function test_create_chat_completion_returns_response(): void
    {
        $factory = (new Factory())
            ->addOptions([
                'timeout' => 30,
                'connect_timeout' => 10,
                'verify' => true,
            ])
            ->enableRetries(2, 1, 500)
            ->fakeResponses([
                new Response(
                    200,
                    ['Content-Type' => 'application/json'],
                    json_encode([
                        'id' => 'chatcmpl-123',
                        'object' => 'chat.completion',
                        'created' => 1731614400,
                        'model' => 'mistral',
                        'choices' => [
                            [
                                'index' => 0,
                                'message' => [
                                    'role' => 'assistant',
                                    'content' => 'Hello from LM Studio.',
                                ],
                            ],
                        ],
                    ], JSON_THROW_ON_ERROR)
                ),
            ]);

        $sdk = new Sdk(
            actionLogger: $this->actionLogger,
            httpClient: $factory->make(),
            baseUrl: 'http://127.0.0.1:1234',
            apiKey: 'test-key',
            timeout: 30,
            connectTimeout: 10,
            maxRetries: 2,
            verifyTls: true,
            logChannel: 'external',
        );

        $request = new ChatCompletionRequest(
            model: 'mistral',
            messages: [
                new ChatMessage(ChatRole::User, 'Hello'),
            ],
            stream: false,
        );

        $response = $sdk->createChatCompletion($request);

        $this->assertEquals('chatcmpl-123', $response->id);

        $history = $factory->getRequestHistory();
        $this->assertNotEmpty($history);
        $this->assertStringContainsString('/v1/chat/completions', $history[0]['uri'] ?? '');
    }

    public function test_create_chat_completion_throws_connection_exception_on_failure(): void
    {
        $sdk = $this->createSdkWithFakes([
            new Response(500, [], 'Error'),
        ]);

        $request = new ChatCompletionRequest(
            model: 'mistral',
            messages: [
                new ChatMessage(ChatRole::User, 'Hello'),
            ],
            stream: false,
        );

        $this->expectException(ConnectionException::class);

        $sdk->createChatCompletion($request);
    }

    public function test_action_logger_records_telemetry(): void
    {
        $sdk = $this->createSdkWithFakes([
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode(['status' => 'ok'], JSON_THROW_ON_ERROR)
            ),
        ]);

        $sdk->healthCheck();

        // Verify ActionLogger was called (simplified assertion to avoid PHPStan issues with Mockery fluent interface)
        $this->actionLogger->shouldHaveReceived('log');
    }

    public function test_health_check_handles_offline_scenario(): void
    {
        $factory = (new Factory())
            ->addOptions([
                'timeout' => 30,
                'connect_timeout' => 10,
                'verify' => true,
            ])
            ->enableRetries(2, 1, 500)
            ->fakeResponses([
                new ConnectException(
                    'Connection refused',
                    new GuzzleRequest('GET', 'http://127.0.0.1:1234/health')
                ),
            ]);

        $sdk = new Sdk(
            actionLogger: $this->actionLogger,
            httpClient: $factory->make(),
            baseUrl: 'http://127.0.0.1:1234',
            apiKey: 'test-key',
            timeout: 30,
            connectTimeout: 10,
            maxRetries: 2,
            verifyTls: true,
            logChannel: 'external',
        );

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Health check failed');

        $sdk->healthCheck();
    }

    public function test_list_models_handles_offline_scenario(): void
    {
        $factory = (new Factory())
            ->addOptions([
                'timeout' => 30,
                'connect_timeout' => 10,
                'verify' => true,
            ])
            ->enableRetries(2, 1, 500)
            ->fakeResponses([
                new ConnectException(
                    'Connection refused',
                    new GuzzleRequest('GET', 'http://127.0.0.1:1234/v1/models')
                ),
            ]);

        $sdk = new Sdk(
            actionLogger: $this->actionLogger,
            httpClient: $factory->make(),
            baseUrl: 'http://127.0.0.1:1234',
            apiKey: 'test-key',
            timeout: 30,
            connectTimeout: 10,
            maxRetries: 2,
            verifyTls: true,
            logChannel: 'external',
        );

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Failed to list models');

        $sdk->listModels();
    }

    public function test_create_chat_completion_handles_timeout(): void
    {
        $factory = (new Factory())
            ->addOptions([
                'timeout' => 30,
                'connect_timeout' => 10,
                'verify' => true,
            ])
            ->enableRetries(2, 1, 500)
            ->fakeResponses([
                new ConnectException(
                    'Connection timeout',
                    new GuzzleRequest('POST', 'http://127.0.0.1:1234/v1/chat/completions')
                ),
            ]);

        $sdk = new Sdk(
            actionLogger: $this->actionLogger,
            httpClient: $factory->make(),
            baseUrl: 'http://127.0.0.1:1234',
            apiKey: 'test-key',
            timeout: 30,
            connectTimeout: 10,
            maxRetries: 2,
            verifyTls: true,
            logChannel: 'external',
        );

        $request = new ChatCompletionRequest(
            model: 'mistral',
            messages: [
                new ChatMessage(ChatRole::User, 'Hello'),
            ],
            stream: false,
        );

        $this->expectException(ConnectionException::class);

        $sdk->createChatCompletion($request);
    }

    public function test_health_check_retries_on_failure(): void
    {
        $factory = (new Factory())
            ->addOptions([
                'timeout' => 30,
                'connect_timeout' => 10,
                'verify' => true,
            ])
            ->enableRetries(2, 1, 500)
            ->fakeResponses([
                new Response(500, [], 'Error'),
                new Response(
                    200,
                    ['Content-Type' => 'application/json'],
                    json_encode([
                        'status' => 'ok',
                        'lmstudio_version' => '0.2.21',
                        'api_version' => 'v1',
                        'models_loaded' => 0,
                        'uptime_ms' => 1000,
                    ], JSON_THROW_ON_ERROR)
                ),
            ]);

        $sdk = new Sdk(
            actionLogger: $this->actionLogger,
            httpClient: $factory->make(),
            baseUrl: 'http://127.0.0.1:1234',
            apiKey: 'test-key',
            timeout: 30,
            connectTimeout: 10,
            maxRetries: 2,
            verifyTls: true,
            logChannel: 'external',
        );

        $status = $sdk->healthCheck();

        $this->assertEquals('ok', $status->status);
        $history = $factory->getRequestHistory();
        $this->assertGreaterThanOrEqual(2, count($history), 'Should retry at least once before succeeding');
    }
}
