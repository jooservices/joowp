# Strict Types Enforcement Plan

**Created:** 2025-11-12  
**Status:** To Do  
**Priority:** P0 (Critical - Blocks quality gates)  
**Estimated Time:** 45 minutes

## Overview

Add `declare(strict_types=1)` to all PHP files missing this mandatory requirement. Pre-commit hook already enforces this for new files, but existing codebase has 20+ violations.

## Rationale

**Why this is critical:**
- Type safety is non-negotiable in our codebase
- Prevents runtime type coercion bugs
- PHPStan max level requires strict types
- Pre-commit hook blocks commits without it
- Existing violations prevent clean commits

## Current Violations

### App Layer (3 files)
1. `app/Models/User.php`
2. `app/Providers/AppServiceProvider.php`
3. `app/Http/Controllers/Controller.php`

### Config Layer (11 files)
1. `config/app.php`
2. `config/auth.php`
3. `config/cache.php`
4. `config/database.php`
5. `config/filesystems.php`
6. `config/logging.php`
7. `config/mail.php`
8. `config/modules.php`
9. `config/queue.php`
10. `config/services.php`
11. `config/session.php`

### Database Layer (3 Laravel defaults)
1. `database/migrations/0001_01_01_000000_create_users_table.php`
2. `database/migrations/0001_01_01_000001_create_cache_table.php`
3. `database/migrations/0001_01_01_000002_create_jobs_table.php`

### Core Module (4 files)
1. `Modules/Core/app/Providers/CoreServiceProvider.php`
2. `Modules/Core/app/Providers/RouteServiceProvider.php`
3. `Modules/Core/app/Providers/EventServiceProvider.php`
4. `Modules/Core/database/seeders/CoreDatabaseSeeder.php`

**Total:** 21 files

## Implementation Tasks

### Task 1: App Layer (5 mins)

**Files:**
- `app/Models/User.php`
- `app/Providers/AppServiceProvider.php`
- `app/Http/Controllers/Controller.php`

**Change for each file:**
```php
<?php

declare(strict_types=1);  // ADD THIS LINE

namespace App\...;
```

**Validation:**
```bash
grep -L "declare(strict_types=1)" app/Models/User.php app/Providers/AppServiceProvider.php app/Http/Controllers/Controller.php
# Should return empty (no results = all have declare)
```

### Task 2: Config Layer (10 mins)

**Files:** All 11 files in `config/*.php`

**Change for each file:**
```php
<?php

declare(strict_types=1);  // ADD THIS LINE

return [
```

**Note:** Config files return arrays directly, so declaration goes before return statement.

**Validation:**
```bash
grep -L "declare(strict_types=1)" config/*.php
# Should return empty
```

### Task 3: Database Layer (10 mins)

**Files:** 3 Laravel migration files in `database/migrations/`

**Change for each migration:**
```php
<?php

declare(strict_types=1);  // ADD THIS LINE

use Illuminate\Database\Migrations\Migration;
```

**Validation:**
```bash
grep -L "declare(strict_types=1)" database/migrations/0001_01_01_*.php
# Should return empty
```

### Task 4: Core Module (10 mins)

**Files:**
- `Modules/Core/app/Providers/CoreServiceProvider.php`
- `Modules/Core/app/Providers/RouteServiceProvider.php`
- `Modules/Core/app/Providers/EventServiceProvider.php`
- `Modules/Core/database/seeders/CoreDatabaseSeeder.php`

**Change for each file:**
```php
<?php

declare(strict_types=1);  // ADD THIS LINE

namespace Modules\Core\...;
```

**Validation:**
```bash
grep -L "declare(strict_types=1)" Modules/Core/app/Providers/*.php Modules/Core/database/seeders/CoreDatabaseSeeder.php
# Should return empty
```

### Task 5: Final Verification (5 mins)

**Verify all PHP files have strict types:**
```bash
# Check all PHP files in key directories
find app config database/migrations Modules/Core/app Modules/Core/database/seeders -name "*.php" -exec grep -L "declare(strict_types=1)" {} \;
# Should return empty if all files fixed
```

**Run quality pipeline:**
```bash
composer lint:pint      # Should pass
composer lint:phpcs     # Should pass
composer analyze:phpmd  # Should pass
composer analyze:phpstan # Should pass
```

**Test pre-commit hook:**
```bash
# Make a trivial change to trigger hook
touch test.txt
git add test.txt
git commit -m "test: verify strict types enforcement"
# Hook should check PHP files and pass
git reset HEAD~1
rm test.txt
```

## Acceptance Criteria

- [ ] All 21 files have `declare(strict_types=1)` after `<?php` opening tag
- [ ] Grep verification returns empty (no files missing declaration)
- [ ] Quality pipeline passes without strict types violations
- [ ] Pre-commit hook validation passes
- [ ] PHPStan analysis completes without missing strict types errors

## Risk Assessment

**Low Risk:**
- Adding `declare(strict_types=1)` is additive only
- Doesn't change function signatures or logic
- PHP will catch type mismatches immediately if any exist
- All tests should still pass (or reveal pre-existing bugs)

**If tests fail after adding:**
- Indicates pre-existing type coercion bug
- Must fix the actual type issue, not remove strict types
- Good thing we're catching it now

## Post-Implementation

After completion:
1. Commit changes: `git commit -m "refactor: add declare(strict_types=1) to all PHP files"`
2. Run full test suite: `composer test`
3. Update compliance plan status
4. Move to Phase 3 (Final Classes)

## References

- [Engineering Principles](../../architecture/principles.md#type-safety-first)
- [Code Compliance Plan](../../technical/code-compliance.md)
- [Pre-commit Hook](../../hooks/README.md)
