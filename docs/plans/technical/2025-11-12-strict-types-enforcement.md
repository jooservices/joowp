# Plan – Strict Types Enforcement

Status: On Hold  
Priority: P0  
Owner: TBD  
Created: 2025-11-12  
Updated: 2025-11-14  
Target: TBD (was 2025-11-13, postponed pending resource allocation)  
Epic: Code Quality

## Summary
Add mandatory `declare(strict_types=1)` to all PHP files missing this requirement. Pre-commit hook blocks commits without strict types, but 21 existing files have violations that prevent clean development workflow.

**Scope:** Existing codebase cleanup only. All new files already enforced by pre-commit hook.

## Dependencies
- Access to modify app/, config/, database/, and Modules/ directories
- Ability to run quality pipeline tools locally
- Pre-commit hook configuration (already in place)

## Objectives
- Achieve 100% strict types compliance across entire PHP codebase (21 files)
- Enable clean commits without pre-commit hook violations
- Eliminate potential type coercion bugs from non-strict mode
- Ensure PHPStan max level analysis works correctly

## Tasks
- [ ] Fix App layer files (3 files)
  - DoD: Add `declare(strict_types=1)` to app/Models/User.php
  - DoD: Add `declare(strict_types=1)` to app/Providers/AppServiceProvider.php  
  - DoD: Add `declare(strict_types=1)` to app/Http/Controllers/Controller.php
  - DoD: All files pass `grep -L "declare(strict_types=1)"` verification
  - Estimated: 1 hour

- [ ] Fix Config layer files (11 files)
  - DoD: Add strict types declaration to all config/*.php files
  - DoD: Verify placement before return statements in config arrays
  - DoD: All 11 config files have proper strict types declaration
  - DoD: Configuration still loads correctly after changes
  - Estimated: 1.5 hours

- [ ] Fix Database migration files (3 files)
  - DoD: Add strict types to database/migrations/0001_01_01_000000_create_users_table.php
  - DoD: Add strict types to database/migrations/0001_01_01_000001_create_cache_table.php
  - DoD: Add strict types to database/migrations/0001_01_01_000002_create_jobs_table.php
  - DoD: Migrations still run successfully with `php artisan migrate:fresh`
  - Estimated: 0.5 hours

- [ ] Fix Core Module files (4 files)
  - DoD: Add strict types to Modules/Core/app/Providers/CoreServiceProvider.php
  - DoD: Add strict types to Modules/Core/app/Providers/RouteServiceProvider.php
  - DoD: Add strict types to Modules/Core/app/Providers/EventServiceProvider.php
  - DoD: Add strict types to Modules/Core/database/seeders/CoreDatabaseSeeder.php
  - DoD: Module still loads and functions correctly
  - Estimated: 1 hour

- [ ] Run comprehensive verification
  - DoD: `find app config database/migrations Modules/Core -name "*.php" -exec grep -L "declare(strict_types=1)" {} \;` returns empty
  - DoD: Full quality pipeline passes: `composer lint`
  - DoD: All tests pass: `composer test`
  - DoD: Pre-commit hook validation passes on test commit
  - Estimated: 0.5 hours

**Total Estimated Effort:** 4.5 hours (~half day for 1 developer)

## Success Metrics
- **Compliance:** 100% of PHP files have `declare(strict_types=1)` (21/21 files)
- **Quality:** Full pipeline passes without strict types violations
- **Workflow:** Pre-commit hooks pass without `--no-verify` flag
- **Stability:** All existing tests continue to pass after changes

## Risks & Mitigations
| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Tests fail due to type coercion bugs | Medium | Low | Fix underlying bugs rather than removing strict types |
| Config loading breaks | High | Low | Test configuration loading after each file change |
| Migration issues | Medium | Low | Test migrations in fresh database environment |
| Module loading problems | Medium | Low | Verify service providers still register correctly |

## Related Plans
- `docs/technical/code-compliance.md` - Overall code quality improvement strategy
- `docs/architecture/principles.md#type-safety` - PHP 8.4 Strict Compliance principle (#2)
- Phase 2: Final class enforcement and readonly property adoption

## Notes
- This is blocking work - P0 priority because it prevents clean git workflow
- Changes are additive only - no logic modifications required
- Pre-existing type coercion bugs (if any) will surface as test failures
- Consider this a prerequisite for all other quality improvement work
