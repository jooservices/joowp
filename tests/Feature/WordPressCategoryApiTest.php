<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Logging\ActionLogger;
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
}
