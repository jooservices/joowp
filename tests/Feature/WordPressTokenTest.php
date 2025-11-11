<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\WpToken;
use Modules\Core\Services\WordPress\Contracts\SdkContract;
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

        $response
            ->assertCreated()
            ->assertJson([
                'message' => 'Token stored successfully.',
            ]);

        $this->assertDatabaseCount('wp_tokens', 1);

        /** @var WpToken $token */
        $token = WpToken::query()->firstOrFail();
        $this->assertSame('demo', $token->username);
        $this->assertSame('demo-token', $token->token);
        $this->assertSame('demo@example.com', $token->payload['user_email']);
    }
}

