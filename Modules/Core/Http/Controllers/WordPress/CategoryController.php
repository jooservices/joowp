<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers\WordPress;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Http\Requests\DeleteCategoryRequest;
use Modules\Core\Http\Requests\IndexCategoriesRequest;
use Modules\Core\Http\Requests\StoreCategoryRequest;
use Modules\Core\Http\Requests\UpdateCategoryRequest;
use Modules\Core\Services\WordPress\CategoryService;

final class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $service) {}

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
        $created = $this->service->create($request->validated(), $request->user());

        return ApiResponse::success(
            code: 'wordpress.categories.created',
            message: 'Category created in WordPress.',
            data: $created,
            status: 201
        );
    }

    public function update(UpdateCategoryRequest $request, int $category): JsonResponse
    {
        $updated = $this->service->update($category, $request->validated(), $request->user());

        return ApiResponse::success(
            code: 'wordpress.categories.updated',
            message: 'Category updated in WordPress.',
            data: $updated
        );
    }

    public function destroy(DeleteCategoryRequest $request, int $category): JsonResponse
    {
        $deleted = $this->service->delete($category, (bool) $request->validated()['force'], $request->user());

        return ApiResponse::success(
            code: 'wordpress.categories.deleted',
            message: 'Category deleted from WordPress.',
            data: $deleted
        );
    }
}
