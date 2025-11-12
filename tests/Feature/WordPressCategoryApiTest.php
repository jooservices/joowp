<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Logging\ActionLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->app->instance(ActionLogger::class, Mockery::spy(ActionLogger::class));

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

        $logger = Mockery::mock(ActionLogger::class);
        $logger->shouldReceive('log');

        $this->app->instance(SdkContract::class, $sdk);
        $this->app->instance(ActionLogger::class, $logger);

        $response = $this->postJson('/api/v1/wordpress/categories', [
            'name' => 'News',
            'slug' => 'news',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 'wordpress.categories.created')
            ->assertJsonPath('status', 201)
            ->assertJsonPath('data.name', 'News');
    }
}
