<?php

declare(strict_types=1);

namespace Modules\WordPress\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Services\WordPress\Exceptions\WordPressRequestException;
use Modules\WordPress\Http\Requests\DeleteTagRequest;
use Modules\WordPress\Http\Requests\IndexTagsRequest;
use Modules\WordPress\Http\Requests\StoreTagRequest;
use Modules\WordPress\Http\Requests\UpdateTagRequest;
use Modules\WordPress\Services\TagService;

final class TagController extends Controller
{
    public function __construct(private readonly TagService $service)
    {
    }

    public function index(IndexTagsRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $tags = $this->service->list($filters);

            return ApiResponse::success(
                code: 'wordpress.tags.list',
                message: 'Tags retrieved successfully.',
                data: [
                    'items' => $tags,
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
            $sourceStatus = $exception->sourceStatus();

            return ApiResponse::error(
                code: 'wordpress.tags.list_failed',
                message: 'Unable to retrieve tags from WordPress. The server may be temporarily unavailable.',
                meta: array_filter([
                    'source_status' => $sourceStatus,
                    'error_message' => $exception->getMessage(),
                ], static fn ($value) => $value !== null),
                data: ['items' => []],
                status: $sourceStatus ?? 502
            );
        }
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        try {
            /** @var Authenticatable|null $actor */
            $actor = $request->user();
            $created = $this->service->create($request->validated(), $actor);

            return ApiResponse::success(
                code: 'wordpress.tags.created',
                message: 'Tag created in WordPress.',
                data: $created,
                status: 201
            );
        } catch (WordPressRequestException $exception) {
            $sourceStatus = $exception->sourceStatus();

            return ApiResponse::error(
                code: 'wordpress.tags.create_failed',
                message: 'Unable to create tag in WordPress. The server may be temporarily unavailable.',
                meta: array_filter([
                    'source_status' => $sourceStatus,
                    'error_message' => $exception->getMessage(),
                ], static fn ($value) => $value !== null),
                data: null,
                status: $sourceStatus ?? 502
            );
        }
    }

    public function update(UpdateTagRequest $request, int $tag): JsonResponse
    {
        try {
            /** @var Authenticatable|null $actor */
            $actor = $request->user();
            $updated = $this->service->update($tag, $request->validated(), $actor);

            return ApiResponse::success(
                code: 'wordpress.tags.updated',
                message: 'Tag updated in WordPress.',
                data: $updated
            );
        } catch (WordPressRequestException $exception) {
            $sourceStatus = $exception->sourceStatus();

            return ApiResponse::error(
                code: 'wordpress.tags.update_failed',
                message: 'Unable to update tag in WordPress. The server may be temporarily unavailable.',
                meta: array_filter([
                    'source_status' => $sourceStatus,
                    'error_message' => $exception->getMessage(),
                    'tag_id' => $tag,
                ], static fn ($value) => $value !== null),
                data: null,
                status: $sourceStatus ?? 502
            );
        }
    }

    public function destroy(DeleteTagRequest $request, int $tag): JsonResponse
    {
        try {
            /** @var Authenticatable|null $actor */
            $actor = $request->user();
            $deleted = $this->service->delete($tag, (bool) ($request->validated()['force'] ?? true), $actor);

            return ApiResponse::success(
                code: 'wordpress.tags.deleted',
                message: 'Tag deleted from WordPress.',
                data: $deleted
            );
        } catch (WordPressRequestException $exception) {
            $sourceStatus = $exception->sourceStatus();

            return ApiResponse::error(
                code: 'wordpress.tags.delete_failed',
                message: 'Unable to delete tag from WordPress. The server may be temporarily unavailable.',
                meta: array_filter([
                    'source_status' => $sourceStatus,
                    'error_message' => $exception->getMessage(),
                    'tag_id' => $tag,
                ], static fn ($value) => $value !== null),
                data: null,
                status: $sourceStatus ?? 502
            );
        }
    }
}

