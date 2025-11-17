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
}
