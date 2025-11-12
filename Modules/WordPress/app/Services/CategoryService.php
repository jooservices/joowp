<?php

declare(strict_types=1);

namespace Modules\WordPress\Services;

use App\Logging\ActionLogger;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Modules\Core\Services\WordPress\Contracts\SdkContract;

final class CategoryService
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
        $query = array_filter([
            'search' => Arr::get($filters, 'search'),
            'per_page' => Arr::get($filters, 'per_page', 20),
            'page' => Arr::get($filters, 'page', 1),
            'context' => 'view',
            'orderby' => 'name',
            'order' => 'asc',
        ], static fn ($value) => $value !== null);

        return $this->sdk->categories($query);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int|string, mixed>
     */
    public function create(array $payload, ?Authenticatable $actor = null): array
    {
        $body = $this->sdk->createCategory($this->filterPayload($payload));

        $this->actionLogger->log(
            'wordpress.category.created',
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
    public function update(int $categoryId, array $payload, ?Authenticatable $actor = null): array
    {
        $before = $this->sdk->category($categoryId);
        $body = $this->sdk->updateCategory($categoryId, $this->filterPayload($payload));

        $this->actionLogger->log(
            'wordpress.category.updated',
            $actor,
            $before,
            $body,
            [
                'source' => 'wordpress',
                'category_id' => $categoryId,
            ]
        );

        return $body;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function delete(int $categoryId, bool $force = true, ?Authenticatable $actor = null): array
    {
        $before = $this->sdk->category($categoryId);
        $body = $this->sdk->deleteCategory($categoryId, ['force' => $force]);

        $this->actionLogger->log(
            'wordpress.category.deleted',
            $actor,
            $before,
            $body,
            [
                'source' => 'wordpress',
                'category_id' => $categoryId,
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
