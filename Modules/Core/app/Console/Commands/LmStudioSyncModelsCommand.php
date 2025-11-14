<?php

declare(strict_types=1);

namespace Modules\Core\Console\Commands;

use App\Logging\ActionLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Services\LmStudio\Contracts\SdkContract;
use Modules\Core\Services\LmStudio\DTO\ListModelsFilter;
use Modules\Core\Services\LmStudio\DTO\ModelSummary;

final class LmStudioSyncModelsCommand extends Command
{
    protected $signature = 'lmstudio:models-sync
        {--owned-by= : Filter by owner}
        {--status= : Filter by model status}
        {--limit= : Limit the number of returned models}
        {--output=lmstudio/models.json : Storage path for the cached model list}
        {--json : Echo the synced models to STDOUT}';

    protected $description = 'Fetch LM Studio models and persist the catalog locally.';

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

        $filter = $this->buildFilter();

        try {
            $sdk = $this->resolveSdk();
            $models = $sdk->listModels($filter);
        } catch (\Throwable $exception) {
            $this->error('Failed to sync models: ' . $exception->getMessage());
            $this->actionLogger->log(
                operation: 'lmstudio.models_sync',
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

        $serialized = array_map(
            static fn (ModelSummary $model) => $model->toArray(),
            $models,
        );

        $disk = Storage::disk('local');
        $output = ltrim((string) $this->option('output'), '/');

        $disk->put($output, json_encode([
            'synced_at' => now()->toIso8601String(),
            'models' => $serialized,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info(sprintf(
            'Synced %d models to %s',
            count($serialized),
            storage_path('app/' . $output),
        ));

        if ($this->option('json')) {
            $this->line(json_encode($serialized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        $this->actionLogger->log(
            operation: 'lmstudio.models_sync',
            actor: null,
            before: [],
            after: [],
            metadata: [
                'success' => true,
                'model_count' => count($serialized),
                'output' => $output,
                'filters' => $filter?->toArray() ?? [],
            ],
        );

        return self::SUCCESS;
    }

    private function buildFilter(): ?ListModelsFilter
    {
        $filter = new ListModelsFilter(
            ownedBy: $this->option('owned-by') ?: null,
            status: $this->option('status') ?: null,
            limit: $this->option('limit') !== null ? (int) $this->option('limit') : null,
            cursor: null,
        );

        return $filter->isEmpty() ? null : $filter;
    }

    private function resolveSdk(): SdkContract
    {
        return app(SdkContract::class);
    }
}
