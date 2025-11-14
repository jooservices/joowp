<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Jobs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Modules\Core\Jobs\ProcessLmStudioJob;
use Modules\Core\Models\LmStudioJob;
use Modules\Core\Models\LmStudioRole;
use Modules\Core\Services\LmStudio\Contracts\SdkContract;
use Modules\Core\Services\LmStudio\DTO\Chat\ChatChoice;
use Modules\Core\Services\LmStudio\DTO\Chat\ChatCompletionRequest;
use Modules\Core\Services\LmStudio\DTO\Chat\ChatCompletionResponse;
use Modules\Core\Services\LmStudio\DTO\Chat\ChatMessage;
use Modules\Core\Services\LmStudio\DTO\Chat\ChatRole;
use Modules\Core\Services\LmStudio\DTO\Shared\Usage;
use Tests\TestCase;

final class ProcessLmStudioJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_job_record_with_response(): void
    {
        $role = LmStudioRole::query()->create([
            'role_name' => 'demo-role',
            'role_prompt' => 'system instructions',
        ]);

        $jobRecord = LmStudioJob::query()->create([
            'prompt_message' => 'Explain streaming',
            'role' => 'assistant',
            'lm_studio_role_id' => $role->id,
        ]);

        $job = new ProcessLmStudioJob($jobRecord->uuid);

        $sdk = Mockery::mock(SdkContract::class);
        $sdk->shouldReceive('createChatCompletion')
            ->once()
            ->with(Mockery::on(function (ChatCompletionRequest $request) use ($role): bool {
                return $request->messages[0]->content === $role->role_prompt
                    && $request->messages[1]->content === 'Explain streaming';
            }))
            ->andReturn(new ChatCompletionResponse(
                id: 'test',
                object: 'chat.completion',
                created: time(),
                model: 'mistral',
                choices: [
                    new ChatChoice(
                        index: 0,
                        message: new ChatMessage(ChatRole::Assistant, 'Streaming response')
                    ),
                ],
                usage: new Usage(
                    promptTokens: 19,
                    completionTokens: 23,
                    totalTokens: 42,
                ),
            ));

        $job->handle($sdk);

        $jobRecord->refresh();

        $this->assertEquals('Streaming response', $jobRecord->respond_message);
        $this->assertNotNull($jobRecord->started_at);
        $this->assertNotNull($jobRecord->completed_at);
        $this->assertEquals(19, $jobRecord->prompt_tokens);
        $this->assertEquals(23, $jobRecord->completion_tokens);
        $this->assertEquals(42, $jobRecord->total_tokens);
    }
}
