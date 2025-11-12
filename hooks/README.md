# Pre-commit Hook

This directory contains the git pre-commit hook that enforces JOOservices Platform quality standards.

## Installation

The hook is automatically created at `.git/hooks/pre-commit`. To reinstall:

```bash
# Copy from this template
cp hooks/pre-commit.sample .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
```

## What It Checks

1. **Strict Types Declaration** - All staged PHP files must have `declare(strict_types=1)`
2. **Laravel Pint** - Auto-fixes code style issues
3. **PHP_CodeSniffer** - Validates PSR-12 compliance
4. **PHPMD** - Checks design quality (SOLID principles)
5. **PHPStan** - Static analysis at maximum level
6. **Tests** - All tests must pass
7. **TypeScript** - Type checking (if .ts or .vue files staged)

## Quality Gates

All checks must pass before commit is allowed. If any check fails, the commit is blocked.

## Bypassing (Not Recommended)

In rare cases where you need to bypass the hook:

```bash
git commit --no-verify -m "message"
```

**WARNING:** Only use `--no-verify` for emergency situations. All bypassed commits must be fixed in follow-up commits.

## Performance

Average runtime: 15-30 seconds depending on staged files.
- Quick commits (docs only): ~5 seconds
- Full commits (code + tests): ~30 seconds

## See Also

- [Engineering Principles](../docs/principles.md)
- [Code Quality Workflow](../docs/code-quality.md)
- [Code Compliance Plan](../docs/plans/2025-11-12-code-compliance.md)
