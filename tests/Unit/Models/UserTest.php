<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_has_fillable_attributes(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $this->assertSame('John Doe', $user->name);
        $this->assertSame('john@example.com', $user->email);
        $this->assertNotNull($user->password);
    }

    public function test_it_hides_password_and_remember_token(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    public function test_it_hashes_password_automatically(): void
    {
        $user = User::factory()->create([
            'password' => 'plaintext-password',
        ]);

        $this->assertNotSame('plaintext-password', $user->password);
        $this->assertTrue(Hash::check('plaintext-password', $user->password));
    }

    public function test_it_casts_email_verified_at_to_datetime(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => '2025-01-15 10:30:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->email_verified_at);
        $this->assertSame('2025-01-15 10:30:00', $user->email_verified_at->format('Y-m-d H:i:s'));
    }

    public function test_it_casts_password_to_hashed(): void
    {
        $user = new User();
        $user->password = 'plaintext-password';

        $this->assertNotSame('plaintext-password', $user->password);
        $this->assertTrue(Hash::check('plaintext-password', $user->password));
    }

    public function test_it_can_have_null_email_verified_at(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->assertNull($user->email_verified_at);
    }
}
