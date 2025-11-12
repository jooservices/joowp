# JOOservices Platform - AI Coding Instructions

Modular Laravel 12 + Vue 3 platform targeting PHP 8.4 with strict type safety and comprehensive quality gates. Before starting any work, review `docs/principles.md` and `docs/code-quality.md`.

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

**Key architecture:**
- Modular design via `nwidart/laravel-modules` - each domain in its own module
- Core module provides shared services (WordPress SDK, utilities) - never duplicate
- API responses use `App\Http\Responses\ApiResponse` envelope pattern
- Frontend is TypeScript-only Vue 3 + Inertia with dark theme

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
php artisan module:make Products
```
Then:
1. Enable in `modules_statuses.json`: `"Products": true`
2. Create config at `Modules/Products/config/config.php`
3. Register services in `Modules/Products/Providers/ProductsServiceProvider`
4. Add routes in `routes/api.php` (auto-prefixed with `/api/v1`)
5. Create `vite.config.js` if module has assets
6. Run migrations: `php artisan migrate`

### Adding WordPress Integration
```php
// Inject the SDK contract
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
- **Pull requests that decrease coverage are automatically rejected**

## Project-Specific Patterns

### WordPress Integration
- **Use the SDK contract**: Inject `Modules\Core\Services\WordPress\Contracts\SdkContract` - never use Guzzle directly
- **Never call WordPress from frontend** - always through Laravel API routes (e.g., `/api/v1/wordpress/token`)
- **JWT tokens**: Managed via `wp_tokens` table with "Remember token" functionality
- **Configuration**: Set via `config('core.wordpress')` - see `Modules/Core/config/config.php`
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
**Two dedicated log channels** (configured in `config/logging.php`):

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

2. **External Log** (`external` channel) - Third-party API calls:
- Used automatically by WordPress SDK for all requests
- Logs method, URI, sanitized options (passwords/tokens masked)
- Stored in `storage/logs/external.log` (14 day retention)
- See `Modules\Core\Services\WordPress\Sdk::logExternalDispatch()` for pattern

### Frontend Patterns  
- **Pages**: Vue SFCs in `resources/js/Pages/` using TypeScript
- **Theme**: Dark theme aesthetic across all UI surfaces
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

- `docs/principles.md` - Core engineering requirements
- `docs/code-quality.md` - Detailed tooling pipeline  
- `docs/guides/core-wordpress-sdk.md` - WordPress integration guide
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

**CRITICAL: Only commit files you directly created or modified**

```bash
# ❌ WRONG - Commits everything including others' work
git add .
git commit -m "docs: update instructions"

# ✅ CORRECT - Explicit files only
git add .github/copilot-instructions.md
git commit -m "docs: update AI coding instructions"
```

**Pre-commit checklist:**
1. Run `git status` - review what will be committed
2. Run `git diff --cached` - verify only your changes
3. Stage specific files: `git add <file1> <file2>`
4. Never use `git add .` or `git add -A` unless you created ALL changed files
5. Commit message format: `<type>: <description>`
   - Types: `feat`, `fix`, `docs`, `test`, `refactor`, `style`, `chore`

**Commit scope guidelines:**
- **1 commit = 1 logical unit of work**
- **1-5 files:** Usually OK
- **5-15 files:** Acceptable if same feature (e.g., Controller + Service + Tests)
- **15-30 files:** Justify in message (e.g., new module setup)
- **>30 files:** Split into multiple commits
- **Decision rule:** "Could this be reverted independently?" → Yes = good boundary

**Examples:**
```bash
# ✅ GOOD - Single feature with tests (12 files)
git commit -m "feat: add CategoryController with CRUD endpoints

- Controller, Service, 4 FormRequests
- 2 feature tests, 1 unit test"

# ✅ GOOD - Related config changes (3 files)
git commit -m "chore: add test coverage configuration

- phpunit.xml, composer.json, phpcs.xml"

# ❌ BAD - Mixed concerns
git commit -m "update stuff"
# Contains: feature + docs + bugfix (should be 3 commits)
```

**Split when:** Multiple features, unrelated changes, different scopes  
**Keep together when:** Feature + tests, interface + implementation, atomic CRUD


