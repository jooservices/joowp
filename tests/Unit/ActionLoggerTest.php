<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Logging\ActionLogger;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

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

        $logger = new ActionLogger;
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

        (new ActionLogger)->log('task.run', null);

        Carbon::setTestNow();
        Mockery::close();
    }
}

final class StubUser implements Authenticatable
{
    public function __construct(private readonly int $identifier) {}

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): int
    {
        return $this->identifier;
    }

    public function getAuthPassword(): string
    {
        return 'secret';
    }

    public function getAuthPasswordName(): ?string
    {
        return 'password';
    }

    public function getRememberToken()
    {
        return null;
    }

    public function setRememberToken($value): void
    {
        //
    }

    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }
}
