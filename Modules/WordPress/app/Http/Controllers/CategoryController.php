<?php

declare(strict_types=1);

namespace Modules\WordPress\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\WordPress\Http\Requests\DeleteCategoryRequest;
use Modules\WordPress\Http\Requests\IndexCategoriesRequest;
use Modules\WordPress\Http\Requests\StoreCategoryRequest;
use Modules\WordPress\Http\Requests\UpdateCategoryRequest;
use Modules\WordPress\Services\CategoryService;

final class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $service)
    {
    }

    public function index(IndexCategoriesRequest $request): JsonResponse
    {
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
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        /** @var Authenticatable|null $actor */
        $actor = $request->user();
        $created = $this->service->create($request->validated(), $actor);

        return ApiResponse::success(
            code: 'wordpress.categories.created',
            message: 'Category created in WordPress.',
            data: $created,
            status: 201
        );
    }

    public function update(UpdateCategoryRequest $request, int $category): JsonResponse
    {
        /** @var Authenticatable|null $actor */
        $actor = $request->user();
        $updated = $this->service->update($category, $request->validated(), $actor);

        return ApiResponse::success(
            code: 'wordpress.categories.updated',
            message: 'Category updated in WordPress.',
            data: $updated
        );
    }

    public function destroy(DeleteCategoryRequest $request, int $category): JsonResponse
    {
        /** @var Authenticatable|null $actor */
        $actor = $request->user();
        $deleted = $this->service->delete($category, (bool) $request->validated()['force'], $actor);

        return ApiResponse::success(
            code: 'wordpress.categories.deleted',
            message: 'Category deleted from WordPress.',
            data: $deleted
        );
    }
}
