# Caching Strategy Implementation Plan

**Created:** 2025-11-12  
**Status:** Ready  
**Priority:** P1 (High - Performance optimization)  
**Updated:** 2025-11-14  
**Estimated Time:** 2-3 hours

> **Note:** This plan contains detailed implementation guides. Tasks need to be converted to checkbox format for proper tracking during implementation.

## Overview

Implement comprehensive caching strategy to improve application performance, reduce database queries, and minimize external API calls (especially WordPress REST API). Focus on caching WordPress data, database queries, and computed results.

## Goals

1. **Reduce WordPress API calls** - Cache WordPress posts, categories, media, tags
2. **Minimize database queries** - Cache eloquent queries and relationships
3. **Improve response times** - Serve cached data for frequently accessed resources
4. **Invalidate smartly** - Clear cache on mutations, preserve on reads

## Cache Layers

### Layer 1: WordPress API Response Cache
**Purpose:** Reduce external API calls to WordPress  
**Driver:** Database or Redis (configurable)  
**TTL:** 5-60 minutes depending on data type

**What to cache:**
- WordPress posts list (5 min)
- Individual post data (10 min)
- Categories list (30 min)
- Tags list (30 min)
- Media items (15 min)
- User profile data (60 min)

### Layer 2: Database Query Cache
**Purpose:** Reduce database hits for common queries  
**Driver:** Database or Redis  
**TTL:** 1-10 minutes

**What to cache:**
- User lookup queries (5 min)
- WordPress token validation (1 min)
- Relationship queries (3 min)

### Layer 3: Computed Results Cache
**Purpose:** Cache expensive computations  
**Driver:** Database or Redis  
**TTL:** Variable based on computation cost

**What to cache:**
- Aggregated statistics (10 min)
- Search results (2 min)
- Filtered/sorted lists (3 min)

## Implementation Tasks

### Phase 1: WordPress SDK Caching (1 hour)

#### Task 1.1: Add Cache Dependency
**File:** `Modules/Core/Services/WordPress/Sdk.php`

**Add cache to constructor:**
```php
use Illuminate\Contracts\Cache\Repository as CacheRepository;

public function __construct(
    private readonly Client $client,
    private readonly Closure $tokenResolver,
    private readonly CacheRepository $cache,
    private readonly string $namespace = 'wp/v2',
) {}
```

#### Task 1.2: Implement Cached Methods
**Pattern for each WordPress SDK method:**
```php
public function posts(array $query = []): array
{
    $cacheKey = 'wp.posts.' . md5(json_encode($query));
    
    return $this->cache->remember($cacheKey, now()->addMinutes(5), function () use ($query) {
        return $this->get('posts', $query);
    });
}

public function post(int $id): array
{
    $cacheKey = "wp.post.{$id}";
    
    return $this->cache->remember($cacheKey, now()->addMinutes(10), function () use ($id) {
        return $this->get("posts/{$id}");
    });
}
```

**Methods to cache:**
- `posts()` - 5 min
- `post($id)` - 10 min
- `categories()` - 30 min
- `category($id)` - 30 min
- `tags()` - 30 min
- `media()` - 15 min
- `users()` - 60 min

#### Task 1.3: Cache Invalidation
**Create helper methods:**
```php
private function invalidateCache(string $pattern): void
{
    // For database cache driver
    Cache::tags(['wordpress'])->flush();
    
    // For Redis, use pattern matching
    // $this->cache->forget($pattern);
}

public function invalidatePostCache(int $id): void
{
    $this->cache->forget("wp.post.{$id}");
    $this->cache->forget('wp.posts.*'); // Invalidate all posts lists
}

public function invalidateCategoryCache(int $id): void
{
    $this->cache->forget("wp.category.{$id}");
    $this->cache->forget('wp.categories'); // Invalidate categories list
}
```

### Phase 2: Service Layer Caching (45 mins)

#### Task 2.1: Cache in CategoryService
**File:** `Modules/Core/Services/WordPress/CategoryService.php`

**Add cache to frequently called methods:**
```php
use Illuminate\Support\Facades\Cache;

public function getAll(array $filters = []): array
{
    $cacheKey = 'categories.all.' . md5(json_encode($filters));
    
    return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($filters) {
        return $this->sdk->categories($filters);
    });
}

public function create(array $data): array
{
    $category = $this->sdk->createCategory($data);
    
    // Invalidate cache after mutation
    Cache::forget('categories.all.*');
    
    return $category;
}

public function update(int $id, array $data): array
{
    $category = $this->sdk->updateCategory($id, $data);
    
    // Invalidate specific + list cache
    Cache::forget("category.{$id}");
    Cache::forget('categories.all.*');
    
    return $category;
}

public function delete(int $id): void
{
    $this->sdk->deleteCategory($id);
    
    // Invalidate cache
    Cache::forget("category.{$id}");
    Cache::forget('categories.all.*');
}
```

### Phase 3: Configuration & Drivers (30 mins)

#### Task 3.1: Configure Cache Driver
**File:** `config/cache.php`

**Already configured, verify settings:**
```php
'default' => env('CACHE_STORE', 'database'),

'stores' => [
    'database' => [
        'driver' => 'database',
        'connection' => env('DB_CACHE_CONNECTION'),
        'table' => env('DB_CACHE_TABLE', 'cache'),
        'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),
        'lock_table' => env('DB_CACHE_LOCK_TABLE'),
    ],
    
    'redis' => [
        'driver' => 'redis',
        'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
        'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
    ],
],
```

#### Task 3.2: Add Cache Configuration to .env
**File:** `.env.example` and `.env`

```bash
# Cache Configuration
CACHE_STORE=database  # or 'redis' for production
DB_CACHE_CONNECTION=sqlite
DB_CACHE_TABLE=cache

# Redis (if using redis driver)
REDIS_CACHE_CONNECTION=cache
```

#### Task 3.3: Run Cache Table Migration
```bash
php artisan cache:table
php artisan migrate
```

### Phase 4: Cache Tags (Optional - for Redis only) (30 mins)

**If using Redis, implement tagged caching:**
```php
// Tag-based cache for better invalidation
Cache::tags(['wordpress', 'posts'])->put($key, $value, $ttl);
Cache::tags(['wordpress', 'categories'])->put($key, $value, $ttl);

// Invalidate all WordPress cache
Cache::tags(['wordpress'])->flush();

// Invalidate only posts
Cache::tags(['posts'])->flush();
```

**Note:** Database driver doesn't support tags, use key patterns instead.

## Cache Key Patterns

**Consistent naming convention:**
```
{domain}.{resource}.{identifier}?{params_hash}

Examples:
- wp.posts.md5({query_params})
- wp.post.123
- wp.categories
- wp.category.456
- categories.all.md5({filters})
- user.tokens.789
```

## Cache Invalidation Strategy

### On Mutations (Create/Update/Delete):
1. **Invalidate specific item**: `Cache::forget("wp.post.{$id}")`
2. **Invalidate list caches**: `Cache::forget('wp.posts.*')` or use tags
3. **Log invalidation**: Action logger for audit trail

### On Reads:
1. **Check cache first**: `Cache::get($key)`
2. **If miss, fetch + store**: `Cache::put($key, $data, $ttl)`
3. **Return data**

### Manual Invalidation:
**Add artisan command:**
```php
// app/Console/Commands/CacheClear.php
php artisan cache:clear-wordpress  # Clear all WordPress cache
php artisan cache:clear-posts      # Clear only posts
php artisan cache:clear-categories # Clear only categories
```

## Testing Strategy

### Unit Tests
```php
public function test_posts_are_cached(): void
{
    Cache::shouldReceive('remember')
        ->once()
        ->with('wp.posts.*', anything(), anything())
        ->andReturn([]);
    
    $this->sdk->posts();
}

public function test_cache_invalidated_on_delete(): void
{
    Cache::shouldReceive('forget')
        ->once()
        ->with('wp.post.123');
    
    $this->service->delete(123);
}
```

### Integration Tests
```php
public function test_second_request_uses_cache(): void
{
    // First request - cache miss
    $response1 = $this->get('/api/v1/posts');
    $this->assertDatabaseHas('cache', ['key' => 'wp.posts.*']);
    
    // Second request - cache hit
    $response2 = $this->get('/api/v1/posts');
    $this->assertEquals($response1->json(), $response2->json());
}
```

## Performance Monitoring

**Add cache hit/miss logging:**
```php
if (Cache::has($key)) {
    Log::debug('Cache hit', ['key' => $key]);
} else {
    Log::debug('Cache miss', ['key' => $key]);
}
```

**Monitor cache effectiveness:**
```bash
# Check cache table size
sqlite3 database/database.sqlite "SELECT COUNT(*) FROM cache;"

# Check cache hit ratio (add to monitoring dashboard)
# hits / (hits + misses) * 100
```

## Acceptance Criteria

- [ ] WordPress SDK methods use cache with appropriate TTLs
- [ ] Cache invalidated on all mutations (create/update/delete)
- [ ] Service layer implements caching for frequently called methods
- [ ] Cache configuration added to .env.example
- [ ] Cache table migration executed
- [ ] Unit tests verify caching behavior
- [ ] Integration tests confirm cache hit/miss scenarios
- [ ] Cache key naming follows consistent pattern
- [ ] Manual cache clear commands available
- [ ] No stale data served after mutations

## Performance Targets

**Before caching:**
- WordPress API calls: ~10-50 per page load
- Response time: 200-500ms
- Database queries: 20-50 per request

**After caching:**
- WordPress API calls: 0-2 per page load (cache hits)
- Response time: 50-150ms (cached)
- Database queries: 5-10 per request

**Expected improvement:**
- 50-70% reduction in response time
- 80-90% reduction in external API calls
- 40-60% reduction in database queries

## Future Enhancements

1. **Cache warming** - Pre-populate cache for common queries
2. **Cache versioning** - Support cache version bumps for deployments
3. **Distributed cache** - Redis cluster for multi-server setups
4. **Cache analytics** - Dashboard showing hit rates, sizes, patterns
5. **Adaptive TTL** - Adjust TTL based on data change frequency

## References

- [Laravel Cache Documentation](https://laravel.com/docs/11.x/cache)
- [Engineering Principles](../principles.md)
- [WordPress SDK Guide](../guides/core-wordpress-sdk.md)
