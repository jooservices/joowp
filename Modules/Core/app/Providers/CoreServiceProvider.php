<?php

declare(strict_types=1);

namespace Modules\Core\Providers;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use JOOservices\Client\Contracts\HttpClientContract;
use JOOservices\Client\Factory\Factory;
use Modules\Core\Console\Commands\LmStudioPingCommand;
use Modules\Core\Console\Commands\LmStudioSyncModelsCommand;
use Modules\Core\Services\WordPress\Contracts\SdkContract;
use Modules\Core\Services\WordPress\Sdk;
use Modules\WordPress\Models\WpToken;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class CoreServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Core';

    protected string $nameLower = 'core';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(LmStudioServiceProvider::class);
        $this->registerWordPressBindings();
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            LmStudioPingCommand::class,
            LmStudioSyncModelsCommand::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower . '.' . $config_key);

                    // Remove duplicated adjacent segments
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->merge_config_from($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Merge config from the given path recursively.
     */
    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        config([$key => array_replace_recursive($existing, $module_config)]);
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/' . $this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace') . '\\' . $this->name . '\\View\\Components', $this->nameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            SdkContract::class,
        ];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->nameLower)) {
                $paths[] = $path . '/modules/' . $this->nameLower;
            }
        }

        return $paths;
    }

    private function registerWordPressBindings(): void
    {
        $this->app->singleton(SdkContract::class, function ($app) {
            /** @var ConfigRepository $configRepository */
            $configRepository = $app->make(ConfigRepository::class);

            $defaults = [
                'base_uri' => rtrim((string) env('WP_URL', 'https://soulevil.com'), '/') . '/wp-json/',
                'timeout' => (float) env('WORDPRESS_API_TIMEOUT', 10.0),
                'user_agent' => env('WORDPRESS_API_USER_AGENT', 'CoreWordPressSdk/1.0'),
                'namespace' => env('WORDPRESS_API_NAMESPACE', 'wp/v2'),
            ];

            $legacyConfig = (array) $configRepository->get('core.wordpress', []);
            $moduleConfig = (array) $configRepository->get('wordpress.api', []);
            $config = array_replace($defaults, $legacyConfig, $moduleConfig);

            $baseUri = (string) ($config['base_uri'] ?? '');
            $normalizedBaseUri = $baseUri === '' ? '' : rtrim($baseUri, '/') . '/';

            // Create jooclient Factory with WordPress-specific configuration
            $factory = (new Factory())
                ->addOptions([
                    'base_uri' => $normalizedBaseUri,
                    'timeout' => $config['timeout'] ?? 10.0,
                    'headers' => [
                        'Accept' => 'application/json',
                        'User-Agent' => $config['user_agent'] ?? 'CoreWordPressSdk/1.0',
                    ],
                ]);

            // Enable retries for external WordPress API calls (3 retries, exponential backoff)
            $factory = $factory->enableRetries(3, 2, 500);

            // Enable circuit breaker for WordPress API (configurable via env)
            // Circuit breaker configuration is read from config/jooclient.php
            // Enable via: JOOCLIENT_CIRCUIT_BREAKER_ENABLED=true
            if (config('jooclient.circuit_breaker.enabled', false)) {
                $factory = $factory->enableCircuitBreaker();
            }

            /** @var HttpClientContract $client */
            $client = $factory->make();

            $tokenResolver = static fn (): ?string => WpToken::query()
                ->latest('updated_at')
                ->value('token');

            /** @var \Illuminate\Contracts\Cache\Repository $cache */
            $cache = $app->make(\Illuminate\Contracts\Cache\Repository::class);

            /** @var \Modules\Core\Services\Cache\CacheHelper $cacheHelper */
            $cacheHelper = $app->make(\Modules\Core\Services\Cache\CacheHelper::class);

            return new Sdk(
                client: $client,
                cache: $cache,
                cacheHelper: $cacheHelper,
                tokenResolver: $tokenResolver,
                namespace: $config['namespace'] ?? 'wp/v2'
            );
        });
    }
}
