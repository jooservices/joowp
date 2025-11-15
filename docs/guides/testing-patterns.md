# Testing Patterns Guide

Complete guide to writing unit tests, feature tests, and integration tests following project standards.

> **Related Documentation:**
> - [Test Coverage Standards](../reference/standards.md#test-coverage-standards) - Coverage targets and requirements
> - [Testing Principles](../architecture/principles.md#4-test-coverage) - Why we test and testing philosophy
> - [Development Guidelines](../development/guidelines.md#test-coverage-implementation) - Step-by-step test writing

---

## Overview

This project follows the **Test Pyramid** strategy:
- **Many Unit Tests** (fast, isolated, test business logic)
- **Fewer Feature Tests** (slower, test HTTP endpoints and workflows)
- **Minimal E2E Tests** (slowest, test complete user flows)

**Coverage Targets:**
- Overall: 80% minimum
- Core Services: 95%
- Controllers: 90%
- FormRequests: 100%
- Models: 85%

---

## Unit Test Patterns

### Test File Structure

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Logging\ActionLogger;
use App\Services\UserService;
use App\Repositories\Contracts\UserRepositoryContract;
use App\Models\User;
use Tests\TestCase;
use Mockery;

final class UserServiceTest extends TestCase
{
    private UserService $service;
    private UserRepositoryContract $repository;
    private ActionLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Arrange: Create mocks
        $this->repository = Mockery::mock(UserRepositoryContract::class);
        $this->logger = Mockery::mock(ActionLogger::class);
        $this->service = new UserService($this->repository, $this->logger);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

### Arrange-Act-Assert Pattern

**Always structure tests with three clear phases:**

```php
public function test_create_with_valid_data_returns_user(): void
{
    // Arrange: Set up test data and expectations
    $userData = ['name' => 'John Doe', 'email' => 'john@example.com'];
    $expectedUser = new User(['id' => 1, ...$userData]);
    
    $this->repository
        ->expects('create')
        ->once()
        ->with($userData)
        ->andReturn($expectedUser);
    
    $this->logger
        ->expects('log')
        ->once()
        ->with('user.created', null, [], $expectedUser->toArray());
    
    // Act: Execute the method under test
    $result = $this->service->create($userData);
    
    // Assert: Verify the results
    $this->assertInstanceOf(User::class, $result);
    $this->assertEquals('John Doe', $result->name);
    $this->assertEquals('john@example.com', $result->email);
}
```

### Testing Happy Paths

```php
public function test_list_returns_all_users(): void
{
    // Arrange
    $users = [
        new User(['id' => 1, 'name' => 'John']),
        new User(['id' => 2, 'name' => 'Jane']),
    ];
    
    $this->repository
        ->expects('all')
        ->once()
        ->andReturn($users);
    
    // Act
    $result = $this->service->list();
    
    // Assert
    $this->assertCount(2, $result);
    $this->assertEquals('John', $result[0]->name);
}
```

### Testing Error Cases

```php
public function test_create_with_invalid_email_throws_exception(): void
{
    // Arrange
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid email format');
    
    $invalidData = ['name' => 'John', 'email' => 'invalid-email'];
    
    // Act
    $this->service->create($invalidData);
}

public function test_create_when_repository_fails_throws_exception(): void
{
    // Arrange
    $userData = ['name' => 'John', 'email' => 'john@example.com'];
    
    $this->repository
        ->expects('create')
        ->once()
        ->andThrow(new \RuntimeException('Database error'));
    
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Database error');
    
    // Act
    $this->service->create($userData);
}
```

### Testing Edge Cases

```php
public function test_create_with_empty_name_throws_exception(): void
{
    $this->expectException(\InvalidArgumentException::class);
    $this->service->create(['name' => '', 'email' => 'john@example.com']);
}

public function test_list_when_no_users_returns_empty_array(): void
{
    $this->repository
        ->expects('all')
        ->once()
        ->andReturn([]);
    
    $result = $this->service->list();
    
    $this->assertIsArray($result);
    $this->assertEmpty($result);
}

public function test_create_with_null_data_throws_type_error(): void
{
    $this->expectException(\TypeError::class);
    $this->service->create(null);
}
```

### Mocking Strategies

#### Mocking Dependencies

```php
// Mock repository
$repository = Mockery::mock(UserRepositoryContract::class);
$repository->shouldReceive('find')
    ->once()
    ->with(1)
    ->andReturn(new User(['id' => 1]));

// Mock logger
$logger = Mockery::mock(ActionLogger::class);
$logger->shouldReceive('log')
    ->once()
    ->with('user.updated', Mockery::any(), [], []);

// Mock SDK
$sdk = Mockery::mock(SdkContract::class);
$sdk->shouldReceive('posts')
    ->once()
    ->andReturn([['id' => 1, 'title' => 'Post']]);
```

#### Using Spies (Verify Calls Without Expectations)

```php
// Spy on logger - verify it was called
$logger = Mockery::spy(ActionLogger::class);
$service = new UserService($repository, $logger);

$service->create($data);

$logger->shouldHaveReceived('log')
    ->once()
    ->with('user.created', null, [], Mockery::any());
```

#### Partial Mocks (Mock Some Methods, Keep Others Real)

```php
// Partial mock - only mock specific methods
$service = Mockery::mock(UserService::class)->makePartial();
$service->shouldReceive('validateEmail')
    ->once()
    ->andReturn(true);

// Other methods use real implementation
$result = $service->create($data);
```

### Testing Services with External APIs

```php
public function test_sync_with_external_api_logs_on_failure(): void
{
    // Arrange
    $product = new Product(['id' => 1, 'name' => 'Test']);
    $exception = new \GuzzleHttp\Exception\RequestException('API error', new \GuzzleHttp\Psr7\Request('POST', '/api'));
    
    $this->sdk
        ->expects('updateProduct')
        ->once()
        ->andThrow($exception);
    
    $this->logger
        ->expects('log')
        ->once()
        ->with('product.sync_failed', Mockery::any(), [], Mockery::hasKey('error'));
    
    $this->expectException(\RuntimeException::class);
    
    // Act
    $this->service->syncWithExternalApi($product);
}
```

---

## Feature Test Patterns

### Test File Structure

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    // Tests here...
}
```

### Testing HTTP Endpoints

```php
public function test_store_creates_category_and_returns_resource(): void
{
    // Arrange
    $user = User::factory()->create();
    $categoryData = ['name' => 'News', 'slug' => 'news'];
    
    // Act
    $response = $this->actingAs($user)
        ->postJson('/api/v1/wordpress/categories', $categoryData);
    
    // Assert
    $response->assertStatus(201)
        ->assertJsonStructure([
            'ok',
            'code',
            'status',
            'message',
            'data' => ['id', 'name', 'slug'],
        ])
        ->assertJson([
            'ok' => true,
            'code' => 'category.created',
            'data' => ['name' => 'News'],
        ]);
    
    $this->assertDatabaseHas('wp_categories', [
        'name' => 'News',
        'slug' => 'news',
    ]);
}
```

### Testing Validation

```php
public function test_store_rejects_invalid_data(): void
{
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)
        ->postJson('/api/v1/wordpress/categories', [
            'name' => '', // Invalid: empty name
            'slug' => 'news',
        ]);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name'])
        ->assertJson([
            'ok' => false,
            'code' => 'validation.failed',
        ]);
}
```

### Testing Authentication

```php
public function test_store_requires_authentication(): void
{
    $response = $this->postJson('/api/v1/wordpress/categories', [
        'name' => 'News',
        'slug' => 'news',
    ]);
    
    $response->assertStatus(401);
}

public function test_store_with_authenticated_user_succeeds(): void
{
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)
        ->postJson('/api/v1/wordpress/categories', [
            'name' => 'News',
            'slug' => 'news',
        ]);
    
    $response->assertStatus(201);
}
```

### Testing Error Responses

```php
public function test_store_returns_error_when_service_fails(): void
{
    // Arrange: Mock service to throw exception
    $this->app->instance(
        CategoryService::class,
        Mockery::mock(CategoryService::class)
            ->shouldReceive('create')
            ->once()
            ->andThrow(new \RuntimeException('Service error'))
            ->getMock()
    );
    
    $user = User::factory()->create();
    
    // Act
    $response = $this->actingAs($user)
        ->postJson('/api/v1/wordpress/categories', [
            'name' => 'News',
            'slug' => 'news',
        ]);
    
    // Assert
    $response->assertStatus(500)
        ->assertJson([
            'ok' => false,
            'code' => 'category.service_error',
        ]);
}
```

### Testing Feature Flags

```php
public function test_infer_returns_503_when_feature_disabled(): void
{
    // Arrange: Disable feature
    config(['features.lmstudio.enabled' => false]);
    
    $user = User::factory()->create();
    
    // Act
    $response = $this->actingAs($user)
        ->postJson('/api/v1/ai/lmstudio/infer', [
            'messages' => [['role' => 'user', 'content' => 'Hello']],
        ]);
    
    // Assert
    $response->assertStatus(503)
        ->assertJson([
            'ok' => false,
            'code' => 'lmstudio.disabled',
        ]);
}
```

---

## FormRequest Testing

### 100% Coverage Required

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use Modules\WordPress\Http\Requests\StoreCategoryRequest;
use Tests\TestCase;
use Illuminate\Support\Facades\Validator;

final class StoreCategoryRequestTest extends TestCase
{
    public function test_rules_require_name(): void
    {
        $request = new StoreCategoryRequest();
        $rules = $request->rules();
        
        $this->assertArrayHasKey('name', $rules);
        $this->assertContains('required', $rules['name']);
        $this->assertContains('string', $rules['name']);
    }
    
    public function test_validation_passes_with_valid_data(): void
    {
        $data = ['name' => 'News', 'slug' => 'news'];
        $validator = Validator::make($data, (new StoreCategoryRequest())->rules());
        
        $this->assertTrue($validator->passes());
    }
    
    public function test_validation_fails_with_empty_name(): void
    {
        $data = ['name' => '', 'slug' => 'news'];
        $validator = Validator::make($data, (new StoreCategoryRequest())->rules());
        
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }
    
    public function test_validation_fails_with_missing_name(): void
    {
        $data = ['slug' => 'news'];
        $validator = Validator::make($data, (new StoreCategoryRequest())->rules());
        
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }
}
```

---

## Test Naming Conventions

### Test Method Names

**Format:** `test_{method_name}_{scenario}_{expected_outcome}()`

**Examples:**
```php
test_create_with_valid_data_returns_user()
test_create_with_invalid_email_throws_exception()
test_list_when_no_users_returns_empty_array()
test_store_creates_category_and_returns_resource()
test_store_rejects_invalid_data()
test_store_requires_authentication()
```

### Test File Names

- Unit tests: `tests/Unit/{Namespace}/{ClassName}Test.php`
- Feature tests: `tests/Feature/{Module}/{Feature}Test.php`

**Examples:**
- `tests/Unit/Services/UserServiceTest.php`
- `tests/Feature/WordPress/CategoryApiTest.php`
- `tests/Unit/Http/Requests/StoreCategoryRequestTest.php`

---

## Common Testing Patterns

### Testing with Factories

```php
// Use factories for test data
$user = User::factory()->create();
$product = Product::factory()->create(['user_id' => $user->id]);

// Override factory attributes
$admin = User::factory()->admin()->create();
```

### Testing Database Interactions

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

final class ProductTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_create_persists_to_database(): void
    {
        $product = Product::factory()->create();
        
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => $product->name,
        ]);
    }
}
```

### Testing Events

```php
Event::fake();

$service->create($data);

Event::assertDispatched(UserCreated::class, function ($event) {
    return $event->user->email === 'john@example.com';
});
```

### Testing Queues

```php
Queue::fake();

$service->dispatchJob($data);

Queue::assertPushed(ProcessJob::class, function ($job) {
    return $job->data['type'] === 'sync';
});
```

---

## Best Practices

### ✅ DO

- **Test behavior, not implementation** - Focus on what the code does, not how
- **Use descriptive test names** - Test name should explain what is being tested
- **One assertion per test** - Or group related assertions logically
- **Test edge cases** - Empty arrays, null values, boundary conditions
- **Mock external dependencies** - Isolate units under test
- **Use factories** - Generate test data consistently
- **Clean up after tests** - Use `RefreshDatabase` or `Mockery::close()`

### ❌ DON'T

- **Don't test framework code** - Laravel is already tested
- **Don't test private methods** - Test through public API
- **Don't use real external APIs** - Always mock SDK calls
- **Don't share test state** - Each test should be independent
- **Don't skip tests** - Fix failing tests, don't mark them as skipped
- **Don't test implementation details** - Test observable behavior

---

## Coverage Commands

```bash
# Run all tests
composer test

# Generate HTML coverage report
composer test:coverage
# Open: storage/coverage/index.html

# Enforce coverage thresholds (CI gate)
composer test:coverage-check

# Run specific test file
composer test -- tests/Unit/Services/UserServiceTest.php

# Run tests with coverage in terminal
composer test -- --coverage-text

# Run specific test method
composer test -- --filter test_create_with_valid_data_returns_user
```

---

## Related Documentation

- [Test Coverage Standards](../reference/standards.md#test-coverage-standards) - Coverage targets
- [Testing Principles](../architecture/principles.md#4-test-coverage) - Why we test
- [Development Guidelines](../development/guidelines.md#test-coverage-implementation) - More examples

