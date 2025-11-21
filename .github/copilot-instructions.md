# JOOservices Platform - AI Coding Instructions

Modular Laravel 12 + Vue 3 platform targeting PHP 8.4 with strict type safety and comprehensive quality gates. Before starting any work, review `docs/architecture/principles.md` and `docs/development/code-quality.md`.

## Quick Start

**Every commit must:**
1. Pass quality pipeline: `composer lint` (pint → phpcs → phpmd → phpstan)
2. Pass all tests with 80%+ coverage: `composer test:coverage-check`
3. Include unit tests for new code (no exceptions)
4. Use `final` classes with `declare(strict_types=1)` at top of **EVERY** PHP file
5. Pass TypeScript validation: `npm run typecheck`

**Non-negotiable requirements:**
- `declare(strict_types=1)` in ALL PHP files (classes, routes, tests, migrations, seeders)
- Coverage: 80% overall, 95% Core services, 90% controllers, 100% FormRequests
- New classes without tests = PR rejected
- Coverage decrease = build failure
- **All documentation must be in English only** (no Vietnamese or other languages)

**Key architecture:**
- **Modular design** via `nwidart/laravel-modules` - 1 domain = 1 module (singular name)
- **Core module = Technical infrastructure ONLY** (logging, base classes, HTTP responses - no business logic)
- **Domain modules = Business-specific logic** (WordPress, Twitter, AI, Product - each owns its complete business domain)
- API responses use `App\Http\Responses\ApiResponse` envelope pattern
- Frontend is TypeScript-only Vue 3 + Inertia with dark theme

**Module organization rule:**
- **Shared across modules?** → Core (e.g., WordPress SDK used by Posts/Media/Categories)
- **Domain-specific?** → Domain module (e.g., PostController only in WordPress module)

**CRITICAL principle:** Organize by **business domain**, NOT technical features
- ✅ WordPress module owns WordPress SDK (WordPress business logic)
- ✅ Twitter module owns Twitter SDK (Twitter business logic)
- ✅ Core module owns ActionLogger (technical infrastructure, no business rules)

## Common Task Workflows

### Starting Development
```bash
composer dev  # Starts server + queue + logs + vite in one command
```

### Creating a New API Endpoint
1. **Add route** in `Modules/YourModule/routes/api.php`:
   ```php
   Route::prefix('v1')->group(function () {
       Route::post('resource', [ResourceController::class, 'store']);
   });
   ```
2. **Create FormRequest** for validation:
   ```php
   final class StoreResourceRequest extends FormRequest
   {
       public function authorize(): bool { return true; }
       
       /** @return array<string, array<int, string>> */
       public function rules(): array
       {
           return ['field' => ['required', 'string']];
       }
   }
   ```
3. **Implement controller** with dependency injection:
   ```php
   final class ResourceController extends Controller
   {
       public function __construct(private readonly ServiceContract $service) {}
       
       public function store(StoreResourceRequest $request): JsonResponse
       {
           $data = $this->service->create($request->validated());
           return ApiResponse::success('resource.created', 'Resource created', $data, [], 201);
       }
   }
   ```
4. **Add audit logging** for mutations:
   ```php
   use App\Logging\ActionLogger;
   
   $logger->log('resource.created', auth()->user(), [], $data->toArray());
   ```
5. **Write tests** in `tests/Unit/` and `tests/Feature/`
6. **Run quality checks**: `composer lint && composer test`

### Creating a New Module
```bash
# Use singular name for domain
php artisan module:make WordPress  # For WordPress-specific management
php artisan module:make AI         # For AI-specific features
php artisan module:make Product    # For product management
```
Then:
1. Enable in `modules_statuses.json`: `"WordPress": true`
2. Create config at `Modules/WordPress/config/config.php`
3. Register services in `Modules/WordPress/Providers/WordPressServiceProvider`
4. Add routes in `routes/api.php` (auto-prefixed with `/api/v1`)
5. Create `vite.config.js` if module has assets
6. Run migrations: `php artisan migrate`

**Module Organization Rule:**
Ask: **"Does this contain business-specific logic?"**
- **YES** → Domain module (WordPress SDK contains WordPress business logic → WordPress module)
- **NO** → Core module (ActionLogger has no business rules → Core module)

**Examples:**
- ✅ WordPress module: WordPress SDK, PostService, CategoryController (WordPress business)
- ✅ Twitter module: Twitter SDK, TweetService (Twitter business)
- ✅ AI module: ContentGeneratorService (AI business)
- ✅ Core module: ActionLogger, ApiResponse, BaseService (generic technical infrastructure)

### Adding WordPress Integration
```php
// WordPress SDK lives in WordPress module (WordPress business logic)
// Inject the SDK contract from WordPress module
public function __construct(private readonly SdkContract $sdk) {}

// Fetch WordPress data
$posts = $this->sdk->posts(['per_page' => 10, 'status' => 'publish']);

// Always proxy through Laravel - never call WP from frontend
Route::get('posts', function (SdkContract $sdk) {
    return ApiResponse::success('posts.fetched', 'Posts retrieved', $sdk->posts());
});
```

### Creating a Vue Page
1. Create SFC in `resources/js/Pages/YourPage.vue`:
   ```vue
   <template>
       <div class="container py-5">
           <h1>{{ title }}</h1>
       </div>
   </template>
   
   <script setup lang="ts">
   import { ref } from 'vue';
   
   const title = ref<string>('Page Title');
   </script>
   
   <style scoped>
   /* Dark theme styles */
   </style>
   ```
2. Add Inertia route in `routes/web.php`:
   ```php
   Route::get('/page', fn() => inertia('YourPage'));
   ```

### Fixing Quality Pipeline Errors
Run tools in order and fix issues sequentially:
```bash
composer lint:pint      # Auto-fixes style issues
composer lint:phpcs     # Validates PSR-12 compliance
composer analyze:phpmd  # Checks design patterns
composer analyze:phpstan # Static analysis (level: max)
```

Common fixes:
- Missing types → Add parameter/return types to all methods
- Missing PHPDoc → Add `@return array<string, mixed>` for array returns
- Missing `declare(strict_types=1)` → Add at top of file after `<?php` (MANDATORY - no exceptions)
- Non-final class → Change `class` to `final class`
- Low coverage → Write unit tests before committing (80% minimum enforced)

## Development Commands
## Development Commands

```bash
# Full development stack (server + queue + logs + vite)
composer dev

# Quality pipeline (MUST be green before commits)
composer lint  # Runs: pint → phpcs → phpmd → phpstan

# Individual quality tools
composer lint:pint        # Laravel Pint (auto-fix style)
composer lint:phpcs       # PHP_CodeSniffer (PSR-12)
composer analyze:phpmd    # PHPMD (design quality)
composer analyze:phpstan  # PHPStan (static analysis)

# Testing (MANDATORY before any commit)
composer test           # Run PHP tests
npm run typecheck       # TypeScript validation
npm run dev            # Vite dev server
npm run build          # Production build
```

## Code Standards (Non-Negotiable)

### Type Safety Requirements
- **MANDATORY: `declare(strict_types=1)` in ALL PHP files** - classes, routes, tests, migrations, seeders, config files
  - No exceptions - pre-commit hook enforces this
  - Place immediately after `<?php` opening tag
- **Every method MUST have concrete parameter and return types** - `mixed` forbidden except documented edge cases
- **Array shapes must be documented with PHPDoc** - e.g., `@return array<string, mixed>`
- **`final` classes** - Default to `final class` unless inheritance is explicitly needed
- **Constructor property promotion with `readonly`** - Use for immutable dependencies
- Frontend: **TypeScript-only** - no plain JavaScript allowed, strict mode enabled (`tsconfig.json`)
- PHPStan level maximum enforced - suppressions require inline justification

**Example - Proper PHP class structure:**
```php
<?php

declare(strict_types=1);  // MANDATORY - first line after <?php

namespace App\Example;

final class Example  // final by default
{
    public function __construct(
        private readonly ServiceContract $service,  // readonly for immutability
    ) {}
    
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function process(array $data): array  // Concrete types required
    {
        return $this->service->handle($data);
    }
}
```

**Example - TypeScript strict mode:**
```typescript
// All .ts and .vue files must use TypeScript with explicit types
import { ref } from 'vue';

const title = ref<string>('Title');
const count = ref<number>(0);

interface User {
    id: number;
    name: string;
}

const user = ref<User | null>(null);
```

### Test Coverage Requirements (Enforced by CI/CD)
- **Overall project: 80% minimum** (build fails if below)
- **Core module services: 95%** (critical shared functionality)
- **API controllers: 90%** (all endpoints tested)
- **FormRequests: 100%** (simple validation rules)
- **Models (business logic): 85%**
- **Repositories: 80%**
- **Middleware: 90%**
- **SDK classes: 95%** (third-party integrations)
- **New classes without tests = PR rejected**
- **Coverage decrease = build failure**
- Run `composer test:coverage-check` before every commit

### Quality Pipeline Order
1. **Laravel Pint** (canonical style) - run first always, uses `pint.json` config
   - Preset: `laravel` with alpha-sorted imports
2. **PHP_CodeSniffer** (PSR-12 validation) - uses `phpcs.xml`, must align with Pint
   - Excludes line length checks to avoid Pint conflicts
3. **PHPMD** (design quality) - uses `phpmd.xml` for SOLID compliance
4. **PHPStan** (static analysis) - uses `phpstan.neon.dist` at maximum level
   - Checks missing iterable types, implicit mixed, generic classes
   - Output cache in `storage/framework/phpstan`

Run individually: `composer lint:pint`, `composer lint:phpcs`, `composer analyze:phpmd`, `composer analyze:phpstan`

### Testing Requirements
- **Unit coverage mandatory** for every class/function - no exceptions
- **Integration tests** for each module's primary workflow
- **Coverage enforcement:** `composer test:coverage-check` must pass (80% minimum)
- **Coverage targets by layer:**
  - Overall: 80% (CI gate)
  - Core services: 95%
  - Controllers: 90%
  - FormRequests: 100%
  - Models: 85%
  - Repositories: 80%
  - Middleware: 90%
  - SDK classes: 95%
- **Audit logging** required for all mutations using `App\Logging\ActionLogger`
- Run full test suite before every commit: `composer test:coverage-check && npm run typecheck`

### Mock Usage Policy (CRITICAL)
**Core Principle:** Use real implementations by default. Mock only when necessary.

**✅ ALWAYS use real implementations for:**
- **Repositories** - Use real database with `RefreshDatabase` trait
- **Services** - Use real service implementations
- **Internal dependencies** - Any class within `App\` or `Modules\`
- **Models** - Use factories and real database interactions
- **FormRequests** - Use real validation logic
- **Loggers** - Use real logger instances (mock Log facade if needed to verify calls)

**✅ ONLY mock for:**
- **3rd Party APIs/SDKs** - External services (WordPress SDK, LM Studio SDK, payment gateways)
- **Live services** - Services requiring network access, authentication, or rate limits
- **Expensive operations** - Slow or resource-intensive operations
- **Laravel Facades** - Mock when you need to verify calls (Log, Cache, Queue)

**Decision Tree:**
```
Should I mock this dependency?
│
├─ Is it a 3rd party API/SDK? → ✅ YES → Mock
│
├─ Does it require network access? → ✅ YES → Mock
│
├─ Is it expensive/slow? → ✅ YES → Mock
│
├─ Is it unavailable in test environment? → ✅ YES → Mock
│
├─ Is it a Repository? → ✅ YES → Use Real (with RefreshDatabase)
│
├─ Is it a Service? → ✅ YES → Use Real
│
├─ Is it a Model? → ✅ YES → Use Real (with factories)
│
└─ Is it internal to application? → ✅ YES → Use Real
```

**Examples:**
```php
// ❌ BAD: Mocking repository
$repository = Mockery::mock(UserRepositoryContract::class);

// ✅ GOOD: Use real repository
use Illuminate\Foundation\Testing\RefreshDatabase;
final class UserServiceTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_create_saves_user(): void
    {
        $repository = new UserRepository();
        $logger = new ActionLogger();
        $service = new UserService($repository, $logger);
        
        $result = $service->create(['name' => 'John']);
        $this->assertDatabaseHas('users', ['name' => 'John']);
    }
}

// ✅ GOOD: Mock external SDK
$sdk = Mockery::mock(WordPressSdkContract::class);
$sdk->shouldReceive('posts')->once()->andReturn([...]);
```

**See:** `docs/guides/mock-usage-policy.md` for complete policy and examples.

**Example - Unit test structure:**
```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;

final class ExampleServiceTest extends TestCase
{
    public function test_performs_operation_correctly(): void
    {
        // Arrange
        $service = new ExampleService();
        
        // Act
        $result = $service->doSomething('input');
        
        // Assert
        $this->assertEquals('expected', $result);
    }
    
    public function test_handles_invalid_input(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $service = new ExampleService();
        $service->doSomething('');
    }
}
```
- **Pull requests that decrease coverage are automatically rejected**

## Project-Specific Patterns

### WordPress Integration
- **Use the SDK contract**: Inject `Modules\Core\Services\WordPress\Contracts\SdkContract` - never use Guzzle directly
- **HTTP Client**: WordPress SDK uses `jooservices/jooclient` package via `JOOservices\Client\Contracts\JsonHttpClientContract`
  - Benefits: Automatic retries, logging, circuit breaker, rate limiting
  - JSON convenience methods: `getJson()`, `postJson()`, `patchJson()`, etc.
  - See: `vendor/jooservices/jooclient/docs/guides/JOOWP_INTEGRATION.md` for integration guide
- **Never call WordPress from frontend** - always through Laravel API routes (e.g., `/api/v1/wordpress/token`)
- **JWT tokens**: Managed via `wp_tokens` table with "Remember token" functionality
- **Configuration**: Set via `config('wordpress.api')` - see `Modules/WordPress/config/config.php`
  - `WP_URL` - WordPress base URL (default: `https://soulevil.com`)
  - `WORDPRESS_API_TIMEOUT` - Request timeout in seconds (default: 10)
  - `WORDPRESS_API_USER_AGENT` - Custom user agent (default: `CoreWordPressSdk/1.0`)
  - `WORDPRESS_API_NAMESPACE` - API namespace (default: `wp/v2`)
- **Available SDK methods**: `posts()`, `post($id)`, `pages()`, `media()`, `categories()`, `tags()`, `users()`, `search()`, `token($username, $password)`, `get($resource, $query)`

### API Response Envelope
All API responses use this standardized structure:
```php
// Success response
ApiResponse::success('domain.action_succeeded', 'Human message', $data, $meta, 200);

// Error response  
ApiResponse::error('domain.action_failed', 'Human message', $meta, $data, 400);
```

Envelope structure:
```json
{
  "ok": true|false,
  "code": "domain.action",
  "status": 200,
  "message": "Human-readable message",
  "data": {},
  "meta": {}
}
```

### Logging Patterns
**Three dedicated log channels** (configured in `config/logging.php`):

1. **Action Log** (`action` channel) - Domain mutations:
```php
use App\Logging\ActionLogger;

$logger->log(
    operation: 'user.updated',
    actor: auth()->user(),
    before: ['name' => 'Old Name'],
    after: ['name' => 'New Name'],
    metadata: ['ip' => request()->ip()]
);
```
- Stored in `storage/logs/action.log` (30 day retention)
- Captures actor, timestamp, before/after state, metadata

2. **General Log** (`external` channel, currently) - Application errors/info:
- Used for controller errors, service errors, general warnings/info
- Flexible format with timestamp, service, message, context
- Stored in `storage/logs/external.log` (14 day retention)
- See controller error logging examples in error-handling guide

3. **Third-Party Request Log** (`third-party-requests` channel, planned):
- Will be used for all third-party API requests/responses
- Standard format with complete request/response data
- See [Third-Party Request Logging Plan](../docs/plans/technical/2025-01-15-third-party-request-logging.md)

### Frontend Patterns  
- **Pages**: Vue SFCs in `resources/js/Pages/` using TypeScript
- **Theme**: Dark theme aesthetic across all UI surfaces
- **Layout**: All pages MUST include Navbar with active state indication
  - Navbar structure: Parent items with nested children (WordPress → Posts, Media, Categories)
  - Active parent: Highlighted when any child page is active
  - Active child: Highlighted when exact route matches
  - Use route matching logic for automatic active states
- **Loading states**: Disable controls + show spinner + dim context during async operations
- **Error handling**: 
  - Fatal errors → sticky toast notifications (user dismissible)
  - Non-fatal notices → auto-dismiss toasts (5 second timeout)
  - Toast position: fixed top-right with stacking
- **Binary toggles**: Use switch controls, not checkboxes (better UX for on/off states)
- **Bootstrap + FontAwesome**: Use these for all UI components and icons
- **Inertia Progress**: Pre-configured with blue progress bar and spinner
- **Form validation**: Use Laravel FormRequest classes with typed `rules()` method
- **Axios integration**: Available globally via `window.axios`, handle errors with type guards
- **Component structure**: `<template>`, `<script setup lang="ts">`, `<style scoped>` order

### Implementing Delete Actions

**All delete actions MUST follow this pattern:**

1. **Delete button with red styling and icon:**
   ```vue
   <button 
       @click="showDeleteModal(item)" 
       class="btn btn-sm btn-danger"
       :disabled="loading"
   >
       <i class="fas fa-trash"></i> Delete
   </button>
   ```

2. **Confirmation modal (Bootstrap 5):**
   ```vue
   <template>
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
   import { ref } from 'vue';
   import { Modal } from 'bootstrap';
   
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
           
           // Reload data or remove from list
           // ... your data refresh logic
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

3. **Icon variations for different contexts:**
   - Generic delete: `<i class="fas fa-trash"></i>`
   - Remove from list: `<i class="fas fa-times"></i>`
   - Clear/reset: `<i class="fas fa-eraser"></i>`
   - Archive: `<i class="fas fa-archive"></i>`

**Key requirements:**
- Always use `btn-danger` class for delete buttons
- Always show confirmation modal before executing delete
- Modal must explain what will be deleted and warn about irreversibility
- Show loading state during delete operation
- Display success/error toast after operation

### Creating a Vue Page with Navbar
1. Create SFC in `resources/js/Pages/YourPage.vue`:
   ```vue
   <template>
       <div>
           <!-- REQUIRED: Include Navbar on every page -->
           <Navbar />
           
           <div class="container-fluid py-5">
               <div class="row">
                   <div class="col-12">
                       <h1>{{ title }}</h1>
                   </div>
               </div>
           </div>
       </div>
   </template>
   
   <script setup lang="ts">
   import { ref } from 'vue';
   import Navbar from '@/Components/Navbar.vue';
   
   const title = ref<string>('Page Title');
   
   // Route matching for active nav states
   // Navbar component handles this automatically via route.current()
   </script>
   
   <style scoped>
   /* Dark theme styles */
   </style>
   ```

2. Add Inertia route in `routes/web.php`:
   ```php
   Route::get('/page', fn() => inertia('YourPage'));
   ```

3. **Navbar active state logic** (handled in Navbar component):
   ```typescript
   // Example: Check if current route matches parent or child
   const isActive = computed(() => {
       const currentRoute = route().current();
       // Parent active if any child is active
       return currentRoute?.startsWith('wordpress.') || false;
   });
   
   const isChildActive = (routeName: string) => {
       return route().current() === routeName;
   };
   ```

### Module Service Registration
Follow the `Modules\Core\Providers\CoreServiceProvider` pattern:

```php
// Register singleton services with contracts
$this->app->singleton(SdkContract::class, function ($app) {
    $config = $app->make(ConfigRepository::class)->get('core.wordpress');
    return new Sdk($client, $tokenResolver, $config['namespace']);
});
```

Key patterns:
- **Singleton bindings** for services
- **Contract-based DI** - bind interfaces, not concrete classes
- **Configuration merging** - use `merge_config_from()` for nested configs
- **Recursive config loading** - auto-discovers all `.php` files in `config/`
- **Duplicate segment removal** - `core.core.wordpress` becomes `core.wordpress`

## Key Files to Understand

- `docs/architecture/principles.md` - Core engineering requirements
- `docs/architecture/flow.md` - Request/response flow and service layer pattern
- `docs/development/code-quality.md` - Detailed tooling pipeline  
- `docs/guides/wordpress-sdk.md` - WordPress integration guide
- `composer.json` scripts - Development and quality commands
- `vite-module-loader.js` - Module asset compilation logic
- `config/modules.php` - Module generation and structure rules
- `Modules/Core/Services/WordPress/Sdk.php` - WordPress SDK implementation
- `app/Http/Responses/ApiResponse.php` - Standardized API response builder
- `app/Logging/ActionLogger.php` - Audit logging for domain mutations

## Integration Points

- **Module assets**: Auto-discovered via `vite-module-loader.js` scanning `modules_statuses.json`
  - Each enabled module's `vite.config.js` exports `paths` array
  - Module assets compiled when module is `true` in status file
  - Skip `.DS_Store` directories during module scanning
  
- **Database**: 
  - Migrations auto-loaded per module from `database/migrations/`
  - Shared seeders in `database/seeders/Modules/`
  - Use `php artisan migrate` to run all module migrations
  
- **Configuration**: 
  - Modules merge configs recursively from `config/` directory
  - Adjacent duplicate segments removed (e.g., `core.core.wordpress` → `core.wordpress`)
  - Access via `config('modulename.key')`
  
- **Logging**: 
  - `action` channel: Domain mutations (30 days, `storage/logs/action.log`)
  - `external` channel: Third-party API calls (14 days, `storage/logs/external.log`)
  - Both use daily rotation with automatic cleanup
  
- **Authentication**: 
  - WordPress JWT via `wp_tokens` table
  - Automatic Bearer header injection in SDK requests
  - Token resolver closure in `CoreServiceProvider::registerWordPressBindings()`
  - "Remember token" switch in navbar - stores JWT for reuse

Remember: Read all documentation in `docs/` before starting any task. Every change must pass the full quality pipeline and have accompanying tests.

## Git Workflow

**🚫 CRITICAL: NEVER COMMIT WITHOUT EXPLICIT HUMAN APPROVAL**

### Atomic Commits Per Sub-task

Break features into tasks, commit each task separately:

```
Feature: User Authentication
├─ Task A1: User model + tests → Commit 1
├─ Task A2: UserRepository + tests → Commit 2  
├─ Task A3: AuthService + tests → Commit 3
└─ Task A4: Controllers + tests → Commit 4
```

### Commit Authorization Process (FOR EACH SUB-TASK):

1. **Complete ONE task** → Code + tests + quality gates ✅
   ```bash
   composer lint && composer test:coverage-check
   npm run typecheck
   ```

2. **Stage files for THAT task only** (after step 1 complete)
   ```bash
   git add app/Models/User.php tests/Unit/Models/UserTest.php
   # NEVER: git add . or git add -A
   # NEVER: Stage before quality gates pass
   ```

3. **ALWAYS ASK & WAIT FOR APPROVAL (MANDATORY)**
   - **Rule:** After ANY implementation, you MUST ask before committing
   - **Rule:** Do NOT rely on previous message patterns or assume approval
   - **Rule:** Ask in yes/no format: "Implementation complete. Ready to commit?"
   ```
   "Implementation complete. Ready to commit? Task A1 complete: User model with validation
   
   Staged files:
   - app/Models/User.php
   - tests/Unit/Models/UserTest.php
   
   Quality gates: ✅ All passed"
   ```
   **MANDATORY: WAIT for human response**
   - **Approve = any indication of agreement** (flexible, not pattern-based)
   - Do NOT rely on pattern matching from previous messages
   - Valid approval: "commit", "yes", "ok", "go ahead", etc.
   - Any other response = do NOT commit

4. **Execute commit (only after approval)**
   ```bash
   git commit -m "feat: add User model with validation (Task A1)"
   ```

5. **Repeat for next task**

---

### ❌ FORBIDDEN:
- Committing without asking first
- Committing without waiting for approval
- Auto-committing after completing work
- Assuming approval from previous messages
- Committing based on pattern matching
- Using `git add .` or `git add -A`
- Staging work-in-progress
- Committing multiple tasks together
- Writing documentation in Vietnamese or any non-English language

### ✅ REQUIRED:
- Explicit human confirmation for EVERY commit
- Clear list of staged files
- Quality gates status (✅ passed)
- Wait for approval before executing
- Only commit files you directly created/modified
- 1 sub-task = 1 commit

---

### Commit Scope Guidelines:

- **1-7 files:** Perfect (one sub-task)
- **7-15 files:** OK if same logical unit (justify in message)
- **15-30 files:** Must justify in message
- **>30 files:** SPLIT into multiple commits

### Commit Message Format:

**Format:** `<type>(<scope>): <description>`

**Types:** `feat`, `fix`, `docs`, `test`, `refactor`, `style`, `chore`

**Scope:** REQUIRED (module or component name)
- Examples: `core`, `wordpress`, `lmstudio`, `app`, `config`, `plans`, `guides`, `standards`, `workflow`

**AI Tool Metadata (REQUIRED for AI-generated code):**

All AI-generated commits MUST include metadata block:

```bash
git commit -m "<type>(<scope>): <description>

<optional body>

Generated-By: <AI Model Name>
Generated-By-Tool: <Tool Name>
Model: <model-version-date>
Task-ID: <PREFIX-N> or N/A
Plan: docs/plans/... or N/A
Coverage: XX% or N/A or Documentation"
```

**Metadata Rules:**
- ✅ **MUST:** All 6 fields present (cannot omit)
- ✅ **MUST:** Use `N/A` for Task-ID or Plan if not applicable (do not leave blank)
- ✅ **MUST:** Use `Documentation` for Coverage if docs-only commit
- ❌ **FORBIDDEN:** Omitting any metadata field
- ❌ **FORBIDDEN:** Leaving Task-ID or Plan blank

**Standardized Tool Values:**
- `Cursor AI` - Primary IDE with Claude integration
- `GitHub Copilot` - GPT-4 Turbo inline suggestions
- `ChatGPT Plus` - Web-based GPT-4/o1 sessions
- `LM Studio` - Local model testing

**Model Format Examples:**
- `claude-sonnet-3.5-20241022` (Cursor AI default)
- `gpt-4-turbo-2024-04-09` (GitHub Copilot)
- `gpt-4o` or `o1` (ChatGPT Plus)
- `llama-3.1-8b-instruct` (LM Studio local)

**Task ID Format:**
- Pattern: `PREFIX-N` (e.g., SDK-1, AUTH-2, CACHE-3)
- Common Prefixes: SDK, AUTH, CACHE, WP, AI, CORE

**Examples:**
```bash
# ✅ CORRECT workflow with AI metadata
[Complete Task SDK-1: LM Studio SDK scaffold]
composer lint && composer test  # ✅ PASS
git add Modules/LMStudio/Services/Sdk.php tests/Unit/Services/SdkTest.php
# AI asks: "Ready to commit? Task SDK-1: SDK scaffold. Files: Sdk.php, SdkTest.php. Quality: ✅"
# Human: "commit"
git commit -m "feat(core): scaffold LM Studio SDK with health check

Implement base SDK class with HTTP client and health endpoint

Generated-By: Cursor Pro
Generated-By-Tool: Cursor Pro
Model: Auto
Task-ID: SDK-1
Plan: docs/plans/technical/2025-11-13-lm-studio-sdk.md
Coverage: 95%"

[Complete Task AUTH-2: Login FormRequest]
composer lint && composer test  # ✅ PASS
git add app/Http/Requests/LoginRequest.php tests/Unit/Requests/LoginRequestTest.php
# AI asks again
# Human: "commit"  
git commit -m "feat(app): add LoginRequest with validation

Email + password validation with rate limiting rules

Generated-By: GitHub Copilot
Generated-By-Tool: GitHub Copilot
Model: gpt-4-turbo-2024-04-09
Task-ID: AUTH-2
Plan: docs/plans/features/2025-11-14-user-authentication.md
Coverage: 100%"
```

**Query Examples for Analytics:**
```bash
# Find all AI-generated commits
git log --grep="Generated-By:"

# Commits by specific AI tool
git log --grep="Generated-By-Tool: Cursor AI"

# Commits by specific model
git log --grep="Model: claude-sonnet-3.5"

# Commits for specific task
git log --grep="Task-ID: SDK-1"

# Commits with low coverage
git log --grep="Coverage:" --grep="[1-7][0-9]%"
```

**REMEMBER: 1 task = 1 commit. ALWAYS ASK BEFORE EVERY COMMIT!**


