# Mock Usage Policy

> **Core Principle:** Use real implementations by default. Mock only when necessary.

This project follows a strict policy for when to use mocks vs real implementations in tests. The goal is to maximize test confidence by testing actual behavior while isolating only external dependencies.

> **Note:** This policy extends and refines the general mocking standards in `ai-workflow/reference/standards.md`. For project-specific testing patterns, follow this guide.

---

## When to Use Real Implementations

✅ **ALWAYS use real implementations for:**

- **Repositories** - Test with real database using `RefreshDatabase` trait
- **Services** - Test with real service implementations
- **Internal dependencies** - Any class within the application (`App\`, `Modules\`)
- **Models** - Use factories and real database interactions
- **FormRequests** - Test with real validation logic
- **Loggers** - Use real logger instances (mock the underlying Log facade if needed)

**Rationale:** Real implementations test actual behavior, catch integration issues, and provide better confidence that the code works correctly.

---

## When to Use Mocks

✅ **ONLY mock for:**

- **3rd Party APIs/SDKs** - External services (WordPress SDK, LM Studio SDK, payment gateways, etc.)
- **Live services** - Services that require network access, authentication, or have rate limits
- **Expensive operations** - Operations that are slow or resource-intensive (file I/O, heavy computations)
- **Unavailable dependencies** - Dependencies that cannot be accessed in test environment
- **Facades** - Mock Laravel facades (Log, Cache, Queue, etc.) when you need to verify calls

**Rationale:** Mocks isolate external dependencies and prevent tests from depending on external availability or causing side effects.

---

## Decision Tree

```
Should I mock this dependency?
│
├─ Is it a 3rd party API/SDK? → ✅ YES, Mock
│
├─ Does it require network access? → ✅ YES, Mock
│
├─ Is it expensive/slow? → ✅ YES, Mock
│
├─ Is it unavailable in test environment? → ✅ YES, Mock
│
├─ Is it a Repository? → ❌ NO, Use Real (with RefreshDatabase)
│
├─ Is it a Service? → ❌ NO, Use Real
│
├─ Is it a Model? → ❌ NO, Use Real (with factories)
│
├─ Is it a Logger? → ❌ NO, Use Real (mock Log facade if needed)
│
└─ Is it internal to application? → ❌ NO, Use Real
```

---

## Examples

### ❌ WRONG: Mocking Repository

```php
// ❌ BAD: Mocking repository unnecessarily
public function test_create_calls_repository(): void
{
    $repository = Mockery::mock(UserRepositoryContract::class);
    $repository->shouldReceive('create')
        ->once()
        ->andReturn(new User(['id' => 1]));
    
    $service = new UserService($repository, $logger);
    $result = $service->create($data);
    
    $this->assertInstanceOf(User::class, $result);
}
```

**Problem:** Repository is internal dependency. Mocking hides real database behavior and integration issues.

### ✅ CORRECT: Using Real Repository

```php
// ✅ GOOD: Use real repository with database
use Illuminate\Foundation\Testing\RefreshDatabase;

final class UserServiceTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_create_saves_user_to_database(): void
    {
        $repository = new UserRepository();
        $logger = new ActionLogger();
        $service = new UserService($repository, $logger);
        
        $result = $service->create(['name' => 'John', 'email' => 'john@example.com']);
        
        $this->assertDatabaseHas('users', ['name' => 'John']);
        $this->assertInstanceOf(User::class, $result);
    }
}
```

**Benefit:** Tests actual database interaction, catches real integration issues.

### ✅ CORRECT: Mocking 3rd Party SDK

```php
// ✅ GOOD: Mock external SDK (3rd party)
public function test_sync_calls_wordpress_sdk(): void
{
    $sdk = Mockery::mock(WordPressSdkContract::class);
    $sdk->shouldReceive('createPost')
        ->once()
        ->andReturn(['id' => 123]);
    
    $repository = new PostRepository();
    $logger = new ActionLogger();
    $service = new PostService($repository, $logger, $sdk);
    $result = $service->syncToWordPress($post);
    
    $this->assertEquals(123, $result['id']);
}
```

**Rationale:** WordPress SDK is external dependency. Mocking prevents real API calls during tests.

### ✅ CORRECT: Using Real Logger with Mocked Facade

```php
// ✅ GOOD: Use real ActionLogger, mock Log facade if needed
use Illuminate\Support\Facades\Log;

public function test_create_logs_action(): void
{
    Log::shouldReceive('channel')
        ->once()
        ->with('action')
        ->andReturnSelf();
    
    Log::shouldReceive('info')
        ->once()
        ->with('Domain action recorded', Mockery::hasKey('operation'));
    
    $logger = new ActionLogger();
    $service = new UserService($repository, $logger);
    
    $service->create(['name' => 'John']);
}
```

**Rationale:** ActionLogger is internal, but we mock the Log facade to verify logging behavior without side effects.

---

## Quick Reference Table

| Dependency Type | Mock? | Example |
|----------------|-------|---------|
| Repository | ❌ NO | Use real with `RefreshDatabase` |
| Service | ❌ NO | Use real implementation |
| Model | ❌ NO | Use factories and real database |
| Logger (internal) | ❌ NO | Use real, mock Log facade if needed |
| 3rd Party SDK | ✅ YES | WordPress SDK, LM Studio SDK |
| Live API | ✅ YES | Payment gateway, external API |
| Laravel Facade | ✅ YES (when verifying) | Log, Cache, Queue |

---

## Test Structure Examples

### Unit Test with Real Dependencies

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Logging\ActionLogger;
use App\Services\UserService;
use App\Repositories\UserRepository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserServiceTest extends TestCase
{
    use RefreshDatabase;
    
    private UserService $service;
    private UserRepository $repository;
    private ActionLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Use real implementations for internal dependencies
        $this->repository = new UserRepository();
        $this->logger = new ActionLogger();
        $this->service = new UserService($this->repository, $this->logger);
    }
    
    public function test_create_saves_user_to_database(): void
    {
        // Arrange
        $userData = ['name' => 'John Doe', 'email' => 'john@example.com'];
        
        // Act
        $result = $this->service->create($userData);
        
        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }
}
```

### Unit Test with External SDK (Mock Required)

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Logging\ActionLogger;
use App\Repositories\PostRepository;
use App\Services\PostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Services\WordPress\Contracts\SdkContract;
use Mockery;
use Tests\TestCase;

final class PostServiceTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_sync_calls_wordpress_sdk(): void
    {
        // Arrange: Mock external SDK
        $sdk = Mockery::mock(SdkContract::class);
        $sdk->shouldReceive('createPost')
            ->once()
            ->andReturn(['id' => 123]);
        
        // Use real internal dependencies
        $repository = new PostRepository();
        $logger = new ActionLogger();
        $service = new PostService($repository, $logger, $sdk);
        
        // Act
        $result = $service->syncToWordPress($post);
        
        // Assert
        $this->assertEquals(123, $result['id']);
    }
}
```

---

## Comparison with ai-workflow Standards

This policy extends the general mocking standards in `ai-workflow/reference/standards.md`:

| Aspect | ai-workflow Standard | This Project Policy |
|--------|---------------------|---------------------|
| Repositories | Mock in unit tests | Use real with RefreshDatabase |
| Services | Mock in unit tests | Use real implementation |
| Loggers | Can mock ActionLogger | Use real, mock Log facade if needed |
| External SDKs | Mock | Mock (same) |
| Feature Tests | Real HTTP/database, mock external | Real HTTP/database, mock external (same) |

**Rationale for differences:**
- Real implementations provide better test confidence
- Catch integration issues early
- Tests actual behavior, not just mocks
- Database operations are fast with RefreshDatabase

---

## Related Documentation

- [Plan: Mock Usage Policy](../plans/technical/2025-11-15-mock-usage-policy.md) - Implementation plan
- [ai-workflow Testing Patterns](../../ai-workflow/guides/testing-patterns.md) - General testing patterns
- [ai-workflow Mocking Standards](../../ai-workflow/reference/standards.md#mocking-standards) - Base mocking standards

---

**Last Updated:** 2025-11-17

