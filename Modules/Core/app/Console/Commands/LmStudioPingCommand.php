<?php

declare(strict_types=1);

namespace Modules\Core\Console\Commands;

use App\Logging\ActionLogger;
use Illuminate\Console\Command;
use Modules\Core\Services\LmStudio\Contracts\SdkContract;

final class LmStudioPingCommand extends Command
{
    protected $signature = 'lmstudio:ping {--json : Output JSON payload}';

    protected $description = 'Check LM Studio health status from the CLI.';

    public function __construct(private readonly ActionLogger $actionLogger)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! config('features.lmstudio.enabled', false)) {
            $this->warn('LM Studio integration is disabled via feature flag.');

            return self::FAILURE;
        }

        try {
            $sdk = $this->resolveSdk();
            $status = $sdk->healthCheck();
        } catch (\Throwable $exception) {
            $this->error('Health check failed: ' . $exception->getMessage());
            $this->actionLogger->log(
                operation: 'lmstudio.ping',
                actor: null,
                before: [],
                after: [],
                metadata: [
                    'success' => false,
                    'error' => $exception->getMessage(),
                ],
            );

            return self::FAILURE;
        }

        $payload = $status->toArray();

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->info('LM Studio health check succeeded:');
            $this->table(
                ['Status', 'LM Studio Version', 'API Version', 'Models Loaded', 'Uptime (ms)'],
                [[
                    $payload['status'] ?? 'unknown',
                    $payload['lmstudio_version'] ?? 'n/a',
                    $payload['api_version'] ?? 'n/a',
                    $payload['models_loaded'] ?? 'n/a',
                    $payload['uptime_ms'] ?? 'n/a',
                ]]
            );
        }

        $this->actionLogger->log(
            operation: 'lmstudio.ping',
            actor: null,
            before: [],
            after: [],
            metadata: [
                'success' => true,
                'status' => $payload['status'] ?? 'unknown',
            ],
        );

        return self::SUCCESS;
    }

    private function resolveSdk(): SdkContract
    {
        return app(SdkContract::class);
    }
}
