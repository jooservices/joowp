<?php

declare(strict_types=1);

namespace Modules\WordPress\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Http\Concerns\HandlesExternalServiceErrors;
use Modules\Core\Services\WordPress\Exceptions\WordPressRequestException;
use Modules\WordPress\Http\Requests\DeleteCategoryRequest;
use Modules\WordPress\Http\Requests\IndexCategoriesRequest;
use Modules\WordPress\Http\Requests\ParentCategoriesRequest;
use Modules\WordPress\Http\Requests\StoreCategoryRequest;
use Modules\WordPress\Http\Requests\UpdateCategoryRequest;
use Modules\WordPress\Services\CategoryService;

final class CategoryController extends Controller
{
    use HandlesExternalServiceErrors;

    public function __construct(private readonly CategoryService $service)
    {
    }

    public function index(IndexCategoriesRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $categories = $this->service->list($filters);

            return ApiResponse::success(
                code: 'wordpress.categories.list',
                message: 'Categories retrieved successfully.',
                data: [
                    'items' => $categories,
                ],
                meta: [
                    'filters' => [
                        'search' => $filters['search'] ?? null,
                        'per_page' => $filters['per_page'] ?? 20,
                        'page' => $filters['page'] ?? 1,
                    ],
                ]
            );
        } catch (WordPressRequestException $exception) {
            return $this->handleServiceError(
                exception: $exception,
                errorCode: 'wordpress.categories.list_failed',
                additionalMeta: [
                    'filters' => $request->validated(),
                ]
            );
        }
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        try {
            /** @var Authenticatable|null $actor */
            $actor = $request->user();
            $created = $this->service->create($request->validated(), $actor);

            return ApiResponse::success(
                code: 'wordpress.categories.created',
                message: 'Category created in WordPress.',
                data: $created,
                status: 201
            );
        } catch (WordPressRequestException $exception) {
            return $this->handleServiceError(
                exception: $exception,
                errorCode: 'wordpress.categories.create_failed'
            );
        }
    }

    public function update(UpdateCategoryRequest $request, int $category): JsonResponse
    {
        try {
            /** @var Authenticatable|null $actor */
            $actor = $request->user();
            $updated = $this->service->update($category, $request->validated(), $actor);

            return ApiResponse::success(
                code: 'wordpress.categories.updated',
                message: 'Category updated in WordPress.',
                data: $updated
            );
        } catch (WordPressRequestException $exception) {
            return $this->handleServiceError(
                exception: $exception,
                errorCode: 'wordpress.categories.update_failed',
                additionalMeta: ['category_id' => $category]
            );
        }
    }

    public function destroy(DeleteCategoryRequest $request, int $category): JsonResponse
    {
        try {
            /** @var Authenticatable|null $actor */
            $actor = $request->user();
            $deleted = $this->service->delete($category, (bool) $request->validated()['force'], $actor);

            return ApiResponse::success(
                code: 'wordpress.categories.deleted',
                message: 'Category deleted from WordPress.',
                data: $deleted
            );
        } catch (WordPressRequestException $exception) {
            return $this->handleServiceError(
                exception: $exception,
                errorCode: 'wordpress.categories.delete_failed',
                additionalMeta: ['category_id' => $category]
            );
        }
    }

    public function parents(ParentCategoriesRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $exclude = isset($filters['exclude']) && $filters['exclude'] > 0 ? (int) $filters['exclude'] : null;
            $includeTrashed = (bool) ($filters['include_trashed'] ?? false);

            $eligibleParents = $this->service->eligibleParents($exclude, $includeTrashed);

            return ApiResponse::success(
                code: 'wordpress.categories.parents',
                message: 'Eligible parent categories retrieved successfully.',
                data: [
                    'items' => $eligibleParents,
                    'hierarchy' => true,
                ]
            );
        } catch (WordPressRequestException $exception) {
            $filters = $request->validated(); // Ensure $filters is defined in catch block
            return $this->handleServiceError(
                exception: $exception,
                errorCode: 'wordpress.categories.parents_failed',
                additionalMeta: ['filters' => $filters]
            );
        }
    }
}
