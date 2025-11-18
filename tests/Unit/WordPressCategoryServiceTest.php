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

    public function test_it_returns_eligible_parents_excluding_self_and_descendants(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $logger = new ActionLogger();

        // Category 1 (root) -> Category 2 (child of 1) -> Category 3 (child of 2)
        $allCategories = [
            ['id' => 1, 'name' => 'Tech', 'slug' => 'tech', 'parent' => 0],
            ['id' => 2, 'name' => 'Programming', 'slug' => 'programming', 'parent' => 1],
            ['id' => 3, 'name' => 'JavaScript', 'slug' => 'javascript', 'parent' => 2],
            ['id' => 4, 'name' => 'Design', 'slug' => 'design', 'parent' => 0],
        ];

        // Mock pagination: page 1 returns all categories (less than 100, so no page 2)
        $sdk->shouldReceive('categories')
            ->once()
            ->with([
                'per_page' => 100,
                'page' => 1,
                'orderby' => 'name',
                'order' => 'asc',
            ])
            ->andReturn($allCategories);

        $service = new CategoryService($sdk, $logger);

        // When editing category 2, exclude 2 and 3 (descendant), but include 1 and 4
        $result = $service->eligibleParents(2);

        self::assertCount(2, $result);
        $ids = array_column($result, 'id');
        self::assertContains(1, $ids); // Tech (root)
        self::assertContains(4, $ids); // Design (root)
        self::assertNotContains(2, $ids); // Programming (self)
        self::assertNotContains(3, $ids); // JavaScript (descendant)
    }

    public function test_it_filters_trashed_categories_by_default(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $logger = new ActionLogger();

        $allCategories = [
            ['id' => 1, 'name' => 'Active', 'slug' => 'active', 'parent' => 0, 'status' => 'publish'],
            ['id' => 2, 'name' => 'Trashed', 'slug' => 'trashed', 'parent' => 0, 'status' => 'trash'],
        ];

        $sdk->shouldReceive('categories')
            ->once()
            ->andReturn($allCategories);

        $service = new CategoryService($sdk, $logger);

        $result = $service->eligibleParents(null, false);

        self::assertCount(1, $result);
        self::assertSame(1, $result[0]['id']);
    }

    public function test_it_includes_trashed_categories_when_requested(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $logger = new ActionLogger();

        $allCategories = [
            ['id' => 1, 'name' => 'Active', 'slug' => 'active', 'parent' => 0, 'status' => 'publish'],
            ['id' => 2, 'name' => 'Trashed', 'slug' => 'trashed', 'parent' => 0, 'status' => 'trash'],
        ];

        $sdk->shouldReceive('categories')
            ->once()
            ->andReturn($allCategories);

        $service = new CategoryService($sdk, $logger);

        $result = $service->eligibleParents(null, true);

        self::assertCount(2, $result);
        $ids = array_column($result, 'id');
        self::assertContains(1, $ids);
        self::assertContains(2, $ids);
    }

    public function test_it_calculates_depth_correctly(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $logger = new ActionLogger();

        $allCategories = [
            ['id' => 1, 'name' => 'Root', 'slug' => 'root', 'parent' => 0],
            ['id' => 2, 'name' => 'Level 1', 'slug' => 'level-1', 'parent' => 1],
            ['id' => 3, 'name' => 'Level 2', 'slug' => 'level-2', 'parent' => 2],
        ];

        $sdk->shouldReceive('categories')
            ->once()
            ->andReturn($allCategories);

        $service = new CategoryService($sdk, $logger);

        $result = $service->eligibleParents();

        $depthMap = [];
        foreach ($result as $item) {
            $depthMap[$item['id']] = $item['depth'];
        }

        self::assertSame(0, $depthMap[1]); // Root
        self::assertSame(1, $depthMap[2]); // Level 1
        self::assertSame(2, $depthMap[3]); // Level 2
    }

    public function test_it_returns_empty_when_all_categories_excluded(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $logger = new ActionLogger();

        // Single category tree
        $allCategories = [
            ['id' => 1, 'name' => 'Only', 'slug' => 'only', 'parent' => 0],
            ['id' => 2, 'name' => 'Child', 'slug' => 'child', 'parent' => 1],
        ];

        $sdk->shouldReceive('categories')
            ->once()
            ->andReturn($allCategories);

        $service = new CategoryService($sdk, $logger);

        // Exclude root category (which excludes everything)
        $result = $service->eligibleParents(1);

        // Should only have "None" option (value 0) - but we return empty array
        // Actually, we return eligible parents, so if all are excluded, result is empty
        self::assertCount(0, $result);
    }
}
