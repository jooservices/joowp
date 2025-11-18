<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Modules\Core\Services\Cache\CacheHelper;

final class CacheClearWordPress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-wordpress
                            {--prefix=wp. : Cache key prefix to clear}
                            {--all : Clear all WordPress cache (wp.*)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear WordPress cache by prefix (default: wp.*)';

    /**
     * Create a new command instance
     *
     * @param  CacheRepository  $cache  The cache repository instance
     * @param  CacheHelper  $cacheHelper  The cache helper service instance
     */
    public function __construct(
        private readonly CacheRepository $cache,
        private readonly CacheHelper $cacheHelper
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command
     *
     * Clears WordPress cache entries matching the specified prefix.
     * Supports both specific prefix and clearing all WordPress cache (wp.*).
     *
     * @return int Command exit code (Command::SUCCESS)
     */
    public function handle(): int
    {
        $prefix = $this->option('all') ? 'wp.' : $this->option('prefix');

        $this->info("Clearing WordPress cache with prefix: {$prefix}");

        $cleared = $this->clearCacheByPrefix($prefix);

        if ($cleared > 0) {
            $this->info("Cleared {$cleared} cache entries.");

            return Command::SUCCESS;
        }

        $this->warn('No cache entries found with the specified prefix.');

        return Command::SUCCESS;
    }

    /**
     * Clear cache entries by prefix
     *
     * Handles different cache drivers appropriately:
     * - Database driver: Uses CacheHelper to query cache table directly
     * - Other drivers: Flushes all cache if prefix is 'wp.' or --all option used
     *
     * @param  string  $prefix  Cache key prefix to clear
     * @return int Number of cache entries cleared, or 1 if flushed all cache
     */
    private function clearCacheByPrefix(string $prefix): int
    {
        $driver = config('cache.default');

        if ($driver === 'database') {
            return $this->clearDatabaseCacheByPrefix($prefix);
        }

        // For array/file cache, we can't easily clear by prefix without iterating
        // For Redis/Memcached, we'd need to use SCAN or similar
        // For now, we'll use a simple approach: clear all if prefix is wp.*
        if ($prefix === 'wp.' || $this->option('all')) {
            $this->cache->flush();
            $this->info('Cache driver "' . $driver . '" - flushed all cache.');

            return 1; // Return 1 to indicate action taken
        }

        $this->warn("Cache driver '{$driver}' doesn't support prefix-based clearing. Use 'cache:clear' to clear all cache.");

        return 0;
    }

    /**
     * Clear database cache by prefix
     *
     * Delegates to CacheHelper to handle infrastructure concerns.
     * Handles exceptions gracefully and returns 0 on failure.
     *
     * @param  string  $prefix  Cache key prefix to clear
     * @return int Number of cache entries cleared, or 0 on error
     */
    private function clearDatabaseCacheByPrefix(string $prefix): int
    {
        try {
            return $this->cacheHelper->clearByPrefix($prefix);
        } catch (\Exception $e) {
            $this->error("Failed to clear cache: {$e->getMessage()}");

            return 0;
        }
    }
}
