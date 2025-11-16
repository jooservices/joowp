<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\Core\Services\LmStudio\Contracts\SdkContract;
use Modules\Core\Services\LmStudio\DTO\ModelSummary;
use Modules\Core\Services\LmStudio\Inference\ChatInferenceService;
use Tests\TestCase;

final class LmStudioApiTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['features.lmstudio.enabled' => true]);

        // Mock external SDK (required for all tests)
        // ChatInferenceService will be resolved with real implementation
        $sdk = Mockery::mock(SdkContract::class)->shouldIgnoreMissing();
        $this->app->instance(SdkContract::class, $sdk);
    }

    public function test_it_lists_models(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $sdk->shouldReceive('listModels')
            ->once()
            ->andReturn([
                ModelSummary::fromArray([
                    'id' => 'mistral',
                    'owned_by' => 'lmstudio',
                    'created' => 123,
                    'status' => 'ready',
                    'metadata' => [],
                ]),
            ]);

        $this->app->instance(SdkContract::class, $sdk);

        $this->getJson('/api/v1/ai/lmstudio/models')
            ->assertOk()
            ->assertJsonPath('code', 'lmstudio.models_listed')
            ->assertJsonPath('data.models.0.id', 'mistral');
    }

    public function test_it_starts_inference_job(): void
    {
        // Arrange: Mock external SDK, use real ChatInferenceService
        $sdk = Mockery::mock(SdkContract::class);
        $sdk->shouldReceive('listModels')->zeroOrMoreTimes();
        
        // Mock SDK's createChatCompletion method (external dependency)
        $sdk->shouldReceive('createChatCompletion')
            ->once()
            ->andReturn(
                \Modules\Core\Services\LmStudio\DTO\Chat\ChatCompletionResponse::fromArray([
                    'id' => 'chat-123',
                    'model' => 'mistral',
                    'created' => 1731614400,
                    'choices' => [
                        [
                            'index' => 0,
                            'message' => [
                                'role' => 'assistant',
                                'content' => 'Hello! How can I help you?',
                            ],
                            'finish_reason' => 'stop',
                        ],
                    ],
                ])
            );
        
        $this->app->instance(SdkContract::class, $sdk);

        // Use Event::fake() to verify events are dispatched
        \Illuminate\Support\Facades\Event::fake();

        $payload = [
            'model' => 'mistral',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello'],
            ],
        ];

        // Act
        $response = $this->postJson('/api/v1/ai/lmstudio/infer', $payload);

        // Assert
        $response->assertStatus(202)
            ->assertJsonPath('status', 202)
            ->assertJsonPath('code', 'lmstudio.infer.accepted')
            ->assertJsonStructure([
                'data' => [
                    'job_id',
                    'model',
                    'created',
                ],
            ])
            ->assertJsonPath('data.model', 'mistral');

        // Verify events were dispatched by real ChatInferenceService
        \Illuminate\Support\Facades\Event::assertDispatched(
            \Modules\Core\Events\LmStudioInferenceStreamed::class
        );
    }

    public function test_it_rejects_when_feature_disabled(): void
    {
        config(['features.lmstudio.enabled' => false]);

        $this->postJson('/api/v1/ai/lmstudio/infer', [
            'messages' => [
                ['role' => 'user', 'content' => 'hello'],
            ],
        ])
            ->assertStatus(503)
            ->assertJsonPath('code', 'lmstudio.disabled')
            ->assertJsonPath('status', 503);
    }

    public function test_request_validation_enforced(): void
    {
        config(['features.lmstudio.enabled' => true]);

        $this->postJson('/api/v1/ai/lmstudio/infer', [
            'messages' => [],
        ])->assertStatus(422);
    }
}
