<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\Core\Services\WordPress\Contracts\SdkContract;
use Tests\TestCase;

final class WordPressTagApiTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use RefreshDatabase;

    public function test_it_lists_tags(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $sdk->shouldReceive('tags')
            ->once()
            ->andReturn([['id' => 1, 'name' => 'Technology']]);

        $this->app->instance(SdkContract::class, $sdk);
        Log::shouldReceive('channel')->with('action')->andReturnSelf();
        Log::shouldReceive('info')->zeroOrMoreTimes();

        $response = $this->getJson('/api/v1/wordpress/tags?search=tech');

        $response
            ->assertOk()
            ->assertJsonPath('code', 'wordpress.tags.list')
            ->assertJsonPath('data.items.0.name', 'Technology');
    }

    public function test_it_creates_tag(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $sdk->shouldReceive('createTag')
            ->once()
            ->with([
                'name' => 'Technology',
                'slug' => 'technology',
            ])
            ->andReturn(['id' => 10, 'name' => 'Technology']);

        $this->app->instance(SdkContract::class, $sdk);
        Log::shouldReceive('channel')->with('action')->andReturnSelf();
        Log::shouldReceive('info')->once();

        $response = $this->postJson('/api/v1/wordpress/tags', [
            'name' => 'Technology',
            'slug' => 'technology',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('code', 'wordpress.tags.created')
            ->assertJsonPath('status', 201)
            ->assertJsonPath('data.name', 'Technology');
    }

    public function test_it_updates_tag(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $sdk->shouldReceive('tag')
            ->once()
            ->with(10)
            ->andReturn(['id' => 10, 'name' => 'Technology', 'slug' => 'technology']);

        $sdk->shouldReceive('updateTag')
            ->once()
            ->with(10, ['name' => 'Updated Technology'])
            ->andReturn(['id' => 10, 'name' => 'Updated Technology', 'slug' => 'technology']);

        $this->app->instance(SdkContract::class, $sdk);
        Log::shouldReceive('channel')->with('action')->andReturnSelf();
        Log::shouldReceive('info')->once();

        $response = $this->postJson('/api/v1/wordpress/tags/10', [
            'name' => 'Updated Technology',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 'wordpress.tags.updated')
            ->assertJsonPath('status', 200)
            ->assertJsonPath('data.name', 'Updated Technology');
    }

    public function test_it_deletes_tag(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $sdk->shouldReceive('tag')
            ->once()
            ->with(10)
            ->andReturn(['id' => 10, 'name' => 'Technology']);

        $sdk->shouldReceive('deleteTag')
            ->once()
            ->with(10, ['force' => true])
            ->andReturn(['deleted' => true, 'previous' => ['id' => 10, 'name' => 'Technology']]);

        $this->app->instance(SdkContract::class, $sdk);
        Log::shouldReceive('channel')->with('action')->andReturnSelf();
        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->zeroOrMoreTimes();

        $response = $this->deleteJson('/api/v1/wordpress/tags/10', [
            'force' => true,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 'wordpress.tags.deleted')
            ->assertJsonPath('status', 200)
            ->assertJsonPath('data.deleted', true);
    }

    public function test_it_validates_tag_name_required(): void
    {
        $response = $this->postJson('/api/v1/wordpress/tags', [
            'slug' => 'test',
        ]);

        $response->assertStatus(422);
    }

    public function test_it_validates_tag_name_max_length(): void
    {
        $response = $this->postJson('/api/v1/wordpress/tags', [
            'name' => str_repeat('a', 101),
        ]);

        $response->assertStatus(422);
    }

    public function test_it_validates_tag_slug_max_length(): void
    {
        $response = $this->postJson('/api/v1/wordpress/tags', [
            'name' => 'Test',
            'slug' => str_repeat('a', 121),
        ]);

        $response->assertStatus(422);
    }

    public function test_it_validates_tag_description_max_length(): void
    {
        $response = $this->postJson('/api/v1/wordpress/tags', [
            'name' => 'Test',
            'description' => str_repeat('a', 501),
        ]);

        $response->assertStatus(422);
    }

    public function test_it_validates_search_max_length(): void
    {
        $response = $this->getJson('/api/v1/wordpress/tags?search=' . str_repeat('a', 121));

        $response->assertStatus(422);
    }

    public function test_it_validates_per_page_range(): void
    {
        $response = $this->getJson('/api/v1/wordpress/tags?per_page=0');

        $response->assertStatus(422);
    }

    public function test_it_validates_per_page_max(): void
    {
        $response = $this->getJson('/api/v1/wordpress/tags?per_page=101');

        $response->assertStatus(422);
    }

    public function test_it_validates_page_min(): void
    {
        $response = $this->getJson('/api/v1/wordpress/tags?page=0');

        $response->assertStatus(422);
    }
}

