<?php

declare(strict_types=1);

namespace Modules\Core\Providers;

use App\Logging\ActionLogger;
use Illuminate\Support\ServiceProvider;
use JOOservices\Client\Contracts\HttpClientContract;
use JOOservices\Client\Factory\Factory;
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
            // Create jooclient Factory with LM Studio-specific configuration
            $factory = (new Factory())
                ->addOptions([
                    'timeout' => config('lmstudio.timeout', 30),
                    'connect_timeout' => config('lmstudio.connect_timeout', 10),
                    'verify' => config('lmstudio.verify_tls', true),
                ]);

            // Enable retries for LM Studio API calls
            $factory = $factory->enableRetries(
                config('lmstudio.max_retries', 2),
                1,
                500
            );

            /** @var HttpClientContract $httpClient */
            $httpClient = $factory->make();

            return new Sdk(
                actionLogger: $app->make(ActionLogger::class),
                httpClient: $httpClient,
                baseUrl: config('lmstudio.base_url'),
                apiKey: config('lmstudio.api_key'),
                timeout: config('lmstudio.timeout', 30),
                connectTimeout: config('lmstudio.connect_timeout', 10),
                maxRetries: config('lmstudio.max_retries', 2),
                verifyTls: config('lmstudio.verify_tls', true),
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
