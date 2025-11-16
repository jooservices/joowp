# Code Compliance Implementation Plan

**Created:** 2025-11-12  
**Status:** Phase 1-3 Complete (Phase 4 Pending)  
**Priority:** P0 (Critical - Blocks quality gates)  
**Updated:** 2025-11-17  
**Estimated Time:** 4 hours

## Overview

Bring entire codebase into compliance with documented principles:
- `declare(strict_types=1)` in ALL PHP files
- Test coverage ≥ 80% with proper reporting
- `final` classes by default
- Quality pipeline passes without errors

## Current State

### Coverage Gap Analysis
- **Tests:** 18 passing (82 assertions)
- **Estimated Coverage:** 30-40% overall
- **Missing Coverage Scripts:** `test:coverage`, `test:coverage-check`
- **Missing phpunit.xml Config:** `<coverage>` section

### Type Safety Violations
**20+ files missing `declare(strict_types=1)`:**
- `app/Models/User.php`
- `app/Providers/AppServiceProvider.php`
- `app/Http/Controllers/Controller.php`
- `config/*.php` (11 files)
- `database/migrations/*.php` (3 Laravel defaults)
- `Modules/Core/app/Providers/*.php` (3 providers)
- `Modules/Core/database/seeders/CoreDatabaseSeeder.php`

### Non-Final Class Violations
- `app/Logging/ActionLogger.php`
- `app/Models/User.php`
- `app/Providers/AppServiceProvider.php`
- `Modules/Core/app/Providers/*` (3 providers)
- `Modules/Core/app/Http/Controllers/CoreController.php`
- `Modules/Core/database/seeders/CoreDatabaseSeeder.php`

### Quality Pipeline Issues
- PHPCS scanning `bootstrap/cache/*` causing 40+ false errors
- Need exclusion pattern in `phpcs.xml`

## Implementation Tasks

### Phase 1: Quality Pipeline Fixes (15 mins)

- [x] **Task 1.1: Fix PHPCS Configuration**
  - DoD: Add `<exclude-pattern>*/bootstrap/cache/*</exclude-pattern>` to `phpcs.xml` ✅
  - DoD: Run `composer lint:phpcs` - should not scan bootstrap/cache ✅
  - File: `phpcs.xml`
  - Status: Completed

- [x] **Task 1.2: Add Coverage Configuration**
  - DoD: Add `<coverage>` section to `phpunit.xml` ✅
  - DoD: Add `<source>` with app/ and Modules/ includes ✅
  - DoD: Exclude Providers, Migrations, Seeders from coverage ✅
  - File: `phpunit.xml`
  - Status: Completed

- [x] **Task 1.3: Add Composer Coverage Scripts**
  - DoD: Add `test:coverage` script to `composer.json` ✅
  - DoD: Add `test:coverage-check` script with 80% threshold ✅
  - DoD: Run `composer test:coverage-check` - should report current coverage % ✅
  - File: `composer.json`
  - Status: Completed

### Phase 2: Type Safety Compliance (45 mins)

- [x] **Task 2.1: Add `declare(strict_types=1)` to App Layer (3 files)**
  - DoD: Add to `app/Models/User.php` ✅ (already present)
  - DoD: Add to `app/Providers/AppServiceProvider.php` ✅ (already present)
  - DoD: Add to `app/Http/Controllers/Controller.php` ✅ (already present)
  - Pattern: Place immediately after `<?php` opening tag
  - Status: Completed (all files already had strict_types)

- [x] **Task 2.2: Add to Core Module (4 files)**
  - DoD: Add to `Modules/Core/app/Providers/CoreServiceProvider.php` ✅ (already present)
  - DoD: Add to `Modules/Core/app/Providers/RouteServiceProvider.php` ✅ (already present)
  - DoD: Add to `Modules/Core/app/Providers/EventServiceProvider.php` ✅ (already present)
  - DoD: Add to `Modules/Core/database/seeders/CoreDatabaseSeeder.php` ✅ (already present)
  - Status: Completed (all files already had strict_types)

- [x] **Task 2.3: Add to Config Files (11 files)**
  - DoD: Add to all 11 config files in `config/` directory ✅ (all already present)
  - DoD: Pre-commit hook should pass without violations ✅
  - Files: app.php, auth.php, database.php, cache.php, session.php, queue.php, modules.php, logging.php, filesystems.php, mail.php, services.php ✅
  - Status: Completed (all config files already had strict_types)

#### Task 2.4: Laravel Default Migrations
**Files:**
- `database/migrations/0001_01_01_000000_create_users_table.php`
- `database/migrations/0001_01_01_000001_create_cache_table.php`
- `database/migrations/0001_01_01_000002_create_jobs_table.php`

**Decision:** Add OR exclude from strict_types requirement (Laravel defaults)

**Validation:**
```bash
# Check all files have strict_types
find . -name "*.php" -path "*/app/*" -exec grep -L "declare(strict_types=1)" {} \;
composer analyze:phpstan  # Should pass with strict types
```

### Phase 3: Final Class Compliance (30 mins)

- [x] **Task 3.1: Update App Layer Classes**
  - `app/Logging/ActionLogger.php` → `final class ActionLogger` ✅
  - `app/Models/User.php` → Keep as-is (extended by Authenticatable) ✅
  - `app/Providers/AppServiceProvider.php` → Keep as-is (ServiceProvider) ✅ (already final)
  - Status: Completed

- [x] **Task 3.2: Update Core Module**
  - `Modules/Core/app/Http/Controllers/CoreController.php` → `final class` ✅ (already final)
  - `Modules/Core/database/seeders/CoreDatabaseSeeder.php` → `final class` ✅ (already final)
  - Providers → Keep as-is (ServiceProvider base) ✅
  - Status: Completed

**Validation:**
```bash
composer lint  # Should pass without non-final warnings
```

### Phase 4: Write Missing Tests (2-3 hours)

#### Priority Test Files Needed

**Core Services (Target: 95%)**
1. `tests/Unit/Services/CategoryServiceTest.php` - Full coverage
2. `tests/Unit/Services/WordPress/SdkTest.php` - Extend existing

**Controllers (Target: 90%)**
1. `tests/Feature/CategoryControllerTest.php` - All CRUD endpoints
2. `tests/Feature/TokenControllerTest.php` - Extend existing

**Models (Target: 85%)**
1. `tests/Unit/Models/UserTest.php` - Accessors, mutators, relationships
2. `tests/Unit/Models/WpTokenTest.php` - Token hashing, retrieval

**Utilities (Target: 80%)**
1. `tests/Unit/Logging/ActionLoggerTest.php` - Extend existing
2. `tests/Unit/Http/Responses/ApiResponseTest.php` - All envelope scenarios

**Test Template:**
```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;

final class ExampleServiceTest extends TestCase
{
    public function test_example_method(): void
    {
        // Arrange
        $service = new ExampleService();
        
        // Act
        $result = $service->doSomething();
        
        // Assert
        $this->assertTrue($result);
    }
}
```

**Validation:**
```bash
composer test:coverage-check  # Must show ≥ 80%
```

### Phase 5: Final Validation (15 mins)

```bash
# 1. Quality pipeline
composer lint

# 2. Coverage check
composer test:coverage-check

# 3. TypeScript
npm run typecheck

# 4. Build
npm run build
```

**All 4 must pass.**

## Success Criteria

- [ ] `composer lint` passes (Pint → PHPCS → PHPMD → PHPStan)
- [ ] `composer test:coverage-check` passes (≥ 80%)
- [ ] All app/Modules files have `declare(strict_types=1)`
- [ ] All applicable classes are `final`
- [ ] PHPCS no longer scans bootstrap/cache
- [ ] Coverage report accessible at `storage/coverage/index.html`
- [ ] CI/CD gates documented and functional

## Rollout Strategy

1. **Create feature branch:** `git checkout -b feat/code-compliance`
2. **Execute Phase 1-3:** Configuration + type safety (1 hour)
3. **Commit:** `git commit -m "chore: add strict types and coverage config"`
4. **Execute Phase 4:** Write tests iteratively (2-3 hours)
5. **Commit per test file:** `git commit -m "test: add XService tests (95% coverage)"`
6. **Final validation:** All 4 quality checks
7. **Merge to main:** After all gates pass

## Notes

- **Config files decision:** Document in principles.md if excluded
- **Laravel migrations:** May exclude from strict_types (vendor defaults)
- **Providers:** Cannot be final (extend ServiceProvider)
- **Coverage exclusions:** Documented in phpunit.xml `<exclude>`

## Related Documents

- `docs/principles.md` - Engineering principles
- `docs/code-quality.md` - Quality pipeline details
- `.github/copilot-instructions.md` - AI coding guidelines
