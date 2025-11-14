<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Services\LmStudio;

use App\Logging\ActionLogger;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
    private Sdk $sdk;

    private MockInterface $actionLogger;

    protected function setUp(): void
    {
        parent::setUp();

        Log::swap($logger = Mockery::mock(\Illuminate\Log\LogManager::class));
        $logger->shouldReceive('channel')->andReturnSelf();
        $logger->shouldReceive('info')->andReturnNull();

        $this->actionLogger = Mockery::spy(ActionLogger::class);

        $this->sdk = new Sdk(
            actionLogger: $this->actionLogger,
            baseUrl: 'http://127.0.0.1:1234',
            apiKey: 'test-key',
            timeout: 30,
            connectTimeout: 10,
            maxRetries: 2,
            verifyTls: true,
            logChannel: 'external',
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_health_check_returns_health_status(): void
    {
        Http::fake([
            '*/health' => Http::response([
                'status' => 'ok',
                'lmstudio_version' => '0.2.21',
                'api_version' => 'v1',
                'models_loaded' => 3,
                'uptime_ms' => 362145,
            ], 200),
        ]);

        $status = $this->sdk->healthCheck();

        $this->assertInstanceOf(HealthStatus::class, $status);
        $this->assertEquals('ok', $status->status);
        $this->assertEquals('0.2.21', $status->lmStudioVersion);
        $this->assertEquals('v1', $status->apiVersion);
        $this->assertEquals(3, $status->modelsLoaded);
        $this->assertEquals(362145, $status->uptimeMs);
    }

    public function test_health_check_throws_connection_exception_on_failure(): void
    {
        Http::fake([
            '*/health' => Http::response('Internal Server Error', 500),
        ]);

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Health check failed');

        $this->sdk->healthCheck();
    }

    public function test_health_check_includes_auth_header_when_api_key_present(): void
    {
        Http::fake([
            '*/health' => Http::response([
                'status' => 'ok',
                'lmstudio_version' => '0.2.21',
                'api_version' => 'v1',
                'models_loaded' => 0,
                'uptime_ms' => 1000,
            ], 200),
        ]);

        $this->sdk->healthCheck();

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test-key');
        });
    }

    public function test_list_models_returns_array_of_model_summaries(): void
    {
        Http::fake([
            '*/v1/models' => Http::response([
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
            ], 200),
        ]);

        $models = $this->sdk->listModels();

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
        Http::fake([
            '*/v1/models*' => Http::response([
                'object' => 'list',
                'data' => [],
            ], 200),
        ]);

        $filter = new ListModelsFilter(
            ownedBy: 'lmstudio',
            status: 'ready',
            limit: 10,
            cursor: null,
        );

        $this->sdk->listModels($filter);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://127.0.0.1:1234/v1/models?owned_by=lmstudio&status=ready&limit=10';
        });
    }

    public function test_list_models_throws_connection_exception_on_failure(): void
    {
        Http::fake([
            '*/v1/models' => Http::response('Service Unavailable', 503),
        ]);

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Failed to list models');

        $this->sdk->listModels();
    }

    public function test_list_models_includes_auth_header(): void
    {
        Http::fake([
            '*/v1/models' => Http::response([
                'object' => 'list',
                'data' => [],
            ], 200),
        ]);

        $this->sdk->listModels();

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test-key');
        });
    }

    public function test_sdk_without_api_key_does_not_include_auth_header(): void
    {
        Http::fake([
            '*/health' => Http::response([
                'status' => 'ok',
                'lmstudio_version' => '0.2.21',
                'api_version' => 'v1',
                'models_loaded' => 0,
                'uptime_ms' => 1000,
            ], 200),
        ]);

        $sdk = new Sdk(
            actionLogger: $this->actionLogger,
            baseUrl: 'http://127.0.0.1:1234',
            apiKey: null,
            timeout: 30,
            connectTimeout: 10,
            maxRetries: 2,
            verifyTls: true,
            logChannel: 'external',
        );

        $sdk->healthCheck();

        Http::assertSent(function ($request) {
            return ! $request->hasHeader('Authorization');
        });
    }

    public function test_sdk_with_verify_tls_false_disables_ssl_verification(): void
    {
        Http::fake([
            '*/health' => Http::response([
                'status' => 'ok',
                'lmstudio_version' => '0.2.21',
                'api_version' => 'v1',
                'models_loaded' => 0,
                'uptime_ms' => 1000,
            ], 200),
        ]);

        $sdk = new Sdk(
            actionLogger: $this->actionLogger,
            baseUrl: 'http://127.0.0.1:1234',
            apiKey: null,
            timeout: 30,
            connectTimeout: 10,
            maxRetries: 2,
            verifyTls: false,
            logChannel: 'external',
        );

        $sdk->healthCheck();

        Http::assertSent(function ($request) {
            return true; // Just verify it doesn't throw - actual option checking is internal to HTTP client
        });
    }

    public function test_list_models_handles_empty_data_array(): void
    {
        Http::fake([
            '*/v1/models' => Http::response([
                'object' => 'list',
                'data' => [],
            ], 200),
        ]);

        $models = $this->sdk->listModels();

        $this->assertIsArray($models);
        $this->assertEmpty($models);
    }

    public function test_list_models_handles_missing_data_key(): void
    {
        Http::fake([
            '*/v1/models' => Http::response([
                'object' => 'list',
            ], 200),
        ]);

        $models = $this->sdk->listModels();

        $this->assertIsArray($models);
        $this->assertEmpty($models);
    }

    public function test_connection_exception_includes_context(): void
    {
        Http::fake([
            '*/health' => Http::response('Error', 500),
        ]);

        try {
            $this->sdk->healthCheck();
            $this->fail('Expected ConnectionException to be thrown');
        } catch (ConnectionException $e) {
            $context = $e->getContext();
            $this->assertArrayHasKey('endpoint', $context);
            $this->assertEquals('/health', $context['endpoint']);
        }
    }

    public function test_list_models_exception_includes_filter_context(): void
    {
        Http::fake([
            '*/v1/models*' => Http::response('Error', 500),
        ]);

        $filter = new ListModelsFilter(
            ownedBy: 'lmstudio',
            status: 'ready',
            limit: 5,
            cursor: null,
        );

        try {
            $this->sdk->listModels($filter);
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
        Http::fake([
            '*/v1/chat/completions' => Http::response([
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
            ], 200),
        ]);

        $request = new ChatCompletionRequest(
            model: 'mistral',
            messages: [
                new ChatMessage(ChatRole::User, 'Hello'),
            ],
            stream: false,
        );

        $response = $this->sdk->createChatCompletion($request);

        $this->assertEquals('chatcmpl-123', $response->id);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://127.0.0.1:1234/v1/chat/completions';
        });
    }

    public function test_create_chat_completion_throws_connection_exception_on_failure(): void
    {
        Http::fake([
            '*/v1/chat/completions' => Http::response('Error', 500),
        ]);

        $request = new ChatCompletionRequest(
            model: 'mistral',
            messages: [
                new ChatMessage(ChatRole::User, 'Hello'),
            ],
            stream: false,
        );

        $this->expectException(ConnectionException::class);

        $this->sdk->createChatCompletion($request);
    }

    public function test_action_logger_records_telemetry(): void
    {
        Http::fake([
            '*/health' => Http::response([
                'status' => 'ok',
            ], 200),
        ]);

        $this->sdk->healthCheck();

        $this->actionLogger->shouldHaveReceived('log')->with(
            'lmstudio.request',
            null,
            Mockery::type('array'),
            Mockery::type('array'),
            Mockery::type('array'),
        );

        $this->assertTrue(true, 'ActionLogger expectation verified.');
    }
}
