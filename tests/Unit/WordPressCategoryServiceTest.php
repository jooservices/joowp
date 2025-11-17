<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Logging\ActionLogger;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\Core\Services\WordPress\Contracts\SdkContract;
use Modules\WordPress\Services\CategoryService;
use Tests\TestCase;

final class WordPressCategoryServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_it_lists_categories_with_filters(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $logger = new ActionLogger();

        $sdk->shouldReceive('categories')
            ->once()
            ->with([
                'search' => 'news',
                'per_page' => 10,
                'page' => 2,
                'context' => 'view',
                'orderby' => 'name',
                'order' => 'asc',
            ])
            ->andReturn([['id' => 1, 'name' => 'News']]);

        $service = new CategoryService($sdk, $logger);

        $result = $service->list([
            'search' => 'news',
            'per_page' => 10,
            'page' => 2,
        ]);

        self::assertSame([['id' => 1, 'name' => 'News']], $result);
    }

    public function test_it_logs_when_creating_categories(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $logger = new ActionLogger();

        $payload = [
            'name' => 'News',
            'slug' => 'news',
            'description' => 'Latest updates',
        ];

        $response = ['id' => 99, 'name' => 'News'];

        $sdk->shouldReceive('createCategory')
            ->once()
            ->with($payload)
            ->andReturn($response);

        Log::shouldReceive('channel')
            ->once()
            ->with('action')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Domain action recorded', Mockery::on(function (array $context): bool {
                return $context['operation'] === 'wordpress.category.created'
                    && $context['after'] === ['id' => 99, 'name' => 'News']
                    && $context['metadata']['source'] === 'wordpress';
            }));

        $service = new CategoryService($sdk, $logger);

        self::assertSame($response, $service->create($payload));
    }

    public function test_it_logs_when_updating_categories(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $logger = new ActionLogger();

        $categoryId = 99;
        $before = ['id' => $categoryId, 'name' => 'Old Name', 'slug' => 'old-slug'];
        $payload = ['name' => 'New Name'];
        $after = ['id' => $categoryId, 'name' => 'New Name', 'slug' => 'old-slug'];

        $sdk->shouldReceive('category')
            ->once()
            ->with($categoryId)
            ->andReturn($before);

        $sdk->shouldReceive('updateCategory')
            ->once()
            ->with($categoryId, $payload)
            ->andReturn($after);

        Log::shouldReceive('channel')
            ->once()
            ->with('action')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Domain action recorded', Mockery::on(function (array $context): bool {
                return $context['operation'] === 'wordpress.category.updated'
                    && $context['before'] === ['id' => 99, 'name' => 'Old Name', 'slug' => 'old-slug']
                    && $context['after'] === ['id' => 99, 'name' => 'New Name', 'slug' => 'old-slug']
                    && $context['metadata']['source'] === 'wordpress'
                    && $context['metadata']['category_id'] === 99;
            }));

        $service = new CategoryService($sdk, $logger);

        self::assertSame($after, $service->update($categoryId, $payload));
    }

    public function test_it_logs_when_deleting_categories(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $logger = new ActionLogger();

        $categoryId = 99;
        $before = ['id' => $categoryId, 'name' => 'Category Name'];
        $after = ['deleted' => true, 'previous' => $before];

        $sdk->shouldReceive('category')
            ->once()
            ->with($categoryId)
            ->andReturn($before);

        $sdk->shouldReceive('deleteCategory')
            ->once()
            ->with($categoryId, ['force' => true])
            ->andReturn($after);

        Log::shouldReceive('channel')
            ->once()
            ->with('action')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Domain action recorded', Mockery::on(function (array $context): bool {
                return $context['operation'] === 'wordpress.category.deleted'
                    && $context['before'] === ['id' => 99, 'name' => 'Category Name']
                    && $context['metadata']['source'] === 'wordpress'
                    && $context['metadata']['category_id'] === 99
                    && $context['metadata']['force'] === true;
            }));

        $service = new CategoryService($sdk, $logger);

        self::assertSame($after, $service->delete($categoryId, true));
    }

    public function test_it_deletes_with_force_false(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $logger = new ActionLogger();

        $categoryId = 99;
        $before = ['id' => $categoryId, 'name' => 'Category Name'];
        $after = ['deleted' => true, 'previous' => $before];

        $sdk->shouldReceive('category')
            ->once()
            ->with($categoryId)
            ->andReturn($before);

        $sdk->shouldReceive('deleteCategory')
            ->once()
            ->with($categoryId, ['force' => false])
            ->andReturn($after);

        Log::shouldReceive('channel')
            ->once()
            ->with('action')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Domain action recorded', Mockery::on(function (array $context): bool {
                return $context['metadata']['force'] === false;
            }));

        $service = new CategoryService($sdk, $logger);

        self::assertSame($after, $service->delete($categoryId, false));
    }

    public function test_it_filters_empty_strings_from_payload(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $logger = new ActionLogger();

        $payload = [
            'name' => 'News',
            'slug' => '',
            'description' => '   ',
            'parent' => null,
        ];

        $filteredPayload = ['name' => 'News'];

        $sdk->shouldReceive('createCategory')
            ->once()
            ->with($filteredPayload)
            ->andReturn(['id' => 1, 'name' => 'News']);

        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info')->once();

        $service = new CategoryService($sdk, $logger);

        $service->create($payload);
    }

    public function test_it_handles_list_with_default_filters(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $logger = new ActionLogger();

        $sdk->shouldReceive('categories')
            ->once()
            ->with([
                'per_page' => 20,
                'page' => 1,
                'context' => 'view',
                'orderby' => 'name',
                'order' => 'asc',
            ])
            ->andReturn([['id' => 1, 'name' => 'News']]);

        $service = new CategoryService($sdk, $logger);

        $result = $service->list([]);

        self::assertSame([['id' => 1, 'name' => 'News']], $result);
    }
}
