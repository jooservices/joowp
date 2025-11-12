# Engineering Principles

## Philosophy

JOOservices Platform is built on strict type safety, comprehensive testing, and automated quality gates. Every line of code must pass a rigorous pipeline before merge.

## Core Principles

### 1. Type Safety First

**MANDATORY: Every PHP file MUST have strict types declaration:**

```php
<?php

declare(strict_types=1);

namespace App\Example;

final class Example
{
    // All code here runs with strict type checking
}
```

**No exceptions** - All PHP files require `declare(strict_types=1)` immediately after `<?php`:
- ✅ All classes (controllers, services, models, repositories)
- ✅ All interfaces and contracts
- ✅ All FormRequests
- ✅ All tests (Unit and Feature)
- ✅ Route files (`routes/web.php`, `routes/api.php`)
- ✅ Configuration files
- ✅ Migrations and seeders
- ✅ Console commands
- ✅ Middleware

**Pre-commit validation enforces this requirement** - commits without strict types will be rejected.

**All methods MUST have concrete types:**
```php
// ❌ WRONG - Missing types
public function process($data)
{
    return $data;
}

// ✅ CORRECT - Complete type declarations
/**
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
public function process(array $data): array
{
    return $data;
}
```

**`mixed` type is forbidden** except in documented edge cases with inline justification.

**Use `final` by default:**
```php
// ✅ Default - prevents unintended inheritance
final class UserService
{
    // ...
}

// Only when inheritance is explicitly needed
abstract class BaseController
{
    // ...
}
```

**Constructor property promotion with `readonly`:**
```php
final class UserService
{
    public function __construct(
        private readonly UserRepositoryContract $repository,
        private readonly ActionLogger $logger,
    ) {}
}
```

### 2. Quality Pipeline (Non-Negotiable Order)

Every commit MUST pass these tools in sequence:

```bash
composer lint  # Runs all 4 tools in correct order
```

**1. Laravel Pint** - Canonical code style (always run first)
```bash
composer lint:pint
```
- Config: `pint.json`
- Preset: Laravel with alpha-sorted imports
- Auto-fixes style issues

**2. PHP_CodeSniffer** - PSR-12 validation
```bash
composer lint:phpcs
```
- Config: `phpcs.xml`
- Validates PSR-12 compliance
- Excludes line length (handled by Pint)

**3. PHPMD** - Design quality
```bash
composer analyze:phpmd
```
- Config: `phpmd.xml`
- Checks SOLID principles
- Detects code smells

**4. PHPStan** - Static analysis
```bash
composer analyze:phpstan
```
- Config: `phpstan.neon.dist`
- Level: maximum
- Checks: missing iterable types, implicit mixed, generic classes
- Cache: `storage/framework/phpstan`

**Order matters** - Pint must run before PHPCS to avoid conflicts.

### 3. Test Coverage Requirements

**MANDATORY minimum coverage targets - enforced by CI/CD:**

| Layer | Minimum Coverage | Rationale |
|-------|-----------------|-----------|
| **Overall Project** | **80%** | Baseline quality standard - CI fails below this |
| **Core Module Services** | **95%** | Critical shared functionality across platform |
| **API Controllers** | **90%** | All endpoints must be integration tested |
| **FormRequests** | **100%** | Simple validation rules - no excuse for gaps |
| **Models (business logic)** | **85%** | Domain rules must be verified |
| **Repositories** | **80%** | Data access layer baseline |
| **Middleware** | **90%** | Request/response pipeline critical |
| **SDK Classes** | **95%** | Third-party integrations require thorough mocking |

**Excluded from coverage:**
- Service Providers (framework boilerplate)
- Migrations (schema definitions)
- Configuration files
- Blade templates

**Coverage enforcement:**

```bash
# Generate HTML coverage report (storage/coverage/index.html)
composer test:coverage

# Enforce 80% minimum (CI/CD gates)
composer test:coverage-check
```

**Coverage must increase or stay same** - pull requests that decrease coverage are automatically rejected.

**Every new class MUST have unit tests before merge:**
```php
# filepath: tests/Unit/Services/UserServiceTest.php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\UserService;
use PHPUnit\Framework\TestCase;

final class UserServiceTest extends TestCase
{
    public function test_creates_user_with_valid_data(): void
    {
        $service = new UserService($this->createMock(UserRepositoryContract::class));
        $user = $service->create(['name' => 'John', 'email' => 'john@example.com']);
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John', $user->name);
    }
}
```

### 4. Modular Architecture

**Module-first design:**
- Each business domain is a separate module via `nwidart/laravel-modules`
- Modules are independent, communicate via contracts
- Core module provides shared services - never duplicate functionality

**Creating modules:**
```bash
php artisan module:make Products
```

**Module structure:**
```
Modules/Products/
├── Config/config.php          # Module configuration
├── Database/
│   ├── Migrations/            # Module-specific migrations
│   └── Seeders/               # Module-specific seeders
├── Http/
│   ├── Controllers/           # API controllers (final classes)
│   └── Requests/              # FormRequest validation (final classes)
├── Providers/
│   └── ProductsServiceProvider.php  # Service registration
├── Services/                  # Business logic (final classes)
├── routes/
│   └── api.php               # Auto-prefixed with /api/v1
├── vite.config.js            # If module has frontend assets
└── module.json               # Module metadata
```

**Enable/disable modules** via `modules_statuses.json`:
```json
{
    "Core": true,
    "Products": true,
    "Analytics": false
}
```

### 5. API Response Standardization

**All API responses use envelope pattern:**
```php
use App\Http\Responses\ApiResponse;

// Success response
return ApiResponse::success(
    code: 'products.created',
    message: 'Product created successfully',
    data: $product->toArray(),
    meta: ['created_at' => now()],
    status: 201
);

// Error response
return ApiResponse::error(
    code: 'products.validation_failed',
    message: 'Invalid product data',
    meta: ['errors' => $validator->errors()],
    data: [],
    status: 422
);
```

**Response structure:**
```json
{
  "ok": true,
  "code": "products.created",
  "status": 201,
  "message": "Product created successfully",
  "data": { "id": 1, "name": "Product" },
  "meta": { "created_at": "2025-11-12T10:00:00Z" }
}
```

### 6. Audit Logging (Mandatory for Mutations)

**Log all domain mutations:**
```php
use App\Logging\ActionLogger;

final class UserController extends Controller
{
    public function __construct(
        private readonly UserService $service,
        private readonly ActionLogger $logger,
    ) {}
    
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $before = $user->toArray();
        
        $updated = $this->service->update($user, $request->validated());
        
        $this->logger->log(
            operation: 'user.updated',
            actor: auth()->user(),
            before: $before,
            after: $updated->toArray(),
            metadata: ['ip' => $request->ip(), 'user_agent' => $request->userAgent()]
        );
        
        return ApiResponse::success('user.updated', 'User updated', $updated);
    }
}
```

**Two log channels:**

1. **Action log** (`storage/logs/action.log`) - 30 day retention
   - Domain mutations (create, update, delete)
   - Captures actor, before/after state, metadata

2. **External log** (`storage/logs/external.log`) - 14 day retention
   - Third-party API calls (WordPress, payment gateways, etc.)
   - Automatically used by WordPress SDK

### 7. WordPress Integration Patterns

**Never call WordPress directly from frontend** - always proxy through Laravel:

```php
// ✅ CORRECT - Backend proxy
Route::get('wordpress/posts', function (SdkContract $sdk) {
    $posts = $sdk->posts(['per_page' => 10, 'status' => 'publish']);
    return ApiResponse::success('posts.fetched', 'Posts retrieved', $posts);
});

// ❌ WRONG - Direct frontend call
// axios.get('https://soulevil.com/wp-json/wp/v2/posts')
```

**Use SDK contract, not Guzzle:**
```php
use Modules\Core\Services\WordPress\Contracts\SdkContract;

final class PostService
{
    public function __construct(private readonly SdkContract $sdk) {}
    
    public function getAllPosts(): array
    {
        return $this->sdk->posts(['per_page' => 100]);
    }
}
```

**Available SDK methods:**
- `posts(array $query = [])` - Fetch posts
- `post(int $id)` - Fetch single post
- `pages(array $query = [])` - Fetch pages
- `media(array $query = [])` - Fetch media
- `categories(array $query = [])` - Fetch categories
- `tags(array $query = [])` - Fetch tags
- `users(array $query = [])` - Fetch users
- `search(array $query = [])` - Search content
- `token(string $username, string $password)` - Authenticate
- `get(string $resource, array $query = [])` - Generic GET

**JWT token management:**
- Tokens stored in `wp_tokens` table (hashed)
- "Remember token" checkbox in navbar persists JWT
- Automatic Bearer header injection via token resolver

**WordPress configuration** (`config/core.php`):
```php
'wordpress' => [
    'url' => env('WP_URL', 'https://soulevil.com'),
    'timeout' => env('WORDPRESS_API_TIMEOUT', 10),
    'user_agent' => env('WORDPRESS_API_USER_AGENT', 'CoreWordPressSdk/1.0'),
    'namespace' => env('WORDPRESS_API_NAMESPACE', 'wp/v2'),
],
```

### 8. Frontend Standards

**TypeScript-only, no JavaScript:**
```vue
<script setup lang="ts">
// ✅ CORRECT - TypeScript with explicit types
import { ref } from 'vue';

const title = ref<string>('Page Title');
const count = ref<number>(0);

interface User {
    id: number;
    name: string;
}

const user = ref<User | null>(null);
</script>
```

**Strict mode enabled** (`tsconfig.json`):
```json
{
  "compilerOptions": {
    "strict": true,
    "noImplicitAny": true,
    "strictNullChecks": true
  }
}
```

**Error handling pattern:**
```typescript
try {
    await axios.post('/api/v1/resource', data);
    toast.success('Operation successful');
} catch (error) {
    if (axios.isAxiosError(error)) {
        // Fatal errors = sticky toast
        toast.error(error.response?.data?.message || 'Error occurred', {
            autoClose: false
        });
    }
}
```

**UI guidelines:**
- Dark theme across all surfaces
- Bootstrap + FontAwesome for components/icons
- Binary toggles use switches, not checkboxes
- Loading states: disable controls + spinner + dim context
- Toast notifications: top-right, auto-dismiss (5s) for non-fatal

### 9. Service Layer Pattern

**Controller → Service → Repository flow:**

```php
// Controller - thin, delegates to service
final class ProductController extends Controller
{
    public function __construct(private readonly ProductService $service) {}
    
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->service->create($request->validated());
        return ApiResponse::success('product.created', 'Product created', $product, [], 201);
    }
}

// Service - business logic
final class ProductService
{
    public function __construct(
        private readonly ProductRepositoryContract $repository,
        private readonly ActionLogger $logger,
    ) {}
    
    public function create(array $data): Product
    {
        $product = $this->repository->create($data);
        
        $this->logger->log('product.created', auth()->user(), [], $product->toArray());
        
        return $product;
    }
}

// Repository - data access
final class ProductRepository implements ProductRepositoryContract
{
    public function create(array $data): Product
    {
        return Product::create($data);
    }
}
```

### 10. FormRequest Validation

**All validation in FormRequest classes:**
```php
# filepath: app/Http/Requests/StoreProductRequest.php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Or check permissions
    }
    
    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'category_id' => ['required', 'exists:categories,id'],
        ];
    }
    
    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required',
            'price.numeric' => 'Price must be a valid number',
        ];
    }
}
```

## Workflow Summary

**Before every commit (MANDATORY):**
```bash
# 1. Run quality pipeline
composer lint

# 2. Run tests with coverage enforcement
composer test:coverage-check

# 3. TypeScript validation
npm run typecheck

# 4. Build check
npm run build
```

**All 4 steps must pass** - no exceptions.

**CI/CD enforces:**
- All quality tools must pass (Pint → PHPCS → PHPMD → PHPStan)
- Test coverage ≥ 80% (fails build if below)
- No TypeScript errors
- Successful production build
- All files have `declare(strict_types=1)`

**Pre-commit hooks prevent:**
- Commits without `declare(strict_types=1)`
- Commits with missing type declarations
- Commits with failing tests
- Commits with quality tool errors
- Commits that decrease coverage percentage

**Coverage violations = build failure:**
- Overall coverage drops below 80%
- Core services drop below 95%
- Controllers drop below 90%
- FormRequests below 100%
- Any new class without tests

## Key Documentation

- `docs/principles.md` - This file
- `docs/code-quality.md` - Detailed tooling configuration
- `docs/guides/core-wordpress-sdk.md` - WordPress integration guide
- `.github/copilot-instructions.md` - AI coding assistant instructions
- `README.md` - Project overview and setup

**Read all documentation before starting any task.**
