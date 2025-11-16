# Pre-commit Hook

This directory contains the git pre-commit hook that enforces JOOservices Platform quality standards.

## Installation

The hooks are automatically created at `.git/hooks/`. To reinstall:

```bash
# Copy pre-commit hook
cp hooks/pre-commit.sample .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit

# Copy commit-msg hook
cp hooks/commit-msg.sample .git/hooks/commit-msg
chmod +x .git/hooks/commit-msg
```

## What It Checks

### Pre-commit Hook (`pre-commit`)
1. **Strict Types Declaration** - All staged PHP files must have `declare(strict_types=1)`
2. **Laravel Pint** - Auto-fixes code style issues
3. **PHP_CodeSniffer** - Validates PSR-12 compliance
4. **PHPMD** - Checks design quality (SOLID principles)
5. **PHPStan** - Static analysis at maximum level
6. **Tests** - All tests must pass
7. **TypeScript** - Type checking (if .ts or .vue files staged)

### Commit-msg Hook (`commit-msg`)
8. **Commit Message Metadata** - Validates metadata block for AI-generated commits (Generated-By, Generated-By-Tool, Model, Task-ID, Plan, Coverage). See `docs/reference/standards.md#commit-message-metadata` for format requirements.

## Quality Gates

All checks must pass before commit is allowed. If any check fails, the commit is blocked.

## Bypassing Hooks

### Pre-commit Hook
In rare cases where you need to bypass the pre-commit hook:

```bash
git commit --no-verify -m "message"
```

**WARNING:** Only use `--no-verify` for emergency situations. All bypassed commits must be fixed in follow-up commits.

### Commit-msg Hook (Metadata Validation)
**❌ NO BYPASS ALLOWED** - The `commit-msg` hook for metadata validation **CANNOT be bypassed**.

**Technical Note:** `--no-verify` only bypasses `pre-commit` hook, NOT `commit-msg` hook. This is Git behavior, not project policy.

**If you need help:**
- Use helper script: `./scripts/git-commit-template.sh`
- See format: `docs/reference/standards.md#commit-message-metadata`

## Performance

Average runtime: 15-30 seconds depending on staged files.
- Quick commits (docs only): ~5 seconds
- Full commits (code + tests): ~30 seconds

## See Also

- [Engineering Principles](../docs/principles.md)
- [Code Quality Workflow](../docs/code-quality.md)
- [Code Compliance Plan](../docs/plans/2025-11-12-code-compliance.md)
