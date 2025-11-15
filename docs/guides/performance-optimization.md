# Performance Optimization Guide

Complete guide to optimizing application performance following performance-by-design principles.

> **Related Documentation:**
> - [Performance by Design Principles](../architecture/principles.md#18-performance-by-design) - Performance requirements
> - [Caching Strategy Plan](../plans/technical/2025-11-14-caching-strategy.md) - Caching implementation

---

## Overview

This project follows **Performance by Design** principle:
- **N+1 query prevention** - Database efficiency in all queries
- **Proper indexing** - Database indexes for all query patterns
- **Caching strategy** - Cache expensive operations
- **Async external calls** - No synchronous third-party calls in requests

---

## Database Optimization

### N+1 Query Prevention

#### ❌ BAD: N+1 Query Problem

```php
// ❌ BAD: N+1 queries
$products = Product::all();
foreach ($products as $product) {
    echo $product->category->name; // N+1 query here
}
```

#### ✅ GOOD: Eager Loading

```php
// ✅ GOOD: Eager load relationships
$products = Product::with('category')->get();
foreach ($products as $product) {
    echo $product->category->name; // No additional queries
}

// ✅ GOOD: Eager load multiple relationships
$products = Product::with(['category', 'tags', 'reviews'])->get();

// ✅ GOOD: Eager load nested relationships
$products = Product::with('category.parent')->get();
```

### Query Optimization

```php
// ✅ GOOD: Select only needed columns
$products = Product::select('id', 'name', 'price')
    ->where('active', true)
    ->get();

// ✅ GOOD: Use indexes
$products = Product::where('category_id', $categoryId)
    ->where('status', 'published')
    ->get();

// ✅ GOOD: Limit results
$products = Product::latest()
    ->limit(20)
    ->get();
```

### Database Indexing

```php
// Migration: Add indexes
Schema::table('products', function (Blueprint $table) {
    $table->index('category_id');
    $table->index('status');
    $table->index(['category_id', 'status']); // Composite index
});
```

---

## Caching Strategies

### Cache Expensive Operations

```php
use Illuminate\Support\Facades\Cache;

public function list(array $filters = []): array
{
    $cacheKey = 'products.list.' . md5(json_encode($filters));
    
    return Cache::remember($cacheKey, 3600, function () use ($filters) {
        return $this->repository->all($filters);
    });
}
```

### Cache Invalidation

```php
public function create(array $data): Product
{
    $product = $this->repository->create($data);
    
    // Invalidate related caches
    Cache::forget('products.list.*');
    Cache::forget("products.{$product->id}");
    
    return $product;
}
```

### Cache Tags (Redis)

```php
// Cache with tags
Cache::tags(['products', 'category-' . $categoryId])
    ->remember($key, 3600, function () {
        return $this->repository->all();
    });

// Invalidate by tag
Cache::tags(['products'])->flush();
```

---

## Async External Calls

### Queue Jobs for External APIs

```php
// ❌ BAD: Synchronous external call in request
public function store(StoreProductRequest $request): JsonResponse
{
    $product = $this->service->create($request->validated());
    
    // ❌ Blocks request until external API responds
    $this->externalSdk->syncProduct($product);
    
    return ProductResource::make($product)->response();
}

// ✅ GOOD: Queue external API call
public function store(StoreProductRequest $request): JsonResponse
{
    $product = $this->service->create($request->validated());
    
    // ✅ Non-blocking: dispatch to queue
    SyncProductJob::dispatch($product);
    
    return ProductResource::make($product)->response();
}
```

### Job Implementation

```php
<?php

declare(strict_types=1);

namespace Modules\Product\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Product\Models\Product;

final class SyncProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly Product $product,
    ) {}

    public function handle(): void
    {
        // External API call happens in background
        $this->externalSdk->syncProduct($this->product);
    }
}
```

---

## Frontend Optimization

### Lazy Loading Components

```vue
<script setup lang="ts">
import { defineAsyncComponent } from 'vue';

// Lazy load heavy components
const HeavyComponent = defineAsyncComponent(() => 
    import('@/components/HeavyComponent.vue')
);
</script>
```

### Code Splitting

```typescript
// Lazy load routes
const routes = [
    {
        path: '/products',
        component: () => import('@/Pages/Products/Index.vue'),
    },
];
```

---

## Best Practices

### ✅ DO

- **Eager load relationships** - Prevent N+1 queries
- **Use database indexes** - Optimize query performance
- **Cache expensive operations** - Reduce database load
- **Queue external calls** - Don't block requests
- **Select only needed columns** - Reduce data transfer
- **Limit query results** - Don't fetch unnecessary data
- **Use pagination** - For large datasets

### ❌ DON'T

- **Don't load all relationships** - Only eager load what you need
- **Don't fetch unnecessary columns** - Select only required fields
- **Don't make synchronous external calls** - Use queues
- **Don't cache everything** - Cache only expensive operations
- **Don't skip indexes** - Index all query patterns

---

## Related Documentation

- [Performance by Design Principles](../architecture/principles.md#18-performance-by-design) - Performance requirements
- [Caching Strategy Plan](../plans/technical/2025-11-14-caching-strategy.md) - Caching implementation

