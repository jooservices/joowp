# Plan – Code Quality Remediation

Status: Ready  
Owner: TBD  
Created: 2025-11-12  
Updated: 2025-11-14

## Summary
- Eliminate legacy PHPCS, PHPMD, and PHPStan violations so pre-commit hooks can run without manual bypasses.

## Objectives
- Catalogue current code-quality violations from PHPCS, PHPMD, and PHPStan.
- Prioritise fixes that unblock commits without altering behaviour.
- Apply consistent formatting updates (e.g., string concatenation spacing) across config/bootstrap files.
- Refactor test method names and utilities to meet PSR-12 camelCase requirements.
- Address PHPMD warnings (duplicate code, long methods, etc.) or adjust rule configuration with clear justification.
- Resolve PHPStan findings through stricter typing or targeted suppressions with context.
- Ensure the final baseline runs clean under all quality gates without `--no-verify`.

## Tasks
- [ ] Run `composer lint:phpcs`, `composer analyze:phpmd`, and `composer analyze:phpstan` and export detailed reports.
- [ ] Group fixes by domain (bootstrap/config, app code, tests) to keep commits focused.
- [ ] Update bootstrap/config files to satisfy spacing and formatting rules (PSR-12).
- [ ] Rename PHPUnit test methods to camelCase and adjust any helper traits/classes triggering PSR-12 errors.
- [ ] Investigate PHPMD findings and refactor or silence with documented rationale.
- [ ] Address PHPStan errors with type annotations, generics, or helper abstractions; avoid blanket ignores.
- [ ] Re-run full quality suite to confirm zero violations.
- [ ] Update `docs/principles.md` or `docs/code-quality.md` if any rule adjustments or workflows change.

## Notes
- Coordinate updates with ongoing feature work to avoid massive merge conflicts.
- Prefer incremental commits per area rather than a single monolithic cleanup.
- Communicate any rule relaxations to project stakeholders before implementation.
