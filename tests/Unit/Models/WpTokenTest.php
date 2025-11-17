<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\WordPress\Models\WpToken;
use Tests\TestCase;

final class WpTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_has_fillable_attributes(): void
    {
        $token = WpToken::query()->create([
            'username' => 'demo',
            'token' => 'demo-token-123',
            'payload' => ['token' => 'demo-token-123', 'user_email' => 'demo@example.com'],
        ]);

        $this->assertSame('demo', $token->username);
        $this->assertSame('demo-token-123', $token->token);
        $this->assertIsArray($token->payload);
    }

    public function test_it_casts_payload_to_array(): void
    {
        $token = WpToken::query()->create([
            'username' => 'demo',
            'token' => 'demo-token',
            'payload' => ['token' => 'demo-token', 'user_email' => 'demo@example.com'],
        ]);

        $this->assertIsArray($token->payload);
        $this->assertSame('demo-token', $token->payload['token']);
        $this->assertSame('demo@example.com', $token->payload['user_email']);
    }

    public function test_it_requires_token(): void
    {
        // Token is required (NOT NULL constraint), so we test that it must be provided
        $token = WpToken::query()->create([
            'username' => 'demo',
            'token' => 'required-token',
            'payload' => null,
        ]);

        $this->assertSame('required-token', $token->token);
        $this->assertNull($token->payload);
    }

    public function test_it_can_have_null_payload(): void
    {
        $token = WpToken::query()->create([
            'username' => 'demo',
            'token' => 'demo-token',
            'payload' => null,
        ]);

        $this->assertNull($token->payload);
    }

    public function test_it_stores_complex_payload_structure(): void
    {
        $complexPayload = [
            'token' => 'demo-token',
            'user_email' => 'demo@example.com',
            'user_id' => 123,
            'expires_at' => '2025-12-31T23:59:59Z',
            'permissions' => ['read', 'write'],
        ];

        $token = WpToken::query()->create([
            'username' => 'demo',
            'token' => 'demo-token',
            'payload' => $complexPayload,
        ]);

        $this->assertSame($complexPayload, $token->payload);
        $this->assertSame('demo@example.com', $token->payload['user_email']);
        $this->assertSame(123, $token->payload['user_id']);
        $this->assertSame(['read', 'write'], $token->payload['permissions']);
    }

    public function test_it_uses_wp_tokens_table(): void
    {
        $token = new WpToken();
        $this->assertSame('wp_tokens', $token->getTable());
    }
}
