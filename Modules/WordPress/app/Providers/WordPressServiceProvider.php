<?php

declare(strict_types=1);

namespace Modules\WordPress\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class WordPressServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'WordPress';

    protected string $nameLower = 'wordpress';

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
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        // $this->commands([]);
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
        $configuredPath = config('modules.paths.generator.config.path', 'config');
        $generatorPath = is_string($configuredPath) ? $configuredPath : 'config';
        $configPath = module_path($this->name, $generatorPath);

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $pathname = $file->getPathname();

                $config = str_replace($configPath . DIRECTORY_SEPARATOR, '', $pathname);
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

                $this->publishes([$pathname => config_path($config)], 'config');
                $this->merge_config_from($pathname, $key);
            }
        }
    }

    /**
     * Merge config from the given path recursively.
     */
    protected function merge_config_from(string $path, string $key): void
    {
        $existing = (array) config($key, []);
        $module_config = require $path;
        if (! is_array($module_config)) {
            $module_config = [];
        }

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
    /**
     * @return array<int, class-string>
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    private function getPublishableViewPaths(): array
    {
        $paths = [];
        $viewPaths = (array) config('view.paths', []);
        foreach ($viewPaths as $path) {
            if (is_dir($path . '/modules/' . $this->nameLower)) {
                $paths[] = $path . '/modules/' . $this->nameLower;
            }
        }

        return $paths;
    }
}
