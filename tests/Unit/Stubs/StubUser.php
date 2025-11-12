<?php

declare(strict_types=1);

namespace Tests\Unit\Stubs;

use Illuminate\Contracts\Auth\Authenticatable;

final class StubUser implements Authenticatable
{
    public function __construct(private readonly int $identifier)
    {
        // No-op constructor.
    }

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

    public function getRememberToken(): ?string
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
