# Development Guidelines

Step-by-step procedures, examples, and workflows for implementing the engineering principles in practice.

---

## Type Safety Implementation

### How to Add Strict Types to Existing Code

#### Step 1: Add Strict Types Declaration
```php
<?php

declare(strict_types=1);  // MUST be first line after <?php

namespace App\Services;
```

#### Step 2: Add Explicit Parameter Types
```php
// Before
public function process($data, $options = [])

// After
public function process(array $data, array $options = []): array
```

#### Step 3: Document Complex Array Types
```php
/**
 * Process user data and return formatted results
 * 
 * @param array<string, mixed> $data User input data
 * @param array<string, string> $options Processing options
 * @return array<int, array<string, mixed>> Processed user records
 */
public function process(array $data, array $options = []): array
```

#### Step 4: Use Constructor Property Promotion
```php
// Before
final class UserService
{
    private UserRepositoryContract $repository;
    private ActionLogger $logger;
    
    public function __construct(UserRepositoryContract $repository, ActionLogger $logger)
    {
        $this->repository = $repository;
        $this->logger = $logger;
    }
}

// After
final class UserService
{
    public function __construct(
        private readonly UserRepositoryContract $repository,
        private readonly ActionLogger $logger,
    ) {}
}
```

#### Step 5: Handle Third-Party Interface Issues
```php
// When forced to use mixed due to third-party limitations
/**
 * @param mixed $request Laravel request data - mixed due to framework limitation
 */
public function handle($request): array
{
    // @phpstan-ignore-next-line - Framework limitation, see issue #123
    $mixed = $request->input('data');
    
    // Cast to known type as soon as possible
    $data = is_array($mixed) ? $mixed : [];
    
    return $this->processData($data);
}
```

---

## Git & Commit Workflow

### AI Agent Commit Authorization

**ABSOLUTE RULE:** AI agents are **FORBIDDEN** from executing `git commit` without explicit human approval.

#### Atomic Commits Per Sub-task

Break features into tasks, commit each task separately:

```
Feature: User Authentication
├─ Task A1: User model + tests → Commit 1
├─ Task A2: UserRepository + tests → Commit 2
├─ Task A3: AuthService + tests → Commit 3
└─ Task A4: Controllers + tests → Commit 4
```

#### Required Process for Each Sub-task:

**1. Complete ALL work for that task**
   - Code implementation finished
   - Tests written and passing
   - Quality gates passed (`composer lint && composer test:coverage-check`)

**2. Stage specific files (only when 100% complete)**
   ```bash
   git add path/to/file1.php path/to/file2.php
   # NEVER use: git add . or git add -A
   ```

**3. Ask human for approval**
   ```
   AI: "Ready to commit? Task A1 complete: User model with validation
   
   Staged files:
   - app/Models/User.php
   - tests/Unit/Models/UserTest.php
   
   Quality gates: ✅ All passed"
   ```

**4. Wait for human response**
   - Do NOT proceed until human responds
   - Valid responses: "commit", "yes", "ok", "go ahead"
   - Any other response = do NOT commit

**5. Execute commit (only after approval)**
   ```bash
   git commit -m "feat: add User model with validation (Task A1)"
   ```

**6. Repeat for next task**

#### Examples:

✅ **CORRECT:**
```
[AI completes UserService implementation]
[AI runs quality checks - all pass]
AI: git add app/Services/UserService.php tests/Unit/Services/UserServiceTest.php
AI: "Ready to commit? Task A2 complete: UserService with validation
     
     Staged files:
     - app/Services/UserService.php
     - tests/Unit/Services/UserServiceTest.php
     
     Quality gates: ✅ All passed"
[AI WAITS]
Human: "commit"
AI: git commit -m "feat: add UserService with validation (Task A2)"
```

❌ **WRONG:**
```
[AI completes work]
AI: git add .
AI: git commit -m "update files"
[No human approval requested]
```

❌ **WRONG:**
```
AI: "Ready to commit?"
AI: git commit -m "..."
[Did not wait for human response]
```

❌ **WRONG:**
```
[AI stages 3 tasks together]
AI: git commit -m "add user management"
[Should be 3 separate commits]
```

#### Commit Scope Guidelines:

- **1-7 files:** Perfect (one sub-task)
- **7-15 files:** OK if same logical unit (justify in message)
- **15-30 files:** Must justify in message
- **>30 files:** SPLIT into multiple commits

#### Commit Message Format:

`<type>: <description> (Task ID)`

**Types:** `feat`, `fix`, `docs`, `test`, `refactor`, `style`, `chore`

**Examples:**
```bash
feat: add User model with validation (Task A1)
feat: implement UserRepository with CRUD (Task A2)
feat: add AuthService with JWT logic (Task A3)
test: add UserService integration tests (Task A2.1)
```

#### Enforcement:

- Pre-commit hooks verify human approval
- Unauthorized commits are automatically reverted
- Violations logged and escalated

**Remember:** 1 sub-task = 1 commit. Each commit must be atomic, complete, and independently reversible.

---

## Quality Pipeline Workflow

### Daily Development Workflow

#### Before Starting Work
```bash
# Pull latest changes
git pull origin main

# Start development environment
composer dev  # Starts server + queue + logs + vite
```

#### While Developing
```bash
# Run quality checks frequently
composer lint:pint  # Auto-fix style issues

# Run specific tool if needed
composer analyze:phpstan  # Check static analysis
```

#### Before Every Commit
```bash
# 1. Check what will be committed
git status
git diff --cached

# 2. Run full quality pipeline
composer lint

# 3. Run tests with coverage
composer test:coverage-check

# 4. TypeScript validation
npm run typecheck

# 5. Production build test
npm run build

# 6. Commit specific files only
git add app/Services/UserService.php tests/Unit/Services/UserServiceTest.php
git commit -m "feat: add user service with validation"
```

### Fixing Quality Pipeline Issues

#### Laravel Pint Issues (Style)
```bash
composer lint:pint
# This auto-fixes most issues - run before other tools
```

#### PHPCS Issues (PSR-12)
Most PHPCS issues are fixed by Pint, but manual fixes for:
```php
// Missing namespace
namespace App\Services;

// Missing class documentation
/**
 * User service for managing user operations
 */
final class UserService
```

#### PHPMD Issues (Design)
```php
// Too many parameters (reduce complexity)
// Before - too many parameters
public function createUser(string $name, string $email, string $phone, string $address, bool $active, array $roles)

// After - use data object
public function createUser(CreateUserData $data): User

// Unused variable
public function process(array $data): array
{
    $result = [];
    // Remove unused variable $temp
    return $result;
}
```

#### PHPStan Issues (Static Analysis)
```php
// Missing return type
// Before
public function getUsers()

// After  
public function getUsers(): Collection

// Missing array shape documentation
// Before
public function formatData(array $data): array

// After
/**
 * @param array<string, mixed> $data
 * @return array<int, array<string, string>>
 */
public function formatData(array $data): array
```

---

## Test Coverage Implementation

### Writing Unit Tests for New Classes

#### Step 1: Create Test File
```php
# filepath: tests/Unit/Services/UserServiceTest.php
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
        
        $this->repository = Mockery::mock(UserRepositoryContract::class);
        $this->logger = Mockery::mock(ActionLogger::class);
        $this->service = new UserService($this->repository, $this->logger);
    }
}
```

#### Step 2: Test Happy Path
```php
public function test_create_with_valid_data_returns_user(): void
{
    // Arrange
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
    
    // Act
    $result = $this->service->create($userData);
    
    // Assert
    $this->assertInstanceOf(User::class, $result);
    $this->assertEquals('John Doe', $result->name);
    $this->assertEquals('john@example.com', $result->email);
}
```

#### Step 3: Test Error Cases
```php
public function test_create_with_invalid_email_throws_exception(): void
{
    // Arrange
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid email format');
    
    // Don't expect repository or logger calls since validation fails early
    
    // Act
    $this->service->create(['name' => 'John', 'email' => 'invalid-email']);
}

public function test_create_when_repository_fails_throws_exception(): void
{
    // Arrange
    $userData = ['name' => 'John Doe', 'email' => 'john@example.com'];
    
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

#### Step 4: Test Edge Cases
```php
public function test_create_with_empty_name_throws_exception(): void
{
    $this->expectException(\InvalidArgumentException::class);
    $this->service->create(['name' => '', 'email' => 'john@example.com']);
}

public function test_create_with_null_data_throws_exception(): void
{
    $this->expectException(\TypeError::class);
    $this->service->create(null);
}
```

### Writing Feature Tests for Controllers

#### Step 1: Create Feature Test
```php
# filepath: tests/Feature/UserControllerTest.php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_user_and_returns_resource(): void
    {
        // Arrange
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        // Act
        $response = $this->postJson('/api/v1/users', $userData);

        // Assert
        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                ]
            ])
            ->assertJson([
                'data' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }
}
```

### Checking Coverage

#### Generate Coverage Report
```bash
# Generate HTML report in storage/coverage/
composer test:coverage

# Open in browser
open storage/coverage/index.html
```

#### Check Specific Coverage
```bash
# Check overall coverage percentage
composer test:coverage-check

# Run tests with coverage info in terminal
composer test -- --coverage-text

# Run specific test with coverage
composer test -- --filter UserServiceTest --coverage-text
```

#### Coverage Targets by Layer
- Check each class meets its layer's minimum coverage requirement
- Use HTML report to identify uncovered lines
- Focus on covering edge cases and error conditions

---

## Module Creation Workflow

### Creating a New Business Domain Module

#### Step 1: Generate Module
```bash
# Create module (use singular PascalCase name)
php artisan module:make Product

# This creates:
# Modules/Product/
# ├── Config/config.php
# ├── Database/Migrations/
# ├── Http/Controllers/
# ├── Models/
# ├── Providers/ProductServiceProvider.php
# ├── Services/
# └── routes/api.php
```

#### Step 2: Configure Module
```php
# Modules/Product/Config/config.php
<?php

declare(strict_types=1);

return [
    'name' => 'Product',
    'enabled' => true,
    'api' => [
        'rate_limit' => env('PRODUCT_API_RATE_LIMIT', 100),
        'timeout' => env('PRODUCT_API_TIMEOUT', 30),
    ],
    'features' => [
        'inventory_tracking' => env('PRODUCT_INVENTORY_ENABLED', true),
        'price_history' => env('PRODUCT_PRICE_HISTORY_ENABLED', false),
    ],
];
```

#### Step 3: Enable Module
```json
# modules_statuses.json
{
    "Core": true,
    "WordPress": true,
    "Product": true
}
```

#### Step 4: Create Service Contract
```php
# Modules/Product/Services/Contracts/ProductServiceContract.php
<?php

declare(strict_types=1);

namespace Modules\Product\Services\Contracts;

use Modules\Product\Models\Product;
use Illuminate\Support\Collection;

interface ProductServiceContract
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Product;
    
    public function findById(int $id): ?Product;
    
    /**
     * @param array<string, mixed> $filters
     * @return Collection<int, Product>
     */
    public function list(array $filters = []): Collection;
    
    /**
     * @param array<string, mixed> $data
     */
    public function update(Product $product, array $data): Product;
    
    public function delete(Product $product): bool;
}
```

#### Step 5: Implement Service
```php
# Modules/Product/Services/ProductService.php
<?php

declare(strict_types=1);

namespace Modules\Product\Services;

use App\Logging\ActionLogger;
use Modules\Product\Models\Product;
use Modules\Product\Repositories\Contracts\ProductRepositoryContract;
use Modules\Product\Services\Contracts\ProductServiceContract;
use Illuminate\Support\Collection;

final class ProductService implements ProductServiceContract
{
    public function __construct(
        private readonly ProductRepositoryContract $repository,
        private readonly ActionLogger $logger,
    ) {}
    
    public function create(array $data): Product
    {
        // Business logic: validate data
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Product name is required');
        }
        
        if (($data['price'] ?? 0) < 0) {
            throw new \InvalidArgumentException('Price cannot be negative');
        }
        
        // Create product
        $product = $this->repository->create($data);
        
        // Audit logging
        $this->logger->log(
            operation: 'product.created',
            actor: auth()->user(),
            before: [],
            after: $product->toArray()
        );
        
        return $product;
    }
    
    public function findById(int $id): ?Product
    {
        return $this->repository->findById($id);
    }
    
    public function list(array $filters = []): Collection
    {
        // Apply default filters
        $filters = array_merge([
            'active' => true,
            'limit' => 50,
        ], $filters);
        
        return $this->repository->findAll($filters);
    }
    
    // ... other methods
}
```

#### Step 6: Register Services
```php
# Modules/Product/Providers/ProductServiceProvider.php
<?php

declare(strict_types=1);

namespace Modules\Product\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Product\Repositories\Contracts\ProductRepositoryContract;
use Modules\Product\Repositories\ProductRepository;
use Modules\Product\Services\Contracts\ProductServiceContract;
use Modules\Product\Services\ProductService;

final class ProductServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge module config
        $this->mergeConfigFrom(
            module_path('Product', 'Config/config.php'),
            'product'
        );
        
        // Bind repository contract
        $this->app->singleton(
            ProductRepositoryContract::class,
            ProductRepository::class
        );
        
        // Bind service contract
        $this->app->singleton(
            ProductServiceContract::class,
            ProductService::class
        );
    }
    
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(module_path('Product', 'routes/api.php'));
        
        // Load migrations
        $this->loadMigrationsFrom(module_path('Product', 'Database/Migrations'));
    }
}
```

#### Step 7: Create API Routes
```php
# Modules/Product/routes/api.php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\ProductController;

// All routes auto-prefixed with /api/v1
Route::prefix('v1')->group(function () {
    Route::apiResource('products', ProductController::class);
});
```

### Deciding Module Placement

#### Use the Decision Tree
Ask: **"Does this contain business-specific logic?"**

```
WordPress REST API SDK
└─ Contains WordPress-specific business logic? → YES
   └─ Goes in WordPress module

Generic HTTP Client
└─ Contains business-specific logic? → NO
   └─ Goes in Core module

Product Inventory Tracker
└─ Contains business-specific logic? → YES (Product domain)
   └─ Goes in Product module

Action Logger
└─ Contains business-specific logic? → NO (generic audit logging)
   └─ Goes in Core module
```

#### Examples
```php
// ✅ WordPress module (WordPress business logic)
Modules/WordPress/Services/Sdk.php          // WordPress REST API
Modules/WordPress/Services/PostService.php  // WordPress post logic
Modules/WordPress/Http/Controllers/TokenController.php  // WordPress JWT

// ✅ Core module (technical infrastructure)
Modules/Core/Logging/ActionLogger.php       // Generic audit logging
Modules/Core/Http/Responses/ApiResponse.php // Generic API responses
Modules/Core/Services/BaseService.php       // Generic base class

// ✅ Product module (Product business logic)
Modules/Product/Services/ProductService.php     // Product business logic
Modules/Product/Services/InventoryService.php   // Inventory management
Modules/Product/Http/Controllers/ProductController.php  // Product API
```

---

## API Development Workflow

### Creating a CRUD Endpoint

#### Step 1: Create FormRequest
```php
# Modules/Product/Http/Requests/StoreProductRequest.php
<?php

declare(strict_types=1);

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Add authorization logic if needed
    }
    
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'category_id' => ['required', 'exists:categories,id'],
            'active' => ['boolean'],
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
            'price.min' => 'Price cannot be negative',
        ];
    }
}
```

#### Step 2: Create Resource (for Model responses)
```php
# Modules/Product/Http/Resources/ProductResource.php
<?php

declare(strict_types=1);

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Models\Product;

/**
 * @mixin Product
 */
final class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => number_format($this->price, 2),
            'active' => $this->active,
            'category' => [
                'id' => $this->category_id,
                'name' => $this->category?->name,
            ],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
```

#### Step 3: Create Controller
```php
# Modules/Product/Http/Controllers/ProductController.php
<?php

declare(strict_types=1);

namespace Modules\Product\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Modules\Product\Http\Requests\StoreProductRequest;
use Modules\Product\Http\Requests\UpdateProductRequest;
use Modules\Product\Http\Resources\ProductResource;
use Modules\Product\Models\Product;
use Modules\Product\Services\Contracts\ProductServiceContract;

final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductServiceContract $service
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $products = $this->service->list();
        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->service->create($request->validated());
        
        return ProductResource::make($product)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Product $product): ProductResource
    {
        return ProductResource::make($product);
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $updated = $this->service->update($product, $request->validated());
        return ProductResource::make($updated);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->service->delete($product);
        
        return response()->json(null, 204);
    }
}
```

### When to Use ApiResponse vs Resource

#### Use Resource (PREFERRED)
```php
// When data maps to a Model
public function store(StoreProductRequest $request): JsonResponse
{
    $product = $this->service->create($request->validated());
    
    // Use Resource - data maps to Product model
    return ProductResource::make($product)
        ->response()
        ->setStatusCode(201);
}
```

#### Use ApiResponse (Raw JSON)
```php
use App\Http\Responses\ApiResponse;

// When response doesn't map to a Model
public function authenticate(AuthRequest $request): JsonResponse
{
    $result = $this->service->authenticate($request->validated());
    
    // Use raw JSON - mixed data from authentication
    return ApiResponse::success(
        code: 'auth.success',
        message: 'Authentication successful',
        data: $result, // ['token' => '...', 'expires_in' => 3600, 'remembered' => true]
        status: 200
    );
}

// For status/confirmation responses
public function destroy(Product $product): JsonResponse
{
    $this->service->delete($product);
    
    // Use raw JSON - simple confirmation
    return ApiResponse::success(
        code: 'product.deleted',
        message: 'Product deleted successfully',
        data: ['id' => $product->id],
        status: 200
    );
}
```

---

## Error Handling Patterns

### Service Layer Error Handling

#### Validation Errors in Services
```php
public function create(array $data): Product
{
    // Business validation (beyond FormRequest)
    if ($this->isDuplicateName($data['name'])) {
        throw new \InvalidArgumentException('Product name already exists');
    }
    
    if ($data['price'] > $this->getMaxAllowedPrice()) {
        throw new \InvalidArgumentException('Price exceeds maximum allowed');
    }
    
    return $this->repository->create($data);
}
```

#### External Service Errors
```php
public function syncWithExternalApi(Product $product): array
{
    try {
        $response = $this->externalSdk->updateProduct($product->external_id, [
            'name' => $product->name,
            'price' => $product->price,
        ]);
        
        return $response;
    } catch (\GuzzleHttp\Exception\RequestException $e) {
        // Log the error but don't expose internal details
        $this->logger->log(
            operation: 'product.sync_failed',
            actor: auth()->user(),
            before: [],
            after: [],
            metadata: ['error' => $e->getMessage(), 'product_id' => $product->id]
        );
        
        throw new \RuntimeException('Failed to sync with external service');
    }
}
```

### Controller Error Handling

#### Let Laravel Handle Validation
```php
// FormRequest automatically handles validation errors
public function store(StoreProductRequest $request): JsonResponse
{
    // If validation fails, Laravel automatically returns 422 with errors
    // No need to manually handle validation errors here
    
    $product = $this->service->create($request->validated());
    return ProductResource::make($product)->response()->setStatusCode(201);
}
```

#### Handle Service Exceptions
```php
public function store(StoreProductRequest $request): JsonResponse
{
    try {
        $product = $this->service->create($request->validated());
        return ProductResource::make($product)->response()->setStatusCode(201);
    } catch (\InvalidArgumentException $e) {
        // Business logic error - return 400
        return ApiResponse::error(
            code: 'product.invalid_data',
            message: $e->getMessage(),
            status: 400
        );
    } catch (\RuntimeException $e) {
        // External service error - return 503
        return ApiResponse::error(
            code: 'product.service_unavailable',
            message: 'Service temporarily unavailable',
            status: 503
        );
    }
}
```

### Frontend Error Handling

#### Axios Error Handling
```typescript
try {
    const response = await axios.post('/api/v1/products', productData);
    toast.success('Product created successfully');
    // Handle success...
} catch (error) {
    if (axios.isAxiosError(error)) {
        const status = error.response?.status;
        const message = error.response?.data?.message || 'An error occurred';
        
        if (status === 422) {
            // Validation errors - show field-specific messages
            const errors = error.response?.data?.meta?.errors || {};
            this.showValidationErrors(errors);
        } else if (status === 400) {
            // Business logic error - show toast
            toast.error(message, { autoClose: false });
        } else if (status >= 500) {
            // Server error - show generic message
            toast.error('Server error. Please try again later.', { autoClose: false });
        } else {
            // Other errors
            toast.error(message, { autoClose: false });
        }
    } else {
        // Network or other errors
        toast.error('Network error. Please check your connection.', { autoClose: false });
    }
}
```

---

## Frontend Development Patterns

### Vue 3 + TypeScript Component Structure

#### Basic Component Template
```vue
<template>
    <div>
        <!-- Include Navbar on every page -->
        <Navbar />
        
        <div class="container-fluid py-5">
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1>{{ title }}</h1>
                        <button 
                            @click="showCreateModal = true" 
                            class="btn btn-primary"
                            :disabled="loading"
                        >
                            <i class="fas fa-plus"></i> Create New
                        </button>
                    </div>
                    
                    <!-- Loading state -->
                    <div v-if="loading" class="text-center py-5">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-3 text-muted">Loading...</p>
                    </div>
                    
                    <!-- Content -->
                    <div v-else>
                        <!-- Your content here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { toast } from 'vue3-toastify';
import Navbar from '@/Components/Navbar.vue';

// Define interfaces
interface Item {
    id: number;
    name: string;
    created_at: string;
}

// Reactive state
const title = ref<string>('Page Title');
const loading = ref<boolean>(false);
const items = ref<Item[]>([]);
const showCreateModal = ref<boolean>(false);

// Methods
const fetchItems = async (): Promise<void> => {
    loading.value = true;
    
    try {
        const response = await window.axios.get('/api/v1/items');
        items.value = response.data.data || [];
    } catch (error) {
        if (axios.isAxiosError(error)) {
            toast.error(error.response?.data?.message || 'Failed to load items', {
                autoClose: false
            });
        }
    } finally {
        loading.value = false;
    }
};

// Lifecycle
onMounted(() => {
    fetchItems();
});
</script>

<style scoped>
/* Dark theme styles */
.container-fluid {
    background-color: var(--bs-dark);
    color: var(--bs-light);
    min-height: 100vh;
}
</style>
```

### Delete Confirmation Pattern

#### Complete Delete Implementation
```vue
<template>
    <!-- Delete Button -->
    <button 
        @click="showDeleteModal(item)" 
        class="btn btn-sm btn-danger"
        :disabled="loading"
    >
        <i class="fas fa-trash"></i> Delete
    </button>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong>{{ itemToDelete?.name }}</strong>?</p>
                    <p class="text-warning mb-0">
                        <i class="fas fa-exclamation-triangle"></i> 
                        This action cannot be undone.
                    </p>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button 
                        type="button" 
                        class="btn btn-danger" 
                        @click="confirmDelete"
                        :disabled="deleting"
                    >
                        <span v-if="deleting">
                            <i class="fas fa-spinner fa-spin"></i> Deleting...
                        </span>
                        <span v-else>
                            <i class="fas fa-trash"></i> Confirm Delete
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Modal } from 'bootstrap';
import { toast } from 'vue3-toastify';

interface Item {
    id: number;
    name: string;
}

const itemToDelete = ref<Item | null>(null);
const deleting = ref<boolean>(false);
let deleteModalInstance: Modal | null = null;

const showDeleteModal = (item: Item) => {
    itemToDelete.value = item;
    if (!deleteModalInstance) {
        const modalEl = document.getElementById('deleteModal');
        deleteModalInstance = new Modal(modalEl!);
    }
    deleteModalInstance.show();
};

const confirmDelete = async () => {
    if (!itemToDelete.value) return;
    
    deleting.value = true;
    
    try {
        await window.axios.delete(`/api/v1/items/${itemToDelete.value.id}`);
        
        // Close modal
        deleteModalInstance?.hide();
        
        // Show success toast
        toast.success('Item deleted successfully');
        
        // Remove from list or reload data
        await fetchItems();
    } catch (error) {
        if (axios.isAxiosError(error)) {
            toast.error(error.response?.data?.message || 'Delete failed', {
                autoClose: false
            });
        }
    } finally {
        deleting.value = false;
        itemToDelete.value = null;
    }
};
</script>
```

### Form Handling Pattern

#### Form with Validation
```vue
<template>
    <form @submit.prevent="submitForm">
        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input 
                type="text" 
                class="form-control"
                :class="{ 'is-invalid': errors.name }"
                id="name" 
                v-model="form.name"
                :disabled="submitting"
            >
            <div v-if="errors.name" class="invalid-feedback">
                {{ errors.name[0] }}
            </div>
        </div>
        
        <div class="mb-3">
            <label for="price" class="form-label">Price</label>
            <input 
                type="number" 
                step="0.01"
                class="form-control"
                :class="{ 'is-invalid': errors.price }"
                id="price" 
                v-model.number="form.price"
                :disabled="submitting"
            >
            <div v-if="errors.price" class="invalid-feedback">
                {{ errors.price[0] }}
            </div>
        </div>
        
        <div class="mb-3">
            <label for="active" class="form-label">Status</label>
            <div class="form-check form-switch">
                <input 
                    class="form-check-input" 
                    type="checkbox" 
                    id="active"
                    v-model="form.active"
                    :disabled="submitting"
                >
                <label class="form-check-label" for="active">
                    Active
                </label>
            </div>
        </div>
        
        <button 
            type="submit" 
            class="btn btn-primary"
            :disabled="submitting"
        >
            <span v-if="submitting">
                <i class="fas fa-spinner fa-spin"></i> Saving...
            </span>
            <span v-else>
                <i class="fas fa-save"></i> Save
            </span>
        </button>
    </form>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import { toast } from 'vue3-toastify';

interface FormData {
    name: string;
    price: number;
    active: boolean;
}

interface ValidationErrors {
    [key: string]: string[];
}

const form = reactive<FormData>({
    name: '',
    price: 0,
    active: true,
});

const submitting = ref<boolean>(false);
const errors = ref<ValidationErrors>({});

const submitForm = async (): Promise<void> => {
    submitting.value = true;
    errors.value = {};
    
    try {
        const response = await window.axios.post('/api/v1/products', form);
        
        toast.success('Product created successfully');
        
        // Reset form
        Object.assign(form, { name: '', price: 0, active: true });
        
        // Handle success (navigate, emit event, etc.)
        
    } catch (error) {
        if (axios.isAxiosError(error)) {
            const status = error.response?.status;
            
            if (status === 422) {
                // Validation errors
                errors.value = error.response?.data?.meta?.errors || {};
                toast.error('Please correct the errors below');
            } else {
                toast.error(error.response?.data?.message || 'Save failed', {
                    autoClose: false
                });
            }
        }
    } finally {
        submitting.value = false;
    }
};
</script>
```

---

## Quick Reference

### File Creation Checklist

#### New Service Class
- [ ] `declare(strict_types=1);` at top
- [ ] `final class` keyword
- [ ] Constructor with `readonly` dependencies
- [ ] All methods have explicit types
- [ ] PHPDoc for array parameters/returns
- [ ] Accompanying unit test file
- [ ] Service contract/interface

#### New Controller
- [ ] `declare(strict_types=1);` at top  
- [ ] `final class` keyword
- [ ] Inject service via constructor
- [ ] Use FormRequest for validation
- [ ] Return Resource or ApiResponse
- [ ] Accompanying feature test

#### New Vue Component
- [ ] Include Navbar component
- [ ] TypeScript with explicit types
- [ ] Loading states for async operations
- [ ] Error handling with toasts
- [ ] Dark theme styling
- [ ] Delete confirmations for destructive actions

#### Pre-Commit Checklist
- [ ] `git status` to review files
- [ ] `composer lint` passes
- [ ] `composer test:coverage-check` passes  
- [ ] `npm run typecheck` passes
- [ ] `git add` specific files only
- [ ] Clear commit message with type prefix

This guidelines document provides practical, step-by-step workflows for implementing all the engineering principles in daily development work.