# Code Compliance Implementation Plan

**Created:** 2025-11-12  
**Status:** To Do  
**Priority:** P0 (Critical - Blocks quality gates)  
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

#### Task 1.1: Fix PHPCS Configuration
**File:** `phpcs.xml`  
**Change:** Add bootstrap cache exclusion
```xml
<exclude-pattern>*/bootstrap/cache/*</exclude-pattern>
```

#### Task 1.2: Add Coverage Configuration
**File:** `phpunit.xml`  
**Change:** Add `<coverage>` section
```xml
<coverage processUncoveredFiles="true">
    <report>
        <html outputDirectory="storage/coverage"/>
        <text outputFile="php://stdout"/>
    </report>
</coverage>

<source>
    <include>
        <directory>app</directory>
        <directory>Modules</directory>
    </include>
    <exclude>
        <directory>app/Providers</directory>
        <directory>Modules/*/Providers</directory>
        <directory>Modules/*/Database/Migrations</directory>
        <directory>Modules/*/Database/Seeders</directory>
    </exclude>
</source>
```

#### Task 1.3: Add Composer Coverage Scripts
**File:** `composer.json`  
**Change:** Add to `scripts` section
```json
"test:coverage": "XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-html storage/coverage --coverage-text",
"test:coverage-check": "XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-text --coverage-clover=coverage.xml && php -r \"$xml = simplexml_load_file('coverage.xml'); $metrics = $xml->project->metrics; $coverage = (float)$metrics['coveredstatements'] / (float)$metrics['statements'] * 100; echo 'Coverage: ' . round($coverage, 2) . '%' . PHP_EOL; exit($coverage < 80 ? 1 : 0);\""
```

**Validation:**
```bash
composer test:coverage  # Should generate storage/coverage/index.html
composer test:coverage-check  # Should report current %
```

### Phase 2: Type Safety Compliance (45 mins)

#### Task 2.1: Add `declare(strict_types=1)` to App Layer
**Files to update:**
1. `app/Models/User.php`
2. `app/Providers/AppServiceProvider.php`
3. `app/Http/Controllers/Controller.php`

**Pattern:**
```php
<?php

declare(strict_types=1);

namespace App\...;
```

#### Task 2.2: Add to Core Module
**Files to update:**
1. `Modules/Core/app/Providers/CoreServiceProvider.php`
2. `Modules/Core/app/Providers/RouteServiceProvider.php`
3. `Modules/Core/app/Providers/EventServiceProvider.php`
4. `Modules/Core/database/seeders/CoreDatabaseSeeder.php`

#### Task 2.3: Add to Config Files (Decision Required)
**Files (11 total):**
- `config/app.php`
- `config/auth.php`
- `config/database.php`
- `config/cache.php`
- `config/session.php`
- `config/queue.php`
- `config/modules.php`
- `config/logging.php`
- `config/filesystems.php`
- `config/mail.php`
- `config/services.php`

**Decision:** Add to all for consistency OR document exception in principles.md

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

#### Task 3.1: Update App Layer Classes
**Files:**
1. `app/Logging/ActionLogger.php` → `final class ActionLogger`
2. `app/Models/User.php` → Keep as-is (extended by Authenticatable)
3. `app/Providers/AppServiceProvider.php` → Keep as-is (ServiceProvider)

#### Task 3.2: Update Core Module
**Files:**
1. `Modules/Core/app/Http/Controllers/CoreController.php` → `final class`
2. `Modules/Core/database/seeders/CoreDatabaseSeeder.php` → `final class`
3. Providers → Keep as-is (ServiceProvider base)

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
