<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $_SERVER['APP_DISABLE_ENV_LOADING'] = $_SERVER['APP_DISABLE_ENV_LOADING'] ?? 'true';
        $_ENV['APP_DISABLE_ENV_LOADING'] = $_ENV['APP_DISABLE_ENV_LOADING'] ?? 'true';

        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
