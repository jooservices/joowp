<?php

declare(strict_types=1);

namespace Modules\WordPress\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

final class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the module.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [];

    /**
     * Indicates if events should be discovered automatically.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;
}
