<?php

declare(strict_types=1);

namespace Modules\Core\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class LmStudioInferenceStreamed implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $jobId,
        public readonly string $type,
        public readonly array $payload = [],
    ) {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('lmstudio.inference.' . $this->jobId);
    }

    public function broadcastAs(): string
    {
        return 'LmStudioInferenceStreamed';
    }

    public function broadcastWith(): array
    {
        return [
            'job_id' => $this->jobId,
            'type' => $this->type,
            'payload' => $this->payload,
        ];
    }
}
