<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Logging\ActionLogger;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\Core\Services\WordPress\CategoryService;
use Modules\Core\Services\WordPress\Contracts\SdkContract;
use PHPUnit\Framework\TestCase;

final class WordPressCategoryServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_it_lists_categories_with_filters(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $logger = Mockery::spy(ActionLogger::class);

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
        $logger = Mockery::mock(ActionLogger::class);

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

        $logger->shouldReceive('log')
            ->once()
            ->with('wordpress.category.created', null, [], $response, ['source' => 'wordpress']);

        $service = new CategoryService($sdk, $logger);

        self::assertSame($response, $service->create($payload));
    }
}
