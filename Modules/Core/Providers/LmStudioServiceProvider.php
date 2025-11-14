<?php

declare(strict_types=1);

namespace Modules\Core\Providers;

use App\Logging\ActionLogger;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Services\LmStudio\Contracts\SdkContract;
use Modules\Core\Services\LmStudio\Sdk;

final class LmStudioServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../../config/lmstudio.php', 'lmstudio');

        if (! $this->featureEnabled()) {
            return;
        }

        $this->app->singleton(SdkContract::class, function ($app) {
            return new Sdk(
                actionLogger: $app->make(ActionLogger::class),
                baseUrl: config('lmstudio.base_url'),
                apiKey: config('lmstudio.api_key'),
                timeout: config('lmstudio.timeout'),
                connectTimeout: config('lmstudio.connect_timeout'),
                maxRetries: config('lmstudio.max_retries'),
                verifyTls: config('lmstudio.verify_tls'),
                logChannel: config('lmstudio.log_channel', 'external'),
            );
        });
    }

    public function boot(): void
    {
        if (! $this->featureEnabled()) {
            return;
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../../config/lmstudio.php' => config_path('lmstudio.php'),
            ], 'config');
        }
    }

    private function featureEnabled(): bool
    {
        return (bool) config('features.lmstudio.enabled', false);
    }
}
