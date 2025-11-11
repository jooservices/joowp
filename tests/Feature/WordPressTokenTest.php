<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\WpToken;
use Modules\Core\Services\WordPress\Contracts\SdkContract;
use Modules\Core\Services\WordPress\Exceptions\WordPressRequestException;
use Mockery;
use Tests\TestCase;

final class WordPressTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_token_via_internal_api(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $sdk
            ->shouldReceive('token')
            ->once()
            ->with('demo', 'secret')
            ->andReturn([
                'token' => 'demo-token',
                'user_email' => 'demo@example.com',
            ]);

        $this->app->instance(SdkContract::class, $sdk);

        $response = $this->postJson('/api/v1/wordpress/token', [
            'username' => 'demo',
            'password' => 'secret',
        ]);

        $response->assertOk();

        $response->assertJson([
            'ok' => true,
            'code' => 'wordpress.token_created',
            'status' => 201,
            'message' => 'Token stored successfully.',
        ]);

        $this->assertDatabaseCount('wp_tokens', 1);

        /** @var WpToken $token */
        $token = WpToken::query()->firstOrFail();
        $this->assertSame($token->id, $response->json('data.id'));
        $this->assertSame('demo', $token->username);
        $this->assertSame('demo-token', $token->token);
        $this->assertSame('demo@example.com', $token->payload['user_email']);
    }

    public function test_it_returns_failure_envelope_when_sdk_fails(): void
    {
        $exception = new WordPressRequestException('Upstream failure', 502);

        $sdk = Mockery::mock(SdkContract::class);
        $sdk
            ->shouldReceive('token')
            ->once()
            ->with('demo', 'invalid')
            ->andThrow($exception);

        $this->app->instance(SdkContract::class, $sdk);

        $response = $this->postJson('/api/v1/wordpress/token', [
            'username' => 'demo',
            'password' => 'invalid',
        ]);

        $response->assertOk();

        $response->assertJson([
            'ok' => false,
            'code' => 'wordpress.token_failed',
            'status' => 502,
            'message' => 'Upstream failure',
        ]);

        $this->assertSame(502, $response->json('meta.source_status'));
        $this->assertDatabaseCount('wp_tokens', 0);
    }
}

