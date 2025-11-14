<?php

declare(strict_types=1);

namespace Modules\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Services\LmStudio\Contracts\SdkContract;
use Modules\Core\Services\LmStudio\Sdk;

final class LmStudioServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../../config/lmstudio.php', 'lmstudio');

        $this->app->singleton(SdkContract::class, function ($app) {
            return new Sdk(
                baseUrl: config('lmstudio.base_url'),
                apiKey: config('lmstudio.api_key'),
                timeout: config('lmstudio.timeout'),
                connectTimeout: config('lmstudio.connect_timeout'),
                maxRetries: config('lmstudio.max_retries'),
                verifyTls: config('lmstudio.verify_tls'),
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../../config/lmstudio.php' => config_path('lmstudio.php'),
            ], 'config');
        }
    }
}
