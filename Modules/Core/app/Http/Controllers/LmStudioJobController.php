<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Requests\StoreLmStudioJobRequest;
use Modules\Core\Jobs\ProcessLmStudioJob;
use Modules\Core\Models\LmStudioJob;
use Modules\Core\Models\LmStudioRole;

final class LmStudioJobController extends Controller
{
    public function store(StoreLmStudioJobRequest $request): JsonResponse
    {
        $role = LmStudioRole::query()->find($request->integer('lm_studio_role_id'))
            ?? LmStudioRole::query()->orderBy('id')->first();

        $job = LmStudioJob::query()->create([
            'prompt_message' => $request->string('prompt_message'),
            'role' => $role?->role_name ?? 'assistant',
            'lm_studio_role_id' => $role?->id,
        ]);

        ProcessLmStudioJob::dispatch($job->uuid);

        return ApiResponse::success(
            code: 'lmstudio.jobs.created',
            message: 'Job queued successfully.',
            data: $this->transform($job),
            status: 202
        );
    }

    public function show(string $uuid): JsonResponse
    {
        $job = LmStudioJob::query()
            ->where('uuid', $uuid)
            ->firstOrFail();

        return ApiResponse::success(
            code: 'lmstudio.jobs.show',
            message: 'Job retrieved.',
            data: $this->transform($job)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(LmStudioJob $job): array
    {
        return [
            'uuid' => $job->uuid,
            'prompt_message' => $job->prompt_message,
            'respond_message' => $job->respond_message,
            'role' => $job->role,
            'lm_studio_role_id' => $job->lm_studio_role_id,
            'started_at' => $job->started_at?->toAtomString(),
            'completed_at' => $job->completed_at?->toAtomString(),
            'prompt_tokens' => $job->prompt_tokens,
            'completion_tokens' => $job->completion_tokens,
            'total_tokens' => $job->total_tokens,
            'created_at' => $job->created_at?->toAtomString(),
            'updated_at' => $job->updated_at?->toAtomString(),
        ];
    }
}
