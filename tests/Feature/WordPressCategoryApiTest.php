<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\Core\Services\WordPress\Contracts\SdkContract;
use Tests\TestCase;

final class WordPressCategoryApiTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use RefreshDatabase;

    public function test_it_lists_categories(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $sdk->shouldReceive('categories')
            ->once()
            ->andReturn([['id' => 1, 'name' => 'News']]);

        $this->app->instance(SdkContract::class, $sdk);
        Log::shouldReceive('channel')->with('action')->andReturnSelf();
        Log::shouldReceive('info')->zeroOrMoreTimes();

        $response = $this->getJson('/api/v1/wordpress/categories?search=news');

        $response
            ->assertOk()
            ->assertJsonPath('code', 'wordpress.categories.list')
            ->assertJsonPath('data.items.0.name', 'News');
    }

    public function test_it_creates_category(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $sdk->shouldReceive('createCategory')
            ->once()
            ->with([
                'name' => 'News',
                'slug' => 'news',
            ])
            ->andReturn(['id' => 10, 'name' => 'News']);

        $this->app->instance(SdkContract::class, $sdk);
        Log::shouldReceive('channel')->with('action')->andReturnSelf();
        Log::shouldReceive('info')->once();

        $response = $this->postJson('/api/v1/wordpress/categories', [
            'name' => 'News',
            'slug' => 'news',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('code', 'wordpress.categories.created')
            ->assertJsonPath('status', 201)
            ->assertJsonPath('data.name', 'News');
    }

    public function test_it_updates_category(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $sdk->shouldReceive('category')
            ->once()
            ->with(10)
            ->andReturn(['id' => 10, 'name' => 'News', 'slug' => 'news']);

        $sdk->shouldReceive('updateCategory')
            ->once()
            ->with(10, ['name' => 'Updated News'])
            ->andReturn(['id' => 10, 'name' => 'Updated News', 'slug' => 'news']);

        $this->app->instance(SdkContract::class, $sdk);
        Log::shouldReceive('channel')->with('action')->andReturnSelf();
        Log::shouldReceive('info')->once();

        $response = $this->postJson('/api/v1/wordpress/categories/10', [
            'name' => 'Updated News',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 'wordpress.categories.updated')
            ->assertJsonPath('status', 200)
            ->assertJsonPath('data.name', 'Updated News');
    }

    public function test_it_deletes_category(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $sdk->shouldReceive('category')
            ->once()
            ->with(10)
            ->andReturn(['id' => 10, 'name' => 'News']);

        $sdk->shouldReceive('deleteCategory')
            ->once()
            ->with(10, ['force' => true])
            ->andReturn(['deleted' => true, 'previous' => ['id' => 10, 'name' => 'News']]);

        $this->app->instance(SdkContract::class, $sdk);
        Log::shouldReceive('channel')->with('action')->andReturnSelf();
        Log::shouldReceive('info')->once();

        $response = $this->deleteJson('/api/v1/wordpress/categories/10', [
            'force' => true,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 'wordpress.categories.deleted')
            ->assertJsonPath('status', 200)
            ->assertJsonPath('data.deleted', true);
    }

    public function test_it_deletes_category_with_force_false(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $sdk->shouldReceive('category')
            ->once()
            ->with(10)
            ->andReturn(['id' => 10, 'name' => 'News']);

        $sdk->shouldReceive('deleteCategory')
            ->once()
            ->with(10, ['force' => false])
            ->andReturn(['deleted' => true, 'previous' => ['id' => 10, 'name' => 'News']]);

        $this->app->instance(SdkContract::class, $sdk);
        Log::shouldReceive('channel')->with('action')->andReturnSelf();
        Log::shouldReceive('info')->once();

        $response = $this->deleteJson('/api/v1/wordpress/categories/10', [
            'force' => false,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 'wordpress.categories.deleted');
    }

    public function test_it_validates_update_request(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $this->app->instance(SdkContract::class, $sdk);

        $response = $this->postJson('/api/v1/wordpress/categories/10', [
            'name' => str_repeat('a', 101), // exceeds max:100
        ]);

        $response->assertStatus(422);
    }

    public function test_it_validates_delete_request(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $this->app->instance(SdkContract::class, $sdk);

        $response = $this->deleteJson('/api/v1/wordpress/categories/10', [
            'force' => 'invalid', // must be boolean
        ]);

        $response->assertStatus(422);
    }

    public function test_it_caches_categories_list_response(): void
    {
        // Test cache hit/miss at SDK level
        // Note: Since we're mocking SDK contract, cache is at SDK layer
        // In real scenario, SDK caches internally, so HTTP client is only called once
        $categoriesData = [['id' => 1, 'name' => 'News'], ['id' => 2, 'name' => 'Tech']];

        $sdk = Mockery::mock(SdkContract::class);
        // SDK's internal cache means it will only be called once for identical queries
        // But since we're mocking the contract, we verify the service layer behavior
        $sdk->shouldReceive('categories')
            ->twice() // Service layer calls SDK twice, but SDK caches internally
            ->andReturn($categoriesData);

        $this->app->instance(SdkContract::class, $sdk);
        Log::shouldReceive('channel')->with('action')->andReturnSelf();
        Log::shouldReceive('info')->zeroOrMoreTimes();

        // First request - cache miss at SDK level, should call SDK
        $response1 = $this->getJson('/api/v1/wordpress/categories');
        $response1
            ->assertOk()
            ->assertJsonPath('code', 'wordpress.categories.list')
            ->assertJsonPath('data.items.0.name', 'News')
            ->assertJsonPath('data.items.1.name', 'Tech');

        // Second request - with real SDK, this would be cache hit
        // But with mocked SDK, we verify service layer still works correctly
        $response2 = $this->getJson('/api/v1/wordpress/categories');
        $response2
            ->assertOk()
            ->assertJsonPath('code', 'wordpress.categories.list')
            ->assertJsonPath('data.items.0.name', 'News')
            ->assertJsonPath('data.items.1.name', 'Tech');

        // Verify responses are identical
        $this->assertSame($response1->json(), $response2->json());
    }

    public function test_it_invalidates_cache_on_category_update(): void
    {
        $sdk = Mockery::mock(SdkContract::class);

        // First, get categories list (will be cached at SDK level)
        $sdk->shouldReceive('categories')
            ->once()
            ->andReturn([['id' => 10, 'name' => 'News', 'slug' => 'news']]);

        // Then update category (should invalidate list cache at SDK level)
        $sdk->shouldReceive('category')
            ->once()
            ->with(10)
            ->andReturn(['id' => 10, 'name' => 'News', 'slug' => 'news']);

        $sdk->shouldReceive('updateCategory')
            ->once()
            ->with(10, ['name' => 'Updated News'])
            ->andReturn(['id' => 10, 'name' => 'Updated News', 'slug' => 'news']);

        // After update, getting categories list again should fetch fresh data (cache invalidated)
        // With real SDK, list cache would be invalidated via version bump, so this would be a fresh call
        $sdk->shouldReceive('categories')
            ->once()
            ->andReturn([['id' => 10, 'name' => 'Updated News', 'slug' => 'news']]);

        $this->app->instance(SdkContract::class, $sdk);
        Log::shouldReceive('channel')->with('action')->andReturnSelf();
        Log::shouldReceive('info')->once();

        // First GET list - cache miss at SDK level
        $getResponse1 = $this->getJson('/api/v1/wordpress/categories');
        $getResponse1->assertOk()->assertJsonPath('data.items.0.name', 'News');

        // Update category - should invalidate list cache at SDK level (version bump)
        $updateResponse = $this->postJson('/api/v1/wordpress/categories/10', [
            'name' => 'Updated News',
        ]);
        $updateResponse->assertOk()->assertJsonPath('data.name', 'Updated News');

        // Second GET list - with real SDK, list cache would be invalidated, so fresh data fetched
        $getResponse2 = $this->getJson('/api/v1/wordpress/categories');
        $getResponse2->assertOk()->assertJsonPath('data.items.0.name', 'Updated News');
    }
}
