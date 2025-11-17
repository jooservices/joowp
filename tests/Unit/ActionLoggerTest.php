<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Logging\ActionLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use Tests\Unit\Stubs\StubUser;

class ActionLoggerTest extends TestCase
{
    public function test_it_logs_structured_payload(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-01T12:00:00Z'));

        Log::shouldReceive('channel')
            ->once()
            ->with('action')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                $this->assertSame('Domain action recorded', $message);
                $this->assertSame('user.updated', $context['operation']);
                $this->assertSame(['name' => 'Before'], $context['before']);
                $this->assertSame(['name' => 'After'], $context['after']);
                $this->assertSame(['resource' => 'user'], $context['metadata']);
                $this->assertSame('2025-01-01T12:00:00+00:00', $context['occurred_at']);
                $this->assertSame([
                    'id' => 42,
                    'type' => StubUser::class,
                ], $context['actor']);

                return true;
            });

        $logger = new ActionLogger();
        $logger->log(
            'user.updated',
            new StubUser(42),
            ['name' => 'Before'],
            ['name' => 'After'],
            ['resource' => 'user']
        );

        Carbon::setTestNow();
        Mockery::close();
    }

    public function test_it_logs_without_actor(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-02T08:30:00Z'));

        Log::shouldReceive('channel')
            ->once()
            ->with('action')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                $this->assertSame('Domain action recorded', $message);
                $this->assertNull($context['actor']);

                return true;
            });

        (new ActionLogger())->log('task.run', null);

        Carbon::setTestNow();
        Mockery::close();
    }

    public function test_it_logs_with_complex_metadata(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-03T14:30:00Z'));

        Log::shouldReceive('channel')
            ->once()
            ->with('action')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                $this->assertSame('Domain action recorded', $message);
                $this->assertSame('wordpress.category.created', $context['operation']);
                $this->assertSame(['source' => 'wordpress', 'category_id' => 99], $context['metadata']);

                return true;
            });

        $logger = new ActionLogger();
        $logger->log(
            'wordpress.category.created',
            new StubUser(1),
            [],
            ['id' => 99, 'name' => 'News'],
            ['source' => 'wordpress', 'category_id' => 99]
        );

        Carbon::setTestNow();
        Mockery::close();
    }

    public function test_it_logs_with_empty_before_and_after(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-04T09:00:00Z'));

        Log::shouldReceive('channel')
            ->once()
            ->with('action')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                $this->assertSame('Domain action recorded', $message);
                $this->assertSame('system.cleanup', $context['operation']);
                $this->assertSame([], $context['before']);
                $this->assertSame([], $context['after']);

                return true;
            });

        $logger = new ActionLogger();
        $logger->log(
            'system.cleanup',
            null,
            [],
            []
        );

        Carbon::setTestNow();
        Mockery::close();
    }

    public function test_it_logs_with_nested_metadata(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-05T16:45:00Z'));

        Log::shouldReceive('channel')
            ->once()
            ->with('action')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                $this->assertSame('Domain action recorded', $message);
                $this->assertSame('api.request', $context['operation']);
                $this->assertSame([
                    'request' => [
                        'method' => 'POST',
                        'endpoint' => '/api/v1/posts',
                    ],
                    'response' => [
                        'status' => 201,
                    ],
                ], $context['metadata']);

                return true;
            });

        $logger = new ActionLogger();
        $logger->log(
            'api.request',
            new StubUser(5),
            [],
            [],
            [
                'request' => [
                    'method' => 'POST',
                    'endpoint' => '/api/v1/posts',
                ],
                'response' => [
                    'status' => 201,
                ],
            ]
        );

        Carbon::setTestNow();
        Mockery::close();
    }

    public function test_it_logs_with_null_metadata(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-06T11:20:00Z'));

        Log::shouldReceive('channel')
            ->once()
            ->with('action')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                $this->assertSame('Domain action recorded', $message);
                $this->assertSame('task.completed', $context['operation']);
                $this->assertSame([], $context['metadata']);

                return true;
            });

        $logger = new ActionLogger();
        $logger->log(
            'task.completed',
            null,
            [],
            [],
            []
        );

        Carbon::setTestNow();
        Mockery::close();
    }
}
