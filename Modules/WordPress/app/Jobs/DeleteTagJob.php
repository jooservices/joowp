<?php

declare(strict_types=1);

namespace Modules\WordPress\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\WordPress\Services\TagService;

final class DeleteTagJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 2;

    public function __construct(
        private readonly int $tagId,
        private readonly bool $force = true,
        private readonly ?int $userId = null
    ) {
        /** @var string $connection */
        $connection = config('queue.default', 'database');
        $this->onConnection($connection);
        // Use default queue - will be processed by: php artisan queue:work
        // If you want separate queue, uncomment: $this->onQueue('wordpress');
        // Then run: php artisan queue:work --queue=wordpress
    }

    public function handle(TagService $tagService): void
    {
        try {
            Log::channel('external')->info('Deleting tag via queue', [
                'tag_id' => $this->tagId,
                'force' => $this->force,
            ]);

            /** @var User|null $actor */
            $actor = $this->userId ? User::query()->find($this->userId) : null;

            $tagService->delete($this->tagId, $this->force, $actor);

            Log::channel('external')->info('Tag deleted successfully via queue', [
                'tag_id' => $this->tagId,
            ]);
        } catch (\Throwable $exception) {
            Log::channel('external')->error('Failed to delete tag via queue', [
                'tag_id' => $this->tagId,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw $exception; // Re-throw to trigger retry
        }
    }
}
