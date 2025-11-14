<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Modules\Core\Jobs\ProcessLmStudioJob;
use Modules\Core\Models\LmStudioJob;
use Modules\Core\Models\LmStudioRole;
use Tests\TestCase;

final class LmStudioQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_job_and_dispatches_queue(): void
    {
        Bus::fake();

        $role = LmStudioRole::query()->create([
            'role_name' => 'demo-role',
            'role_prompt' => 'demo prompt',
        ]);

        $response = $this->postJson('/api/v1/ai/lmstudio/jobs', [
            'prompt_message' => 'Hello queue',
            'role' => 'user',
            'lm_studio_role_id' => $role->id,
        ]);

        $response
            ->assertStatus(202)
            ->assertJsonPath('code', 'lmstudio.jobs.created');

        $payload = $response->json('data');

        $this->assertNotNull($payload['uuid']);
        Bus::assertDispatched(ProcessLmStudioJob::class);
    }

    public function test_it_shows_job_status(): void
    {
        /** @var LmStudioJob $job */
        $job = LmStudioJob::query()->create([
            'prompt_message' => 'Ping',
            'respond_message' => 'Pong',
            'role' => 'assistant',
        ]);

        $response = $this->getJson("/api/v1/ai/lmstudio/jobs/{$job->uuid}");

        $response
            ->assertOk()
            ->assertJsonPath('data.respond_message', 'Pong');
    }
}
