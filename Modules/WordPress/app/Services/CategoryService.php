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
        $includeTrashed = Arr::get($filters, 'include_trashed', false);
        $query = array_filter([
            'search' => Arr::get($filters, 'search'),
            'per_page' => Arr::get($filters, 'per_page', 20),
            'page' => Arr::get($filters, 'page', 1),
            'context' => 'view',
            'orderby' => 'name',
            'order' => 'asc',
            // WordPress REST API: when include_trashed is true, we need to include status=trash
            // Note: WordPress categories don't have status field by default, but we pass it for consistency
            // The actual filtering will be done in the frontend or via eligibleParents method
        ], static fn ($value) => $value !== null);

        $categories = $this->sdk->categories($query);

        // Filter trashed categories if include_trashed is false
        if (! $includeTrashed) {
            $categories = array_filter($categories, static function ($category) {
                if (! is_array($category)) {
                    return true;
                }

                $status = $category['status'] ?? null;

                // WordPress categories don't have status field, but check for consistency
                return $status !== 'trash';
            });
        }

        return array_values($categories);
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
     * Get eligible parent categories for dropdown
     *
     * Returns categories that can legally be parents, excluding:
     * - The category being edited (if exclude provided)
     * - All descendants of the excluded category
     * - Trashed categories (unless include_trashed=true)
     *
     * @param  int|null  $exclude  Category ID to exclude (self + all descendants)
     * @param  bool  $includeTrashed  Whether to include trashed categories
     * @return array<int|string, mixed> Array of eligible parent categories with depth information
     */
    public function eligibleParents(?int $exclude = null, bool $includeTrashed = false): array
    {
        // Fetch all categories with pagination
        // WordPress REST API limits per_page to maximum 100
        // We need to paginate to get all categories for parent dropdown
        $allCategories = [];
        $page = 1;
        $perPage = 100; // WordPress REST API maximum per_page limit

        do {
            $query = [
                'per_page' => $perPage,
                'page' => $page,
                'orderby' => 'name',
                'order' => 'asc',
            ];

            $pageCategories = $this->sdk->categories($query);
            /** @var array<int|string, mixed> $pageCategories */
            $pageCount = count($pageCategories);

            if ($pageCount > 0) {
                $allCategories = array_merge($allCategories, $pageCategories);
            }

            // Continue to next page if we got a full page (might be more)
            $page++;
        } while ($pageCount === $perPage);

        // Build category map for quick lookup
        /** @var array<int, array<string, mixed>> $categoryMap */
        $categoryMap = [];
        foreach ($allCategories as $category) {
            if (! is_array($category)) {
                continue;
            }

            $idValue = $category['id'] ?? null;
            $id = is_numeric($idValue) ? (int) $idValue : 0;
            if ($id > 0) {
                /** @var array<string, mixed> $category */
                $categoryMap[$id] = $category;
            }
        }

        // Get descendant IDs to exclude
        $excludeIds = [];
        if ($exclude !== null && $exclude > 0) {
            $excludeIds = $this->getDescendantIds($exclude, $categoryMap);
            $excludeIds[] = $exclude; // Include self
        }

        // Filter eligible categories
        $eligible = [];
        foreach ($allCategories as $category) {
            if (! is_array($category)) {
                continue;
            }

            $idValue = $category['id'] ?? null;
            $id = is_numeric($idValue) ? (int) $idValue : 0;

            // Skip excluded categories
            if (in_array($id, $excludeIds, true)) {
                continue;
            }

            // Filter trashed categories (check for status field - WordPress REST API)
            // Note: Field name needs verification during implementation
            $status = is_string($category['status'] ?? null) ? $category['status'] : null;
            if (! $includeTrashed && $status === 'trash') {
                continue;
            }

            // Calculate depth from parent chain
            $depth = $this->calculateDepth($id, $categoryMap);

            $name = is_string($category['name'] ?? null) ? $category['name'] : '';
            $slug = is_string($category['slug'] ?? null) ? $category['slug'] : '';
            $parentValue = $category['parent'] ?? null;
            $parent = is_numeric($parentValue) ? (int) $parentValue : 0;

            $eligible[] = [
                'id' => $id,
                'name' => $name,
                'slug' => $slug,
                'parent' => $parent,
                'depth' => $depth,
                'status' => $status,
            ];
        }

        // Sort by depth, then by name
        usort($eligible, static function (array $a, array $b): int {
            $aDepth = $a['depth'];
            $bDepth = $b['depth'];

            if ($aDepth !== $bDepth) {
                return $aDepth <=> $bDepth;
            }

            return strcmp($a['name'], $b['name']);
        });

        return $eligible;
    }

    /**
     * Get all descendant IDs of a category recursively
     *
     * @param  int  $categoryId  The category ID
     * @param  array<int, array<string, mixed>>  $categoryMap  Map of category ID to category data
     * @return array<int> Array of descendant category IDs
     */
    /**
     * @param  array<int, array<string, mixed>>  $categoryMap
     * @return array<int>
     */
    private function getDescendantIds(int $categoryId, array $categoryMap): array
    {
        $descendants = [];
        $children = array_filter($categoryMap, static function ($cat) use ($categoryId) {
            if (! is_array($cat)) {
                return false;
            }

            $parentValue = $cat['parent'] ?? null;
            $parent = is_numeric($parentValue) ? (int) $parentValue : 0;

            return $parent === $categoryId;
        });

        foreach ($children as $child) {
            if (! is_array($child)) {
                continue;
            }

            $childIdValue = $child['id'] ?? null;
            $childId = is_numeric($childIdValue) ? (int) $childIdValue : 0;
            if ($childId > 0) {
                $descendants[] = $childId;
                $descendants = array_merge($descendants, $this->getDescendantIds($childId, $categoryMap));
            }
        }

        return $descendants;
    }

    /**
     * Calculate depth of a category by traversing parent chain
     *
     * @param  int  $categoryId  The category ID
     * @param  array<int, array<string, mixed>>  $categoryMap  Map of category ID to category data
     * @return int The depth (0 for root categories)
     */
    /**
     * @param  array<int, array<string, mixed>>  $categoryMap
     */
    private function calculateDepth(int $categoryId, array $categoryMap): int
    {
        $category = $categoryMap[$categoryId] ?? null;
        if (! is_array($category)) {
            return 0;
        }

        $parentValue = $category['parent'] ?? null;
        $parentId = is_numeric($parentValue) ? (int) $parentValue : 0;
        if ($parentId === 0) {
            return 0;
        }

        return 1 + $this->calculateDepth($parentId, $categoryMap);
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
