<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Services\LmStudio\Inference;

use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\Core\Events\LmStudioInferenceStreamed;
use Modules\Core\Services\LmStudio\Contracts\SdkContract;
use Modules\Core\Services\LmStudio\DTO\Chat\ChatChoice;
use Modules\Core\Services\LmStudio\DTO\Chat\ChatCompletionRequest;
use Modules\Core\Services\LmStudio\DTO\Chat\ChatCompletionResponse;
use Modules\Core\Services\LmStudio\DTO\Chat\ChatMessage;
use Modules\Core\Services\LmStudio\DTO\Chat\ChatRole;
use Modules\Core\Services\LmStudio\Inference\ChatInferenceService;
use Tests\TestCase;

final class ChatInferenceServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_it_dispatches_events_and_returns_metadata(): void
    {
        Event::fake();

        $sdk = Mockery::mock(SdkContract::class);
        $service = new ChatInferenceService($sdk);

        $request = new ChatCompletionRequest(
            model: 'mistral',
            messages: [
                new ChatMessage(ChatRole::User, 'Hello!'),
            ],
            stream: true,
        );

        $response = new ChatCompletionResponse(
            id: 'chatcmpl-123',
            object: 'chat.completion',
            created: 1731614400,
            model: 'mistral',
            choices: [
                new ChatChoice(
                    index: 0,
                    message: new ChatMessage(ChatRole::Assistant, 'Hello from LM Studio!')
                ),
            ],
        );

        $sdk->shouldReceive('createChatCompletion')
            ->once()
            ->with($request)
            ->andReturn($response);

        $result = $service->start($request);

        $this->assertArrayHasKey('job_id', $result);
        $this->assertSame('mistral', $result['model']);
        $this->assertSame(1731614400, $result['created']);

        Event::assertDispatched(LmStudioInferenceStreamed::class, function (LmStudioInferenceStreamed $event) {
            return $event->type === 'chunk'
                && $event->payload['content'] !== '';
        });

        Event::assertDispatched(LmStudioInferenceStreamed::class, function (LmStudioInferenceStreamed $event) {
            return $event->type === 'completed'
                && isset($event->payload['response']);
        });
    }
}
