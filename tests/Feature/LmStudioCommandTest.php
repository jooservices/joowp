<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Logging\ActionLogger;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Modules\Core\Services\LmStudio\Contracts\SdkContract;
use Modules\Core\Services\LmStudio\DTO\HealthStatus;
use Modules\Core\Services\LmStudio\DTO\ModelSummary;
use Tests\TestCase;

final class LmStudioCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_ping_command_outputs_table(): void
    {
        config(['features.lmstudio.enabled' => true]);

        $sdk = Mockery::mock(SdkContract::class);
        $sdk->shouldReceive('healthCheck')
            ->once()
            ->andReturn(new HealthStatus('ok', '0.2.21', 'v1', 3, 1000, []));
        $this->app->instance(SdkContract::class, $sdk);

        $logger = Mockery::mock(ActionLogger::class);
        $logger->shouldReceive('log')->once();
        $this->app->instance(ActionLogger::class, $logger);

        $this->artisan('lmstudio:ping')
            ->expectsOutput('LM Studio health check succeeded:')
            ->assertExitCode(0);
    }

    public function test_ping_command_outputs_json_when_requested(): void
    {
        config(['features.lmstudio.enabled' => true]);

        $sdk = Mockery::mock(SdkContract::class);
        $payload = new HealthStatus('ok', '0.2.22', 'v1', 2, 500, []);
        $sdk->shouldReceive('healthCheck')->once()->andReturn($payload);
        $this->app->instance(SdkContract::class, $sdk);

        $logger = Mockery::mock(ActionLogger::class);
        $logger->shouldReceive('log')->once();
        $this->app->instance(ActionLogger::class, $logger);

        $expected = json_encode($payload->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $this->artisan('lmstudio:ping --json')
            ->expectsOutput($expected)
            ->assertExitCode(0);
    }

    public function test_models_sync_command_writes_cache_file(): void
    {
        config(['features.lmstudio.enabled' => true]);

        $sdk = Mockery::mock(SdkContract::class);
        $sdk->shouldReceive('listModels')
            ->with(Mockery::type('Modules\Core\Services\LmStudio\DTO\ListModelsFilter'))
            ->once()
            ->andReturn([
                new ModelSummary('mistral', 'lmstudio', 1, 'ready', []),
                new ModelSummary('llama', 'lmstudio', 2, 'ready', []),
            ]);
        $this->app->instance(SdkContract::class, $sdk);

        $logger = Mockery::mock(ActionLogger::class);
        $logger->shouldReceive('log')->once();
        $this->app->instance(ActionLogger::class, $logger);

        Storage::fake('local');

        $this->artisan('lmstudio:models-sync --owned-by=lmstudio --status=ready --limit=5 --output=snapshots/models.json')
            ->expectsOutputToContain('Synced 2 models')
            ->assertExitCode(0);

        Storage::disk('local')->assertExists('snapshots/models.json');
        $contents = json_decode(Storage::disk('local')->get('snapshots/models.json'), true);

        $this->assertArrayHasKey('synced_at', $contents);
        $this->assertCount(2, $contents['models']);
    }

    public function test_models_sync_command_handles_failures(): void
    {
        config(['features.lmstudio.enabled' => true]);

        $sdk = Mockery::mock(SdkContract::class);
        $sdk->shouldReceive('listModels')->once()->andThrow(new \RuntimeException('boom'));
        $this->app->instance(SdkContract::class, $sdk);

        $logger = Mockery::mock(ActionLogger::class);
        $logger->shouldReceive('log')->once();
        $this->app->instance(ActionLogger::class, $logger);

        $this->artisan('lmstudio:models-sync')
            ->expectsOutput('Failed to sync models: boom')
            ->assertExitCode(1);
    }

    public function test_commands_abort_when_feature_disabled(): void
    {
        config(['features.lmstudio.enabled' => false]);

        $this->artisan('lmstudio:ping')
            ->expectsOutput('LM Studio integration is disabled via feature flag.')
            ->assertExitCode(1);

        $this->artisan('lmstudio:models-sync')
            ->expectsOutput('LM Studio integration is disabled via feature flag.')
            ->assertExitCode(1);
    }
}
