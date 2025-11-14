<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Requests\LmStudioInferenceRequest;
use Modules\Core\Services\LmStudio\Contracts\SdkContract;
use Modules\Core\Services\LmStudio\DTO\ListModelsFilter;
use Modules\Core\Services\LmStudio\Exceptions\LmStudioException;
use Modules\Core\Services\LmStudio\Inference\ChatInferenceService;

final class LmStudioController extends Controller
{
    public function __construct(
        private readonly ChatInferenceService $inferenceService,
        private readonly SdkContract $sdk
    ) {
    }

    public function models(Request $request): JsonResponse
    {
        if (! $this->featureEnabled()) {
            return $this->featureDisabled();
        }

        $filter = new ListModelsFilter(
            ownedBy: $request->string('owned_by')->toString() ?: null,
            status: $request->string('status')->toString() ?: null,
            limit: $request->integer('limit') ?: null,
            cursor: $request->string('cursor')->toString() ?: null,
        );

        try {
            $models = $this->sdk->listModels($filter->isEmpty() ? null : $filter);
        } catch (LmStudioException $exception) {
            return $this->errorFromException($exception);
        }

        return ApiResponse::success(
            code: 'lmstudio.models_listed',
            message: 'LM Studio models retrieved.',
            data: [
                'models' => array_map(
                    static fn ($model) => $model->toArray(),
                    $models
                ),
            ],
        );
    }

    public function infer(LmStudioInferenceRequest $request): JsonResponse
    {
        if (! $this->featureEnabled()) {
            return $this->featureDisabled();
        }

        try {
            $result = $this->inferenceService->start($request->toDto());
        } catch (LmStudioException $exception) {
            return $this->errorFromException($exception);
        }

        return ApiResponse::success(
            code: 'lmstudio.infer.accepted',
            message: 'Inference started successfully.',
            data: $result,
            status: 202
        );
    }

    private function featureEnabled(): bool
    {
        return (bool) config('features.lmstudio.enabled', false);
    }

    private function featureDisabled(): JsonResponse
    {
        return ApiResponse::error(
            code: 'lmstudio.disabled',
            message: 'LM Studio integration is disabled.',
            status: 503
        );
    }

    private function errorFromException(LmStudioException $exception): JsonResponse
    {
        return ApiResponse::error(
            code: 'lmstudio.error',
            message: $exception->getMessage(),
            meta: $exception->getContext(),
            status: 502
        );
    }
}
