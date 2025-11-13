# Documentation Structure Demo - Principle → Guideline → Rule/Standard

This demonstrates the clear 3-tier hierarchy for organizing project requirements.

---

## Example 1: Type Safety

### 🎯 Principle: Type Safety
**What you must do:** All code must be type-safe with no implicit type coercion or ambiguous types.

**Why:** Type safety prevents runtime errors, improves IDE support, and makes code self-documenting. Explicit types eliminate entire classes of bugs before code runs.

### 📋 Guidelines: How to Achieve Type Safety

#### 1. File-Level Strict Mode
Enable strict type checking for every PHP file to prevent automatic type coercion.

#### 2. Explicit Type Declarations
Declare parameter and return types for all functions/methods. Never rely on type inference for public APIs.

#### 3. Immutable Dependencies
Use readonly properties for injected dependencies to prevent accidental mutation and improve thread safety.

#### 4. Generic Type Annotations
Document array shapes and collection types using PHPDoc to help static analysis tools understand complex structures.

### ⚙️ Rules/Standards: Exact Implementation

#### MANDATORY Requirements:
- ✅ **MUST:** Add `declare(strict_types=1);` immediately after `<?php` in ALL PHP files
  - Applies to: Classes, interfaces, traits, routes, config, migrations, seeders, tests
  - Pre-commit hook enforces this (build fails without it)
  - No exceptions permitted

- ✅ **MUST:** Declare explicit types on all method parameters and return values
  - Forbidden: `public function process($data)` 
  - Required: `public function process(array $data): array`
  - Exception: `mixed` only when interfacing with untyped third-party code (requires inline comment with justification)

- ✅ **MUST:** Use `final` keyword for all classes by default
  - Exception: `abstract` classes designed for inheritance (requires architectural review)

- ✅ **MUST:** Use `readonly` modifier for constructor-injected dependencies
  ```php
  public function __construct(
      private readonly ServiceContract $service,
      private readonly ActionLogger $logger,
  ) {}
  ```

#### PHPDoc Requirements:
- ✅ **MUST:** Document array shapes for complex structures
  ```php
  /**
   * @param array<string, mixed> $data
   * @return array<int, User>
   */
  ```

#### Tool Configuration:
- PHPStan level: `max` (no suppressions without justification)
- PHPStan rules:
  - `checkMissingIterableValueType: true`
  - `checkGenericClassInNonGenericObjectType: true`
  - `reportAnyTypeWideningInVarTag: true`

#### Validation:
- Pre-commit: Checks for `declare(strict_types=1)` presence
- CI/CD: PHPStan must pass at maximum level
- Coverage: Type-related violations = build failure

---

## Example 2: Test Coverage

### 🎯 Principle: Comprehensive Testing
**What you must do:** All code must be covered by automated tests that verify correctness and prevent regressions.

**Why:** Tests serve as living documentation, enable safe refactoring, and catch bugs before production. High coverage correlates with lower defect rates.

### 📋 Guidelines: How to Achieve Comprehensive Testing

#### 1. Test Pyramid Strategy
Follow the testing pyramid: many unit tests, fewer integration tests, minimal E2E tests.

#### 2. Arrange-Act-Assert Pattern
Structure all tests with clear setup, execution, and verification phases for readability.

#### 3. Test Behavior, Not Implementation
Focus tests on public APIs and observable behavior, not internal implementation details.

#### 4. Mock External Dependencies
Isolate units under test by mocking external services, databases, and third-party APIs.

#### 5. Test Edge Cases
Include tests for error conditions, boundary values, and exceptional scenarios, not just happy paths.

### ⚙️ Rules/Standards: Exact Implementation

#### MANDATORY Coverage Targets (CI-Enforced):

| Layer | Minimum Coverage | Rationale |
|-------|-----------------|-----------|
| **Overall Project** | **80%** | Baseline quality gate - CI fails below this |
| **Core Module Services** | **95%** | Critical shared infrastructure |
| **API Controllers** | **90%** | All endpoints must be integration tested |
| **FormRequests** | **100%** | Simple validation rules - no excuse for gaps |
| **Models (business logic)** | **85%** | Domain rules must be verified |
| **Repositories** | **80%** | Data access layer baseline |
| **Middleware** | **90%** | Request/response pipeline critical |
| **SDK Classes** | **95%** | Third-party integrations require thorough testing |

#### Coverage Exclusions:
- Service Providers (framework boilerplate)
- Migrations (schema definitions)
- Configuration files (data, not logic)
- Blade templates (frontend presentation)

#### New Code Requirements:
- ✅ **MUST:** Every new class requires accompanying unit tests before merge
- ✅ **MUST:** Every new feature requires integration tests covering main workflow
- ✅ **MUST:** Pull requests that decrease coverage are automatically rejected
- ✅ **MUST:** Coverage gaps in modified code must be addressed in same PR

#### Test File Naming:
- Unit tests: `tests/Unit/{Namespace}/{ClassName}Test.php`
- Feature tests: `tests/Feature/{Module}/{Feature}Test.php`
- Test methods: `test_{method_name}_{scenario}_{expected_outcome}()`

#### Example Test Structure:
```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;

final class UserServiceTest extends TestCase
{
    public function test_create_with_valid_data_returns_user(): void
    {
        // Arrange
        $repository = $this->createMock(UserRepositoryContract::class);
        $repository->expects($this->once())
            ->method('create')
            ->willReturn(new User(['id' => 1]));
        
        $service = new UserService($repository);
        
        // Act
        $user = $service->create(['name' => 'John', 'email' => 'john@example.com']);
        
        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(1, $user->id);
    }
    
    public function test_create_with_invalid_email_throws_exception(): void
    {
        // Arrange
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');
        
        $service = new UserService($this->createMock(UserRepositoryContract::class));
        
        // Act
        $service->create(['name' => 'John', 'email' => 'invalid']);
    }
}
```

#### Commands:
```bash
# Generate HTML coverage report
composer test:coverage

# Enforce coverage thresholds (CI gate)
composer test:coverage-check

# Run specific test suite
composer test -- --filter UserServiceTest
```

#### Tool Configuration:
- PHPUnit config: `phpunit.xml`
- Coverage format: HTML + Clover XML
- Coverage output: `storage/coverage/`
- Fail on: Coverage below thresholds, risky tests, incomplete tests

#### Validation:
- Pre-commit: Optional coverage check (recommended)
- CI/CD: `composer test:coverage-check` must pass
- PR Review: Coverage report diff required

---

## Example 3: Code Quality Pipeline

### 🎯 Principle: Automated Quality Gates
**What you must do:** All code must pass automated quality checks before merge to maintain consistent standards.

**Why:** Automated gates catch issues early, enforce consistency across team, and reduce review burden. Quality tools are faster and more thorough than manual review.

### 📋 Guidelines: How to Maintain Code Quality

#### 1. Fix in Priority Order
Run tools sequentially and fix issues before proceeding: Pint → PHPCS → PHPMD → PHPStan.

#### 2. Auto-Fix When Possible
Let Pint auto-fix style issues before manually addressing structural concerns.

#### 3. Understand Violations
Don't blindly suppress warnings - understand the issue and fix root cause.

#### 4. Configure Once, Enforce Everywhere
Maintain tool configurations in version control; never override locally.

#### 5. Keep Tools Updated
Regularly update quality tools to benefit from new checks and bug fixes.

### ⚙️ Rules/Standards: Exact Implementation

#### MANDATORY Tool Execution Order:

1. **Laravel Pint** (Style - Auto-fix)
   ```bash
   composer lint:pint
   ```
   - Config: `pint.json`
   - Preset: `laravel`
   - Features: Alpha-sorted imports, single-line arrays when possible
   - **ALWAYS run first** - defines canonical style

2. **PHP_CodeSniffer** (PSR-12 Validation)
   ```bash
   composer lint:phpcs
   ```
   - Config: `phpcs.xml`
   - Standard: PSR-12
   - Exclusions: Line length (handled by Pint), `bootstrap/cache`
   - Must align with Pint to avoid conflicts

3. **PHPMD** (Design Quality)
   ```bash
   composer analyze:phpmd
   ```
   - Config: `phpmd.xml`
   - Rules: Clean code, code size, controversial, design, naming, unused code
   - Checks: Cyclomatic complexity, NPath complexity, coupling

4. **PHPStan** (Static Analysis)
   ```bash
   composer analyze:phpstan
   ```
   - Config: `phpstan.neon.dist`
   - Level: `max` (9)
   - Cache: `storage/framework/phpstan`
   - Parallel processing: enabled

#### Composite Command:
```bash
# Run all tools in correct order
composer lint
```

#### Configuration Files (Version Controlled):

**`pint.json`:**
```json
{
    "preset": "laravel",
    "rules": {
        "ordered_imports": {
            "sort_algorithm": "alpha"
        },
        "no_unused_imports": true
    }
}
```

**`phpcs.xml`:**
```xml
<?xml version="1.0"?>
<ruleset name="JOOwp">
    <rule ref="PSR12">
        <!-- Exclude line length - Pint handles this -->
        <exclude name="Generic.Files.LineLength"/>
    </rule>
    <exclude-pattern>bootstrap/cache/*</exclude-pattern>
    <exclude-pattern>storage/*</exclude-pattern>
    <exclude-pattern>vendor/*</exclude-pattern>
</ruleset>
```

**`phpstan.neon.dist`:**
```neon
parameters:
    level: max
    paths:
        - app
        - Modules
    tmpDir: storage/framework/phpstan
    checkMissingIterableValueType: true
    checkGenericClassInNonGenericObjectType: true
    excludePaths:
        - bootstrap/cache
        - storage
        - vendor
```

#### Pre-Commit Requirements:
- ✅ **MUST:** All 4 tools pass before commit
- ✅ **MUST:** Zero violations (warnings treated as errors)
- ✅ **MUST:** No suppressions without inline justification

#### CI/CD Gates:
- Pipeline stage: `quality` (runs before tests)
- Failure behavior: Block merge, require fixes
- No bypass: Even admins must pass quality checks

#### Suppression Rules:
- ❌ **FORBIDDEN:** Global suppressions in config files
- ⚠️ **DISCOURAGED:** Line-level suppressions (`@phpstan-ignore-next-line`)
- ✅ **PERMITTED:** Inline suppressions with justification:
  ```php
  // @phpstan-ignore-next-line - Framework limitation, see issue #123
  $mixed = $request->input('data');
  ```

#### Validation:
- Local: `composer lint` before every commit
- Pre-commit hook: Automatic check (can be skipped with `--no-verify` but discouraged)
- CI/CD: Required gate, no bypass

---

## Example 4: Modular Architecture

### 🎯 Principle: Domain-Driven Modularity
**What you must do:** Organize code by business domain, not technical layer, with clear module boundaries.

**Why:** Domain-based organization scales better, improves maintainability, and makes business logic easier to find. Modules can be developed/tested/deployed independently.

### 📋 Guidelines: How to Structure Modules

#### 1. One Domain = One Module
Each business domain gets its own self-contained module with all related code.

#### 2. Module Independence
Modules communicate through contracts/interfaces, never direct dependencies on concrete implementations.

#### 3. Core vs Domain Modules
Core module contains only technical infrastructure (no business logic). Domain modules contain business-specific logic.

#### 4. Explicit Dependencies
Module dependencies declared in `module.json`, enforced by architecture tests.

#### 5. Shared Code Through Contracts
Cross-module functionality exposed via interfaces, implemented in each domain.

### ⚙️ Rules/Standards: Exact Implementation

#### Module Naming Convention:
- ✅ **MUST:** Singular PascalCase names matching business domain
  - ✅ Correct: `WordPress`, `Twitter`, `AI`, `Product`
  - ❌ Wrong: `Posts` (technical feature), `WordPressIntegration` (redundant)

#### Module Structure (MANDATORY):
```
Modules/{DomainName}/
├── Config/
│   └── config.php              # MUST: Module configuration
├── Database/
│   ├── Migrations/             # MUST: Domain-specific migrations
│   └── Seeders/                # OPTIONAL: Domain-specific seeders
├── Http/
│   ├── Controllers/            # MUST: API controllers (final classes)
│   ├── Requests/               # MUST: FormRequest validation (final classes)
│   ├── Resources/              # OPTIONAL: API resources for Models
│   └── Middleware/             # OPTIONAL: Domain-specific middleware
├── Models/                     # MUST: Domain models (final classes)
├── Providers/
│   └── {Domain}ServiceProvider.php  # MUST: Service registration
├── Repositories/               # OPTIONAL: If using local database
│   ├── Contracts/              # MUST: Repository interfaces
│   └── {Entity}Repository.php  # MUST: Repository implementations
├── Services/                   # MUST: Business logic (final classes)
│   └── Contracts/              # MUST: Service interfaces
├── routes/
│   └── api.php                 # MUST: API routes (auto-prefixed /api/v1)
├── Resources/                  # OPTIONAL: Frontend assets
│   ├── js/
│   ├── css/
│   └── views/
├── vite.config.js              # OPTIONAL: If module has frontend assets
├── composer.json               # MUST: Module dependencies
└── module.json                 # MUST: Module metadata
```

#### Module Decision Tree:

**"Where does this code belong?"**

```
Does this code contain business-specific logic?
│
├─ NO → Core Module
│   └─ Examples:
│       - ActionLogger (generic audit logging)
│       - ApiResponse (generic response builder)
│       - BaseService (generic base class)
│       - HttpClient (generic HTTP wrapper)
│
└─ YES → Domain Module
    └─ Which business domain?
        ├─ WordPress → WordPress module
        │   └─ WordPress SDK, PostService, CategoryController
        ├─ Twitter → Twitter module
        │   └─ Twitter SDK, TweetService
        ├─ AI → AI module
        │   └─ ContentGenerator, ImageAnalyzer
        └─ Product → Product module
            └─ ProductService, InventoryManager
```

#### Module Creation:
```bash
# Create new domain module
php artisan module:make {DomainName}

# Examples:
php artisan module:make WordPress  # WordPress business domain
php artisan module:make AI         # AI business domain
php artisan module:make Product    # Product management domain
```

#### Module Activation:
**`modules_statuses.json`:**
```json
{
    "Core": true,
    "WordPress": true,
    "Twitter": false,
    "AI": true,
    "Product": false
}
```

#### Module Configuration:
**`Modules/{Domain}/Config/config.php`:**
```php
<?php

declare(strict_types=1);

return [
    'name' => 'WordPress',
    'enabled' => true,
    'api' => [
        'url' => env('WP_URL', 'https://soulevil.com'),
        'timeout' => env('WP_TIMEOUT', 10),
    ],
];
```

Access via: `config('wordpress.api.url')`

#### Service Provider Pattern:
```php
<?php

declare(strict_types=1);

namespace Modules\WordPress\Providers;

use Illuminate\Support\ServiceProvider;

final class WordPressServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge config recursively
        $this->mergeConfigFrom(
            module_path('WordPress', 'Config/config.php'),
            'wordpress'
        );
        
        // Bind contracts to implementations
        $this->app->singleton(
            PostRepositoryContract::class,
            PostRepository::class
        );
        
        $this->app->singleton(
            SdkContract::class,
            function ($app) {
                return new Sdk(
                    $app->make(Client::class),
                    config('wordpress.api')
                );
            }
        );
    }
    
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(module_path('WordPress', 'routes/api.php'));
        
        // Load migrations
        $this->loadMigrationsFrom(module_path('WordPress', 'Database/Migrations'));
    }
}
```

#### Inter-Module Communication:
- ✅ **MUST:** Use contracts/interfaces for cross-module dependencies
- ❌ **FORBIDDEN:** Direct instantiation of classes from other modules
- ✅ **MUST:** Declare module dependencies in `module.json`

**Example:**
```php
// ❌ WRONG - Direct dependency on concrete class
use Modules\WordPress\Services\PostService;

public function __construct(private readonly PostService $posts) {}

// ✅ CORRECT - Dependency on contract
use Modules\WordPress\Services\Contracts\PostServiceContract;

public function __construct(private readonly PostServiceContract $posts) {}
```

#### Validation:
- Architecture tests: Enforce module boundaries
- Static analysis: Detect cross-module concrete dependencies
- CI/CD: Module isolation tests must pass

---

## Template: Adding New Principle

### 🎯 Principle: {Principle Name}
**What you must do:** {Clear statement of what must be achieved}

**Why:** {Business/technical justification - why this principle exists}

### 📋 Guidelines: How to {Achieve This Principle}

#### 1. {Guideline Title}
{Description of approach or methodology}

#### 2. {Guideline Title}
{Description of approach or methodology}

#### 3. {Guideline Title}
{Description of approach or methodology}

### ⚙️ Rules/Standards: Exact Implementation

#### MANDATORY Requirements:
- ✅ **MUST:** {Specific requirement with no ambiguity}
- ✅ **MUST:** {Specific requirement with no ambiguity}
- ⚠️ **SHOULD:** {Recommended practice with exceptions allowed}
- ❌ **FORBIDDEN:** {Explicitly prohibited practice}

#### Configuration:
```bash
# Relevant commands
```

```php
// Code examples
```

#### Tool Settings:
- Tool name: `configuration value`
- Setting: `value`

#### Validation:
- Local: How to check locally
- Pre-commit: Automated checks
- CI/CD: Required gates

---

## Documentation Organization

### Primary Documents:

1. **`docs/architecture/principles.md`**
   - Contains all principles with embedded guidelines and rules
   - Reference point for engineering standards
   - Updated rarely (only when adding new principles)

2. **`docs/reference/standards.md`**
   - Quick reference for all concrete rules/standards
   - Coverage targets, tool configurations, naming conventions
   - Extracted from principles for easy lookup

3. **`docs/development/guidelines.md`**
   - Step-by-step procedures for implementing principles
   - How-to guides for common scenarios
   - Examples and workflows

### When to Use Each:

- **Need to understand WHY?** → Read Principles
- **Need to know HOW?** → Read Guidelines
- **Need exact numbers/configs?** → Read Standards/Rules
- **Need step-by-step instructions?** → Read Guidelines

### Example Flow:

1. Developer asks: "What coverage is required?"
   - Quick answer: Check `standards.md` → 80% overall
   
2. Developer asks: "Why do we need coverage?"
   - Deep answer: Check `principles.md` → Comprehensive Testing principle explains rationale
   
3. Developer asks: "How do I write good tests?"
   - Procedural answer: Check `guidelines.md` → Testing guidelines with examples

---

## Summary: The 3-Tier Hierarchy

| Tier | Purpose | Questions Answered | Location | Update Frequency |
|------|---------|-------------------|----------|------------------|
| **Principle** | What & Why | "What must we do?" "Why does this matter?" | `principles.md` | Rare (architectural) |
| **Guideline** | How (Approach) | "How do we achieve this?" "What's the methodology?" | `principles.md` or `guidelines.md` | Occasional (process) |
| **Rule/Standard** | How (Exact) | "What's the exact requirement?" "What are the numbers?" | `principles.md` and `standards.md` | Frequent (tactical) |

### Key Differences:

- **Principle** = Strategy (type safety, comprehensive testing)
- **Guideline** = Tactics (use readonly, test edge cases)
- **Rule/Standard** = Execution (80% coverage, PHPStan level max)

### Benefits:

1. **Clear Hierarchy:** No confusion about requirement levels
2. **Easy Lookup:** Developers can find answers quickly
3. **Better Onboarding:** New team members understand the "why" behind rules
4. **Maintainable:** Can update rules without changing principles
5. **Traceable:** Rules link back to guiding principles
