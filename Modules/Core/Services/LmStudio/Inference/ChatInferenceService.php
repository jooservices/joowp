<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\Inference;

use Illuminate\Support\Str;
use Modules\Core\Events\LmStudioInferenceStreamed;
use Modules\Core\Services\LmStudio\Contracts\SdkContract;
use Modules\Core\Services\LmStudio\DTO\Chat\ChatChoice;
use Modules\Core\Services\LmStudio\DTO\Chat\ChatCompletionRequest;
use Modules\Core\Services\LmStudio\DTO\Chat\ChatCompletionResponse;

class ChatInferenceService
{
    public function __construct(private readonly SdkContract $sdk)
    {
    }

    public function start(ChatCompletionRequest $request): array
    {
        $jobId = (string) Str::uuid();

        $response = $this->sdk->createChatCompletion($request);
        $content = $this->extractContent($response);

        foreach ($this->chunkContent($content) as $chunk) {
            event(new LmStudioInferenceStreamed(
                jobId: $jobId,
                type: 'chunk',
                payload: ['content' => $chunk],
            ));
        }

        event(new LmStudioInferenceStreamed(
            jobId: $jobId,
            type: 'completed',
            payload: [
                'response' => $response->toArray(),
            ],
        ));

        return [
            'job_id' => $jobId,
            'model' => $response->model,
            'created' => $response->created,
        ];
    }

    private function extractContent(ChatCompletionResponse $response): string
    {
        if ($response->choices === []) {
            return '';
        }

        /** @var ChatChoice $choice */
        $choice = $response->choices[0];

        return $choice->message?->content ?? '';
    }

    /**
     * @return array<int, string>
     */
    private function chunkContent(string $content): array
    {
        if ($content === '') {
            return [];
        }

        return mb_str_split($content, 40, 'UTF-8') ?: [$content];
    }
}
