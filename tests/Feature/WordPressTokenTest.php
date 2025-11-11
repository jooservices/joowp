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
            'remember' => true,
        ]);

        $response->assertOk();

        $response->assertJson([
            'ok' => true,
            'code' => 'wordpress.token_created',
            'status' => 201,
            'message' => 'Token stored successfully.',
            'data' => [
                'remembered' => true,
                'username' => 'demo',
            ],
        ]);

        $this->assertDatabaseCount('wp_tokens', 1);

        /** @var WpToken $token */
        $token = WpToken::query()->firstOrFail();
        $this->assertSame($token->id, $response->json('data.id'));
        $this->assertSame('demo', $token->username);
        $this->assertSame('demo-token', $token->token);
        $this->assertSame('demo@example.com', $token->payload['user_email']);
        $this->assertSame('demo***ken', $response->json('data.masked_token'));
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
            'remember' => true,
        ]);

        $response->assertOk();

        $response->assertJson([
            'ok' => false,
            'code' => 'wordpress.token_failed',
            'status' => 502,
            'message' => 'Upstream failure',
            'meta' => [
                'remembered' => false,
            ],
        ]);

        $this->assertSame(502, $response->json('meta.source_status'));
        $this->assertDatabaseCount('wp_tokens', 0);
    }

    public function test_it_does_not_store_token_when_not_remembered(): void
    {
        $sdk = Mockery::mock(SdkContract::class);
        $sdk
            ->shouldReceive('token')
            ->once()
            ->with('demo', 'secret')
            ->andReturn([
                'token' => 'demo-token',
            ]);

        $this->app->instance(SdkContract::class, $sdk);

        $response = $this->postJson('/api/v1/wordpress/token', [
            'username' => 'demo',
            'password' => 'secret',
            'remember' => false,
        ]);

        $response->assertOk();
        $response->assertJson([
            'ok' => true,
            'code' => 'wordpress.token_created',
            'status' => 201,
            'message' => 'Token retrieved successfully.',
            'data' => [
                'remembered' => false,
                'masked_token' => null,
            ],
        ]);

        $this->assertDatabaseCount('wp_tokens', 0);
    }

    public function test_it_returns_absent_state_when_no_token_remembered(): void
    {
        $response = $this->getJson('/api/v1/wordpress/token');

        $response->assertOk();
        $response->assertJson([
            'ok' => true,
            'code' => 'wordpress.token_absent',
            'data' => [
                'remembered' => false,
                'masked_token' => null,
            ],
        ]);
    }

    public function test_it_returns_masked_token_when_token_exists(): void
    {
        WpToken::query()->create([
            'username' => 'demo',
            'token' => 'demo-token',
            'payload' => ['token' => 'demo-token'],
        ]);

        $response = $this->getJson('/api/v1/wordpress/token');

        $response->assertOk();
        $response->assertJson([
            'ok' => true,
            'code' => 'wordpress.token_remembered',
            'data' => [
                'remembered' => true,
                'masked_token' => 'demo***ken',
                'username' => 'demo',
            ],
        ]);
    }

    public function test_it_clears_remembered_token(): void
    {
        WpToken::query()->create([
            'username' => 'demo',
            'token' => 'demo-token',
            'payload' => ['token' => 'demo-token'],
        ]);

        $response = $this->deleteJson('/api/v1/wordpress/token');

        $response->assertOk();
        $response->assertJson([
            'ok' => true,
            'code' => 'wordpress.token_cleared',
            'data' => [
                'remembered' => false,
                'masked_token' => null,
                'username' => null,
            ],
        ]);

        $this->assertDatabaseCount('wp_tokens', 0);
    }
}

