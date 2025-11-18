<?php

declare(strict_types=1);

namespace Modules\WordPress\Services;

use App\Logging\ActionLogger;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Modules\Core\Services\WordPress\Contracts\SdkContract;

final class TagService
{
    public function __construct(
        private readonly SdkContract $sdk,
        private readonly ActionLogger $actionLogger
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int|string, mixed>
     */
    public function list(array $filters = []): array
    {
        // Build query according to WordPress REST API documentation
        // https://developer.wordpress.org/rest-api/reference/tags/
        $query = array_filter([
            'search' => Arr::get($filters, 'search'),
            'per_page' => Arr::get($filters, 'per_page', 20),
            'page' => Arr::get($filters, 'page', 1),
            'context' => 'view', // Default: view, valid: view, embed, edit
            'orderby' => 'name', // Default: name, valid: id, include, name, slug, include_slugs, term_group, description, count
            'order' => 'asc', // Default: asc, valid: asc, desc
        ], static fn ($value) => $value !== null && $value !== '');

        $tags = $this->sdk->tags($query);

        return array_values($tags);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int|string, mixed>
     */
    public function create(array $payload, ?Authenticatable $actor = null): array
    {
        $body = $this->sdk->createTag($this->filterPayload($payload));

        $this->actionLogger->log(
            'wordpress.tag.created',
            $actor,
            [],
            $body,
            ['source' => 'wordpress']
        );

        return $body;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int|string, mixed>
     */
    public function update(int $tagId, array $payload, ?Authenticatable $actor = null): array
    {
        $before = $this->sdk->tag($tagId);
        $body = $this->sdk->updateTag($tagId, $this->filterPayload($payload));

        $this->actionLogger->log(
            'wordpress.tag.updated',
            $actor,
            $before,
            $body,
            [
                'source' => 'wordpress',
                'tag_id' => $tagId,
            ]
        );

        return $body;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function delete(int $tagId, bool $force = true, ?Authenticatable $actor = null): array
    {
        $before = $this->sdk->tag($tagId);
        $body = $this->sdk->deleteTag($tagId, ['force' => $force]);

        $this->actionLogger->log(
            'wordpress.tag.deleted',
            $actor,
            $before,
            $body,
            [
                'source' => 'wordpress',
                'tag_id' => $tagId,
                'force' => $force,
            ]
        );

        return $body;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function filterPayload(array $payload): array
    {
        return array_filter(
            $payload,
            static fn ($value) => ! (is_string($value) && trim($value) === '') && $value !== null
        );
    }
}
