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

        $sdk = Mockery::mock(SdkContract::class)->shouldIgnoreMissing();
        $this->app->instance(SdkContract::class, $sdk);

        $service = Mockery::mock(ChatInferenceService::class)->shouldIgnoreMissing();
        $this->app->instance(ChatInferenceService::class, $service);
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
        $sdk = Mockery::mock(SdkContract::class);
        $sdk->shouldReceive('listModels')->zeroOrMoreTimes();
        $this->app->instance(SdkContract::class, $sdk);

        $service = Mockery::mock(ChatInferenceService::class);
        $service->shouldReceive('start')
            ->once()
            ->andReturn([
                'job_id' => 'job-123',
                'model' => 'mistral',
                'created' => 1731614400,
            ]);

        $this->app->instance(ChatInferenceService::class, $service);

        $payload = [
            'model' => 'mistral',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello'],
            ],
        ];

        $this->postJson('/api/v1/ai/lmstudio/infer', $payload)
            ->assertStatus(202)
            ->assertJsonPath('status', 202)
            ->assertJsonPath('data.job_id', 'job-123')
            ->assertJsonPath('code', 'lmstudio.infer.accepted');
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
