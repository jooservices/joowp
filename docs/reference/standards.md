# Standards Reference

Quick lookup for all concrete rules, coverage targets, tool configurations, and exact requirements.

---

## Type Safety Standards

### MANDATORY Requirements
- ✅ **MUST:** `declare(strict_types=1);` in ALL PHP files (no exceptions)
- ✅ **MUST:** Explicit types on all method parameters and return values
- ✅ **MUST:** `final` keyword for all classes by default
- ✅ **MUST:** `readonly` modifier for constructor-injected dependencies
- ❌ **FORBIDDEN:** `mixed` type (except third-party interfaces with justification)

### PHPDoc Requirements
- ✅ **MUST:** Document array shapes: `@param array<string, mixed> $data`
- ✅ **MUST:** Document collection types: `@return array<int, User>`

### Tool Configuration
- PHPStan level: `max`
- PHPStan rules: `checkMissingIterableValueType: true`, `checkGenericClassInNonGenericObjectType: true`

---

## Quality Pipeline Standards

### Tool Execution Order (MANDATORY)
1. **Laravel Pint** → `composer lint:pint`
2. **PHP_CodeSniffer** → `composer lint:phpcs`
3. **PHPMD** → `composer analyze:phpmd`
4. **PHPStan** → `composer analyze:phpstan`

### Commands
```bash
composer lint              # All tools in order
composer lint:pint         # Laravel Pint only
composer lint:phpcs        # PHPCS only
composer analyze:phpmd     # PHPMD only
composer analyze:phpstan   # PHPStan only
```

### Configuration Files
- Laravel Pint: `pint.json`
- PHPCS: `phpcs.xml`
- PHPMD: `phpmd.xml`
- PHPStan: `phpstan.neon.dist`

### Requirements
- ✅ **MUST:** All tools pass before commit (zero violations)
- ❌ **FORBIDDEN:** Global suppressions in config files
- ⚠️ **DISCOURAGED:** Line-level suppressions without inline justification

---

## Test Coverage Standards

### Coverage Targets (CI-Enforced)

| Layer | Minimum Coverage | 
|-------|-----------------|
| **Overall Project** | **80%** |
| **Core Module Services** | **95%** |
| **API Controllers** | **90%** |
| **FormRequests** | **100%** |
| **Models (business logic)** | **85%** |
| **Repositories** | **80%** |
| **Middleware** | **90%** |
| **SDK Classes** | **95%** |

### Coverage Exclusions
- Service Providers (framework boilerplate)
- Migrations (schema definitions)
- Configuration files (data, not logic)
- Blade templates (frontend presentation)

### Commands
```bash
composer test                 # Run all tests
composer test:coverage        # Generate HTML coverage report
composer test:coverage-check  # Enforce coverage thresholds (CI gate)
```

### New Code Requirements
- ✅ **MUST:** Every new class requires accompanying unit tests before merge
- ✅ **MUST:** PRs that decrease coverage are automatically rejected
- ✅ **MUST:** Coverage gaps in modified code addressed in same PR

### Test File Naming
- Unit tests: `tests/Unit/{Namespace}/{ClassName}Test.php`
- Feature tests: `tests/Feature/{Module}/{Feature}Test.php`
- Test methods: `test_{method_name}_{scenario}_{expected_outcome}()`

---

## Module Standards

### Naming Convention
- ✅ **MUST:** Singular PascalCase names matching business domain
- ✅ **Correct:** `WordPress`, `Twitter`, `AI`, `Product`
- ❌ **Wrong:** `Posts` (technical feature), `WordPressIntegration` (redundant)

### Module Creation
```bash
php artisan module:make {DomainName}
```

### Module Structure (MANDATORY)
```
Modules/{DomainName}/
├── Config/config.php              # MUST: Module configuration
├── Database/Migrations/           # MUST: Domain-specific migrations
├── Http/
│   ├── Controllers/               # MUST: API controllers (final classes)
│   └── Requests/                  # MUST: FormRequest validation
├── Models/                        # MUST: Domain models (final classes)
├── Providers/
│   └── {Domain}ServiceProvider.php # MUST: Service registration
├── Services/                      # MUST: Business logic (final classes)
│   └── Contracts/                 # MUST: Service interfaces
├── routes/api.php                 # MUST: API routes (auto-prefixed /api/v1)
└── module.json                    # MUST: Module metadata
```

### Module Decision Rule
**"Does this contain business-specific logic?"**
- **YES** → Business domain module (WordPress SDK → WordPress module)
- **NO** → Core module (ActionLogger → Core module)

### Module Activation
Configure in `modules_statuses.json`:
```json
{
    "Core": true,
    "WordPress": true,
    "Twitter": false,
    "AI": true
}
```

---

## API Response Standards

### Response Envelope (MANDATORY)
```php
use App\Http\Responses\ApiResponse;

// Success
return ApiResponse::success(
    code: 'resource.created',
    message: 'Resource created successfully',
    data: $resource->toArray(),
    meta: ['timestamp' => now()],
    status: 201
);

// Error
return ApiResponse::error(
    code: 'resource.validation_failed',
    message: 'Invalid data',
    meta: ['errors' => $validator->errors()],
    data: [],
    status: 422
);
```

### Response Structure
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

### Resource vs Raw JSON
- ✅ **Use Resource:** Data maps to an Eloquent Model
- ✅ **Use Raw JSON:** Authentication responses, aggregated stats, confirmations

---

## Audit Logging Standards

### Action Log Channel
- **File:** `storage/logs/action.log`
- **Retention:** 30 days
- **Use:** Domain mutations (create, update, delete)

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

### External Log Channel
- **File:** `storage/logs/external.log`
- **Retention:** 14 days
- **Use:** Third-party API calls (automatic in WordPress SDK)

### Requirements
- ✅ **MUST:** Log all domain mutations using ActionLogger
- ✅ **MUST:** Include actor, before/after state, metadata
- ✅ **MUST:** Mask sensitive data (passwords, tokens) in logs

---

## WordPress Integration Standards

### SDK Usage
- ✅ **MUST:** Use `SdkContract`, never Guzzle directly
- ✅ **MUST:** Proxy through Laravel - never call WordPress from frontend
- ✅ **MUST:** Use SDK methods: `posts()`, `categories()`, `token()`, etc.

### Configuration
- WordPress URL: `WP_URL` (default: `https://soulevil.com`)
- API timeout: `WORDPRESS_API_TIMEOUT` (default: 10 seconds)
- User agent: `WORDPRESS_API_USER_AGENT` (default: `CoreWordPressSdk/1.0`)
- Namespace: `WORDPRESS_API_NAMESPACE` (default: `wp/v2`)

### JWT Token Management
- Storage: `wp_tokens` table (hashed)
- Remember functionality: Optional checkbox in navbar
- Auto-injection: Bearer header via token resolver

---

## Frontend Standards

### TypeScript Requirements
- ✅ **MUST:** TypeScript-only (no JavaScript files)
- ✅ **MUST:** Explicit types for all variables and functions
- ✅ **MUST:** Strict mode enabled in `tsconfig.json`

### UI/UX Standards
- ✅ **MUST:** Dark theme aesthetic on all surfaces
- ✅ **MUST:** Bootstrap + FontAwesome exclusively
- ✅ **MUST:** Binary toggles use switches, not checkboxes
- ✅ **MUST:** Loading states: disable controls + spinner + dim context
- ✅ **MUST:** Toast notifications: top-right, auto-dismiss (5s) non-fatal errors

### Layout Requirements
- ✅ **MUST:** All pages include Navbar component
- ✅ **MUST:** Active state indication on current page's parent/child nav items
- ✅ **MUST:** Container layout with `container-fluid` and Bootstrap grid

### Delete Action Pattern
- ✅ **MUST:** Delete buttons use `btn-danger` class (red styling)
- ✅ **MUST:** Confirmation modal before execution
- ✅ **MUST:** Modal includes: action description, item name, irreversibility warning
- ✅ **MUST:** FontAwesome icons: `fa-trash`, `fa-times`, etc.

### Error Handling
```typescript
try {
    await axios.post('/api/v1/resource', data);
    toast.success('Success message');
} catch (error) {
    if (axios.isAxiosError(error)) {
        toast.error(error.response?.data?.message || 'Error occurred', {
            autoClose: false  // Sticky for fatal errors
        });
    }
}
```

---

## Service Layer Standards

### Flow Pattern
`Controller → Service → Repository/SDK`

### Service Rules
- ✅ **MUST:** One service = one business logic domain
- ✅ **MUST:** Services call SDK directly (no repository for external APIs)
- ✅ **MUST:** Use Repository only for local database operations
- ✅ **MUST:** Inject other services for different business logic domains

### Repository Rules
- ✅ **MUST:** Create repositories only for database access
- ✅ **MUST:** Return Models or null
- ❌ **FORBIDDEN:** Business logic in repositories
- ❌ **FORBIDDEN:** Repositories for external APIs

### FormRequest Rules
- ✅ **MUST:** All validation in FormRequest classes
- ✅ **MUST:** Typed `rules()` method: `@return array<string, array<int, string|Rule>>`
- ✅ **MUST:** 100% test coverage for FormRequests

---

## Git Standards

### Commit Discipline
- ✅ **MUST:** Only commit files you directly created/modified
- ❌ **FORBIDDEN:** `git add .` or `git add -A` (commits others' work)
- ✅ **MUST:** Use explicit file paths: `git add specific-file.php`

### Pre-commit Checklist
1. `git status` - Review what will be committed
2. `git diff --cached` - Verify only your changes
3. `git add <specific-files>` - Stage explicit files
4. `composer lint && composer test:coverage-check` - Quality gates
5. `npm run typecheck` - TypeScript validation

### Commit Message Format
```
<type>(<scope>): <description>

<optional body>
```

**Format Requirements:**
- ✅ **MUST:** Format: `<type>(<scope>): <description>`
- ✅ **MUST:** Scope is required (module or component name)
- ✅ **MUST:** Types: `feat`, `fix`, `docs`, `test`, `refactor`, `style`, `chore`

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation only
- `test`: Adding/updating tests
- `refactor`: Code change (no new features/fixes)
- `style`: Formatting, no logic change
- `chore`: Build/tooling/config changes

**Scope Examples:**
- `core`: Core module changes
- `wordpress`: WordPress module changes
- `lmstudio`: LM Studio module changes
- `app`: Root application changes
- `config`: Configuration changes
- `plans`: Planning documentation

**Examples:**
```
feat(core): add HTTP client service
fix(wordpress): resolve category parent validation
docs(plans): update LM Studio SDK plan
test(lmstudio): add inference service tests
```

### Commit Size Guidelines
- **1-5 files:** Usually appropriate
- **5-15 files:** Acceptable if same feature (Controller + Service + Tests)
- **15-30 files:** Justify in commit message (e.g., new module setup)
- **>30 files:** Consider splitting into multiple commits

### Decision Rule
"Could this commit be reverted independently without breaking anything?"
- ✅ Yes → Good commit boundary
- ❌ No → Consider combining or splitting

---

## Pre-commit Validation

### Required Checks (MANDATORY)
```bash
# 1. Quality pipeline
composer lint

# 2. Test coverage enforcement
composer test:coverage-check

# 3. TypeScript validation
npm run typecheck

# 4. Build check
npm run build
```

### Enforcement
- Pre-commit hook: Checks for `declare(strict_types=1)` presence
- CI/CD: All 4 checks must pass (no bypass for anyone)
- Build failure: Coverage below thresholds, quality violations, TypeScript errors

---

## Quick Reference Commands

### Development
```bash
composer dev                 # Start full dev stack (server + queue + logs + vite)
composer lint                # Full quality pipeline
composer test:coverage-check # Test with coverage enforcement
npm run typecheck            # TypeScript validation
npm run build               # Production build check
```

### Quality Tools Individual
```bash
composer lint:pint           # Auto-fix style issues
composer lint:phpcs          # PSR-12 validation
composer analyze:phpmd       # Design quality checks
composer analyze:phpstan     # Static analysis (max level)
```

### Module Management
```bash
php artisan module:make WordPress  # Create new module
php artisan migrate                # Run all module migrations
```

### Testing
```bash
composer test                      # Run all tests
composer test -- --filter UserServiceTest  # Specific test
composer test:coverage            # HTML coverage report
```