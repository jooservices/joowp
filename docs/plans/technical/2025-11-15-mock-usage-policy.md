# Plan – Mock Usage Policy & Testing Strategy

Status: Active  
Priority: P1  
Owner: Platform Engineering  
Created: 2025-11-15  
Updated: 2025-11-15  
Target: 2025-11-22  
Epic: Code Quality

## Summary
Establish clear policy and guidelines for when to use mocks vs real implementations in tests. Reduce unnecessary mocking of internal dependencies (repositories, services) and reserve mocks for external dependencies (3rd party APIs, live services) that cannot be accessed or should be limited in tests.

**Scope:** Documentation updates, test refactoring examples, and enforcement guidelines. No breaking changes to existing tests.

## Dependencies
- `docs/guides/testing-patterns.md` - Existing testing guide
- `docs/architecture/principles.md` - Testing principles
- Existing test suite for examples and refactoring

## Objectives
- Define clear criteria for when mocks are required vs when real implementations should be used
- Provide examples of correct vs incorrect mock usage
- Refactor existing tests that overuse mocks unnecessarily
- Update documentation to reflect new policy
- Establish enforcement guidelines for code reviews

## Core Principle

**Use real implementations by default. Mock only when necessary.**

### When to Use Real Implementations

✅ **ALWAYS use real implementations for:**
- **Repositories** - Test with real database (use `RefreshDatabase`)
- **Services** - Test with real service implementations
- **Internal dependencies** - Any class within the application
- **Models** - Use factories and real database
- **FormRequests** - Test with real validation logic

**Rationale:** Real implementations test actual behavior, catch integration issues, and provide better confidence.

### When to Use Mocks

✅ **ONLY mock for:**
- **3rd Party APIs/SDKs** - External services (WordPress SDK, LM Studio SDK, etc.)
- **Live services** - Services that require network access, authentication, or have rate limits
- **Expensive operations** - Operations that are slow or resource-intensive (file I/O, heavy computations)
- **Unavailable dependencies** - Dependencies that cannot be accessed in test environment

**Rationale:** Mocks isolate external dependencies and prevent tests from depending on external availability.

## Tasks

- [ ] Phase 0 – Policy Definition
  - DoD: Clear policy document defining when to mock vs use real implementations
  - DoD: Examples of correct vs incorrect usage patterns
  - DoD: Decision tree/flowchart for mock usage decisions
  - Estimated: 2 hours
  - Status: In Progress

- [ ] Phase 1 – Documentation Updates
  - DoD: Update `docs/guides/testing-patterns.md` with mock usage policy
  - DoD: Add examples showing real implementation usage for repositories/services
  - DoD: Update `docs/development/guidelines.md` with mock usage guidelines
  - DoD: Create decision tree/guide for developers
  - Estimated: 3 hours
  - Status: Pending

- [ ] Phase 2 – Test Refactoring Examples
  - DoD: Identify 3-5 examples of tests overusing mocks
  - DoD: Refactor examples to use real implementations
  - DoD: Document before/after comparisons
  - DoD: Measure impact (test speed, coverage, confidence)
  - Estimated: 4 hours
  - Status: Pending

- [ ] Phase 3 – Enforcement & Guidelines
  - DoD: Add mock usage policy to code review checklist
  - DoD: Update `.github/copilot-instructions.md` with mock usage rules
  - DoD: Create linting/static analysis rules if possible
  - DoD: Document exceptions and edge cases
  - Estimated: 2 hours
  - Status: Pending

**Total Estimated Effort:** 11 hours (~1.5 days for 1 developer)

## Examples

### ❌ WRONG: Mocking Repository in Service Test

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

### ✅ CORRECT: Using Real Repository in Service Test

```php
// ✅ GOOD: Use real repository with database
use Illuminate\Foundation\Testing\RefreshDatabase;

public function test_create_saves_user_to_database(): void
{
    $repository = new UserRepository();
    $logger = Mockery::mock(ActionLogger::class);
    $service = new UserService($repository, $logger);
    
    $result = $service->create(['name' => 'John', 'email' => 'john@example.com']);
    
    $this->assertDatabaseHas('users', ['name' => 'John']);
    $this->assertInstanceOf(User::class, $result);
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
    
    $service = new PostService($repository, $sdk);
    $result = $service->syncToWordPress($post);
    
    $this->assertEquals(123, $result['id']);
}
```

**Rationale:** WordPress SDK is external dependency. Mocking prevents real API calls during tests.

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
└─ Is it internal to application? → ❌ NO, Use Real
```

## Success Metrics
- **Test Confidence:** Tests using real implementations catch more integration issues
- **Test Speed:** Real implementations may be slower but provide better value
- **Code Quality:** Reduced mock complexity improves test readability
- **Coverage:** Real implementations test actual behavior, not just mocks

## Risks & Mitigations
| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Tests become slower | Medium | Medium | Use RefreshDatabase efficiently, optimize test data |
| Tests become flaky | Medium | Low | Ensure test isolation, use transactions |
| Over-mocking still occurs | Low | Medium | Code review enforcement, documentation |
| Developers confused | Low | Low | Clear examples and decision tree |

## Related Plans
- `docs/guides/testing-patterns.md` - Testing patterns guide
- `docs/development/guidelines.md` - Development guidelines
- `docs/architecture/principles.md` - Testing principles

## Notes
- This is a policy/guideline change, not a breaking change
- Existing tests can be refactored gradually
- Focus on new tests following the policy
- Real implementations provide better test confidence than mocks



