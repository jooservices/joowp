<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Logging\ActionLogger;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\Core\Services\WordPress\Contracts\SdkContract;
use Modules\WordPress\Services\CategoryService;
use PHPUnit\Framework\TestCase;

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
}
