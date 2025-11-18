<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Logging\ActionLogger;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\Core\Services\WordPress\Contracts\SdkContract;
use Modules\WordPress\Services\TagService;
use Tests\TestCase;

final class WordPressTagServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_it_lists_tags_with_filters(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $logger = new ActionLogger();

        $sdk->shouldReceive('tags')
            ->once()
            ->with([
                'search' => 'tech',
                'per_page' => 10,
                'page' => 2,
                'context' => 'view',
                'orderby' => 'name',
                'order' => 'asc',
            ])
            ->andReturn([['id' => 1, 'name' => 'Technology']]);

        $service = new TagService($sdk, $logger);

        $result = $service->list([
            'search' => 'tech',
            'per_page' => 10,
            'page' => 2,
        ]);

        self::assertSame([['id' => 1, 'name' => 'Technology']], $result);
    }

    public function test_it_logs_when_creating_tags(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $logger = new ActionLogger();

        $payload = [
            'name' => 'Technology',
            'slug' => 'technology',
            'description' => 'Tech related content',
        ];

        $response = ['id' => 99, 'name' => 'Technology'];

        $sdk->shouldReceive('createTag')
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
                return $context['operation'] === 'wordpress.tag.created'
                    && $context['after'] === ['id' => 99, 'name' => 'Technology']
                    && $context['metadata']['source'] === 'wordpress';
            }));

        $service = new TagService($sdk, $logger);

        self::assertSame($response, $service->create($payload));
    }

    public function test_it_logs_when_updating_tags(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $logger = new ActionLogger();

        $tagId = 99;
        $before = ['id' => $tagId, 'name' => 'Old Name', 'slug' => 'old-slug'];
        $payload = ['name' => 'New Name'];
        $after = ['id' => $tagId, 'name' => 'New Name', 'slug' => 'old-slug'];

        $sdk->shouldReceive('tag')
            ->once()
            ->with($tagId)
            ->andReturn($before);

        $sdk->shouldReceive('updateTag')
            ->once()
            ->with($tagId, $payload)
            ->andReturn($after);

        Log::shouldReceive('channel')
            ->once()
            ->with('action')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Domain action recorded', Mockery::on(function (array $context) use ($tagId, $before, $after): bool {
                return $context['operation'] === 'wordpress.tag.updated'
                    && $context['before'] === $before
                    && $context['after'] === $after
                    && $context['metadata']['source'] === 'wordpress'
                    && $context['metadata']['tag_id'] === $tagId;
            }));

        $service = new TagService($sdk, $logger);

        self::assertSame($after, $service->update($tagId, $payload));
    }

    public function test_it_logs_when_deleting_tags(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $logger = new ActionLogger();

        $tagId = 99;
        $before = ['id' => $tagId, 'name' => 'Technology'];
        $after = ['deleted' => true, 'previous' => $before];

        $sdk->shouldReceive('tag')
            ->once()
            ->with($tagId)
            ->andReturn($before);

        $sdk->shouldReceive('deleteTag')
            ->once()
            ->with($tagId, ['force' => true])
            ->andReturn($after);

        Log::shouldReceive('channel')
            ->once()
            ->with('action')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Domain action recorded', Mockery::on(function (array $context) use ($tagId, $before, $after): bool {
                return $context['operation'] === 'wordpress.tag.deleted'
                    && $context['before'] === $before
                    && $context['after'] === $after
                    && $context['metadata']['source'] === 'wordpress'
                    && $context['metadata']['tag_id'] === $tagId
                    && $context['metadata']['force'] === true;
            }));

        $service = new TagService($sdk, $logger);

        self::assertSame($after, $service->delete($tagId, true));
    }

    public function test_it_filters_empty_strings_from_payload(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $logger = new ActionLogger();

        $payload = [
            'name' => 'Technology',
            'slug' => '',
            'description' => '   ',
        ];

        $filteredPayload = ['name' => 'Technology'];

        $sdk->shouldReceive('createTag')
            ->once()
            ->with($filteredPayload)
            ->andReturn(['id' => 1, 'name' => 'Technology']);

        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info')->once();

        $service = new TagService($sdk, $logger);

        $service->create($payload);
    }

    public function test_it_handles_pagination(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $logger = new ActionLogger();

        $sdk->shouldReceive('tags')
            ->once()
            ->with([
                'per_page' => 100,
                'page' => 1,
                'context' => 'view',
                'orderby' => 'name',
                'order' => 'asc',
            ])
            ->andReturn(array_fill(0, 100, ['id' => 1, 'name' => 'Tag']));

        $service = new TagService($sdk, $logger);

        $result = $service->list([
            'per_page' => 100,
            'page' => 1,
        ]);

        self::assertCount(100, $result);
    }
}
