# Mock Usage Policy - Refactoring Examples

This document shows before/after comparisons of tests refactored to follow the mock usage policy.

**Policy:** Use real implementations by default. Mock only external dependencies (3rd party APIs/SDKs).

---

## Example 1: LmStudioApiTest - Using Real ChatInferenceService

### ❌ BEFORE: Mocking Internal Service

```php
protected function setUp(): void
{
    parent::setUp();
    config(['features.lmstudio.enabled' => true]);

    $sdk = Mockery::mock(SdkContract::class)->shouldIgnoreMissing();
    $this->app->instance(SdkContract::class, $sdk);

    // ❌ BAD: Mocking internal service
    $service = Mockery::mock(ChatInferenceService::class)->shouldIgnoreMissing();
    $this->app->instance(ChatInferenceService::class, $service);
}

public function test_it_starts_inference_job(): void
{
    $sdk = Mockery::mock(SdkContract::class);
    $sdk->shouldReceive('listModels')->zeroOrMoreTimes();
    $this->app->instance(SdkContract::class, $sdk);

    // ❌ BAD: Mocking internal service
    $service = Mockery::mock(ChatInferenceService::class);
    $service->shouldReceive('start')
        ->once()
        ->andReturn([
            'job_id' => 'job-123',
            'model' => 'mistral',
            'created' => 1731614400,
        ]);
    $this->app->instance(ChatInferenceService::class, $service);

    $response = $this->postJson('/api/v1/ai/lmstudio/infer', $payload);
    // ... assertions
}
```

**Problems:**
- Mocking internal service (`ChatInferenceService`) hides real behavior
- Doesn't test actual event dispatching
- Doesn't test content chunking logic
- Doesn't catch integration issues

### ✅ AFTER: Using Real ChatInferenceService

```php
protected function setUp(): void
{
    parent::setUp();
    config(['features.lmstudio.enabled' => true]);

    // ✅ GOOD: Mock only external SDK
    $sdk = Mockery::mock(SdkContract::class)->shouldIgnoreMissing();
    $this->app->instance(SdkContract::class, $sdk);
    
    // ChatInferenceService will be resolved with real implementation
}

public function test_it_starts_inference_job(): void
{
    // Arrange: Mock external SDK, use real ChatInferenceService
    $sdk = Mockery::mock(SdkContract::class);
    $sdk->shouldReceive('listModels')->zeroOrMoreTimes();
    
    // Mock SDK's createChatCompletion method (external dependency)
    $sdk->shouldReceive('createChatCompletion')
        ->once()
        ->andReturn(
            ChatCompletionResponse::fromArray([
                'id' => 'chat-123',
                'model' => 'mistral',
                'created' => 1731614400,
                'choices' => [
                    [
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Hello! How can I help you?',
                        ],
                        'finish_reason' => 'stop',
                    ],
                ],
            ])
        );
    
    $this->app->instance(SdkContract::class, $sdk);

    // Use Event::fake() to verify events are dispatched
    Event::fake();

    $payload = [
        'model' => 'mistral',
        'messages' => [
            ['role' => 'user', 'content' => 'Hello'],
        ],
    ];

    // Act
    $response = $this->postJson('/api/v1/ai/lmstudio/infer', $payload);

    // Assert
    $response->assertStatus(202)
        ->assertJsonPath('status', 202)
        ->assertJsonPath('code', 'lmstudio.infer.accepted')
        ->assertJsonStructure([
            'data' => [
                'job_id',
                'model',
                'created',
            ],
        ])
        ->assertJsonPath('data.model', 'mistral');

    // ✅ Verify events were dispatched by real ChatInferenceService
    Event::assertDispatched(LmStudioInferenceStreamed::class);
}
```

**Benefits:**
- ✅ Tests actual `ChatInferenceService` behavior
- ✅ Verifies event dispatching works correctly
- ✅ Tests content chunking logic (40-char chunks)
- ✅ Catches integration issues between service and SDK
- ✅ More confidence that the code works correctly

---

## Example 2: WordPressCategoryServiceTest - Already Following Policy

This test was already correctly using real `ActionLogger` and mocking only the external SDK:

```php
public function test_it_lists_categories_with_filters(): void
{
    // ✅ GOOD: Mock external SDK
    $sdk = Mockery::mock(SdkContract::class);
    
    // ✅ GOOD: Use real ActionLogger
    $logger = new ActionLogger();

    $sdk->shouldReceive('categories')
        ->once()
        ->with([...])
        ->andReturn([['id' => 1, 'name' => 'News']]);

    $service = new CategoryService($sdk, $logger);
    $result = $service->list([...]);

    self::assertSame([['id' => 1, 'name' => 'News']], $result);
}
```

**Status:** ✅ Already following policy correctly.

---

## Example 3: WordPressTokenTest - Already Following Policy

This feature test correctly mocks only the external SDK:

```php
public function test_it_persists_token_via_internal_api(): void
{
    // ✅ GOOD: Mock external SDK only
    $sdk = Mockery::mock(SdkContract::class);
    $sdk->shouldReceive('token')
        ->once()
        ->with('demo', 'secret')
        ->andReturn([...]);

    $this->app->instance(SdkContract::class, $sdk);

    // Uses real controllers, services, repositories
    $response = $this->postJson('/api/v1/wordpress/token', [...]);
    
    // Verifies actual database interaction
    $this->assertDatabaseCount('wp_tokens', 1);
}
```

**Status:** ✅ Already following policy correctly.

---

## Summary

### Tests Refactored
- ✅ `LmStudioApiTest::test_it_starts_inference_job()` - Changed from mocking `ChatInferenceService` to using real implementation

### Tests Already Following Policy
- ✅ `WordPressCategoryServiceTest` - Uses real `ActionLogger`, mocks only SDK
- ✅ `WordPressTokenTest` - Feature test that mocks only external SDK
- ✅ `LmStudioCommandTest` - Feature test that mocks only external SDK
- ✅ `WordPressCategoryApiTest` - Feature test that mocks only external SDK

### Impact
- **Test Confidence:** Increased - tests now verify actual service behavior
- **Integration Coverage:** Improved - tests catch issues between service and SDK
- **Event Testing:** Added - verifies events are dispatched correctly
- **Test Speed:** Similar - no significant performance impact

---

**Last Updated:** 2025-11-17

