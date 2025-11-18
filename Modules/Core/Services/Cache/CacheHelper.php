<?php

declare(strict_types=1);

namespace Modules\Core\Services\Cache;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\DB;

/**
 * Cache Helper Service
 * Handles cache operations including prefix-based clearing for database cache driver
 * Separates infrastructure concerns from business logic
 */
final class CacheHelper
{
    public function __construct(
        private readonly CacheRepository $cache
    ) {
    }

    /**
     * Clear cache entries by prefix
     * Handles database cache driver by querying cache table directly
     * Accounts for Laravel's configured cache key prefix
     *
     * @param  string  $prefix  Cache key prefix (e.g., 'wp.post.1.')
     * @return int Number of cache entries cleared
     */
    public function clearByPrefix(string $prefix): int
    {
        $driver = config('cache.default');

        if ($driver !== 'database') {
            // For other cache drivers (array, file, redis), we can't easily clear by prefix
            // without wildcard support. The cache will expire naturally via TTL.
            // For production with Redis, consider implementing cache tags for better invalidation.
            return 0;
        }

        $table = $this->getCacheTableName();

        // Laravel automatically prepends cache prefix to keys when storing in database
        // We need to account for this prefix when querying
        $cachePrefix = config('cache.prefix', '');
        $searchPrefix = $cachePrefix !== '' ? $cachePrefix . $prefix : $prefix;

        return (int) DB::table($table)
            ->where('key', 'like', $searchPrefix . '%')
            ->delete();
    }

    /**
     * Get cache table name from configuration
     */
    private function getCacheTableName(): string
    {
        $tableValue = config('cache.stores.database.table', 'cache');

        return is_string($tableValue) ? $tableValue : 'cache';
    }
}
