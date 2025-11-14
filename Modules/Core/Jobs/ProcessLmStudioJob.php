<?php

declare(strict_types=1);

namespace Modules\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Core\Models\LmStudioJob;
use Modules\Core\Services\LmStudio\Contracts\SdkContract;
use Modules\Core\Services\LmStudio\DTO\Chat\ChatCompletionRequest;
use Modules\Core\Services\LmStudio\DTO\Chat\ChatMessage;
use Modules\Core\Services\LmStudio\DTO\Chat\ChatRole;

final class ProcessLmStudioJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly string $jobUuid)
    {
        $this->onConnection(config('lmstudio.queue_connection', config('queue.default')));
        $this->onQueue(config('lmstudio.queue', 'default'));
    }

    public function handle(SdkContract $sdk): void
    {
        /** @var LmStudioJob|null $job */
        $job = LmStudioJob::query()
            ->with('roleDefinition')
            ->where('uuid', $this->jobUuid)
            ->first();

        if ($job === null) {
            return;
        }

        $job->forceFill([
            'started_at' => now(),
        ])->save();

        $model = config('lmstudio.default_model') ?? 'lmstudio-local';

        $systemPrompt = $job->roleDefinition?->role_prompt ?? 'You are a helpful assistant.';

        $messages = [
            new ChatMessage(ChatRole::System, $systemPrompt),
            new ChatMessage(ChatRole::User, $job->prompt_message),
        ];

        $request = new ChatCompletionRequest(
            model: $model,
            messages: $messages,
            stream: false,
        );

        $response = $sdk->createChatCompletion($request);
        $choice = $response->choices[0] ?? null;
        $message = $choice?->message?->content ?? '';
        $role = $choice?->message?->role?->value ?? 'assistant';

        $job->forceFill([
            'respond_message' => $message,
            'role' => $role,
            'completed_at' => now(),
            'prompt_tokens' => $response->usage?->promptTokens,
            'completion_tokens' => $response->usage?->completionTokens,
            'total_tokens' => $response->usage?->totalTokens,
        ])->save();
    }
}
