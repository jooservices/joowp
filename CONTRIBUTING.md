# Contributing to JOOservices Platform

Thank you for considering contributing to the JOOservices Platform! This document provides guidelines and workflows for contributing to the project.

## Before You Start

**Read these documents first:**
- [Engineering Principles](docs/principles.md) - Core engineering requirements
- [Code Quality Workflow](docs/code-quality.md) - Quality pipeline details
- [AI Coding Instructions](.github/copilot-instructions.md) - AI assistant guidelines
- [Code Compliance Plan](docs/plans/2025-11-12-code-compliance.md) - Current implementation roadmap

## Development Setup

### Prerequisites

- PHP 8.4+
- Node.js 20+
- Composer
- npm
- SQLite (for local development)
- Xdebug (for test coverage)

### Initial Setup

```bash
# 1. Fork and clone the repository
git clone https://github.com/YOUR_USERNAME/joowp.git
cd joowp

# 2. Install PHP dependencies
composer install

# 3. Install frontend dependencies
npm install

# 4. Setup environment
cp .env.example .env
php artisan key:generate

# 5. Run migrations
php artisan migrate

# 6. Start development server
composer dev  # Runs server + queue + logs + vite in one command
```

## Development Workflow

### 1. Create a Feature Branch

```bash
git checkout -b feat/your-feature-name
# or
git checkout -b fix/bug-description
```

Branch naming convention:
- `feat/` - New features
- `fix/` - Bug fixes
- `refactor/` - Code refactoring
- `test/` - Adding/updating tests
- `docs/` - Documentation changes
- `chore/` - Tooling/config changes

### 2. Make Your Changes

**Follow these principles:**
- `declare(strict_types=1)` in ALL PHP files (mandatory)
- Use `final` classes by default
- Type all parameters and return values
- TypeScript strict mode for frontend
- Write tests for all new code

See [Engineering Principles](docs/principles.md) for complete requirements and [AI Coding Instructions](.github/copilot-instructions.md) for code examples.

### 3. Write Tests

**Every new class MUST have unit tests** - no exceptions.

**Coverage targets (enforced by CI):**
- Overall project: 80% minimum
- Core module services: 95%
- API controllers: 90%
- FormRequests: 100%
- Models: 85%

See [AI Coding Instructions](.github/copilot-instructions.md) for test examples and patterns.

### 4. Run Quality Checks

**Before committing, run ALL quality gates:**

```bash
# 1. Auto-fix style issues
composer lint:pint

# 2. Validate PSR-12 compliance
composer lint:phpcs

# 3. Check design quality
composer analyze:phpmd

# 4. Static analysis
composer analyze:phpstan

# 5. Run tests with coverage
composer test:coverage-check  # Must be ≥ 80%

# 6. TypeScript validation (if frontend changes)
npm run typecheck

# 7. Build verification
npm run build
```

**Or run all at once:**
```bash
composer lint && composer test:coverage-check && npm run typecheck && npm run build
```

### 5. Commit Your Changes

**Follow commit guidelines strictly:**

```bash
# 1. Review changes
git status
git diff

# 2. Stage ONLY files you modified
git add path/to/file1.php path/to/file2.php

# 3. Commit with proper format
git commit -m "type: description

Optional detailed explanation"
```

**Commit message format:**
```
<type>: <description>

[optional body]
[optional footer]
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation only
- `test`: Adding/updating tests
- `refactor`: Code change (no new features/fixes)
- `style`: Formatting, no logic change
- `chore`: Build/tooling/config changes

**Examples:**
```bash
git commit -m "feat: add Category CRUD endpoints with FormRequest validation"
git commit -m "test: add UserService unit tests (95% coverage)"
git commit -m "fix: add missing declare(strict_types=1) to User.php"
git commit -m "docs: update coverage requirements in principles.md"
```

**Commit scope guidelines:**
- **1-5 files:** Usually appropriate
- **5-15 files:** Acceptable if same feature (Controller + Service + Tests)
- **15-30 files:** Justify in commit message
- **>30 files:** Split into multiple commits

**Rule:** "Could this commit be reverted independently?" → Yes = good boundary

### 6. Push and Create Pull Request

```bash
# Push your branch
git push origin feat/your-feature-name

# Create PR on GitHub
# PR will automatically run CI/CD quality gates
```

## Quality Gates (MANDATORY)

All pull requests must pass these automated checks:

### PHP Quality Pipeline
1. ✅ **Laravel Pint** - Code style (auto-fixes)
2. ✅ **PHP_CodeSniffer** - PSR-12 validation
3. ✅ **PHPMD** - Design quality (SOLID principles)
4. ✅ **PHPStan** - Static analysis (maximum level)

### Testing Requirements
5. ✅ **PHPUnit** - All tests must pass
6. ✅ **Coverage** - Minimum 80% (enforced)

### Frontend Requirements
7. ✅ **TypeScript** - Strict mode validation
8. ✅ **Build** - Production build succeeds

### Code Standards
9. ✅ **Strict Types** - All PHP files have `declare(strict_types=1)`
10. ✅ **Final Classes** - Classes are `final` by default

**If any gate fails, the PR cannot be merged.**

## Pre-commit Hook

The repository includes a pre-commit hook that runs quality checks automatically:

```bash
# Install the hook (first time only)
cp hooks/pre-commit.sample .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
```

The hook will:
- Check for `declare(strict_types=1)` in PHP files
- Auto-run quality pipeline
- Enforce test passage
- Validate TypeScript (if applicable)
- Block commit if any check fails

**Bypassing Hooks:**

**Pre-commit Hook (emergency only):**
```bash
git commit --no-verify -m "message"
```
**WARNING:** Only use `--no-verify` for emergency situations. All bypassed commits must be fixed in follow-up commits.

**Commit-msg Hook (Metadata Validation):**
❌ **NO BYPASS ALLOWED** - Metadata validation **CANNOT be bypassed**. All commits must include metadata block.

**Technical Note:** `--no-verify` only bypasses `pre-commit` hook, NOT `commit-msg` hook (Git behavior).

**If you need help:**
- Use helper script: `./scripts/git-commit-template.sh`
- See format: `docs/reference/standards.md#commit-message-metadata`

## Common Tasks

### Creating a New Module

```bash
php artisan module:make YourModule
```

Then enable and configure the module. See [AI Coding Instructions](.github/copilot-instructions.md#creating-a-new-module) for detailed steps.

### Adding a New API Endpoint

**High-level steps:**
1. Create FormRequest for validation
2. Create Service for business logic
3. Create Controller (inject Service)
4. Register route in `routes/api.php`
5. Add audit logging for mutations
6. Write unit + feature tests

See [AI Coding Instructions](.github/copilot-instructions.md#creating-a-new-api-endpoint) for complete implementation guide with code examples.

### WordPress Integration

Always proxy through Laravel - never call WordPress from frontend.

See [WordPress SDK Guide](docs/guides/core-wordpress-sdk.md) for integration patterns.

## Pull Request Guidelines

### PR Title Format
```
<type>: <description>
```

Examples:
- `feat: implement Category management CRUD endpoints`
- `fix: resolve strict_types violations in User model`
- `test: increase coverage to 85% for Core services`

### PR Description Template

```markdown
## Description
Brief summary of changes.

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Unit tests added/updated
- [ ] Feature tests added/updated
- [ ] Manual testing performed
- [ ] Coverage ≥ 80%

## Quality Checklist
- [ ] `composer lint` passes
- [ ] `composer test:coverage-check` passes
- [ ] `npm run typecheck` passes
- [ ] All PHP files have `declare(strict_types=1)`
- [ ] Classes are `final` (unless inheritance required)
- [ ] Audit logging added for mutations

## Related Issues
Closes #123
```

## Code Review Process

### For Contributors
- Respond to review comments promptly
- Make requested changes in separate commits
- Re-request review after addressing feedback
- Squash commits before merge (if requested)

### For Reviewers
- Review within 48 hours
- Check adherence to principles
- Verify test coverage
- Ensure CI passes
- Approve only if all gates pass

## Getting Help

- **Questions:** Open a GitHub Discussion
- **Bugs:** Open a GitHub Issue with reproduction steps
- **Features:** Open a GitHub Issue with use case description
- **Documentation:** Check `docs/` directory first

## Code of Conduct

- Be respectful and constructive
- Focus on code quality, not personal preferences
- Follow documented principles and patterns
- Help others learn and improve

## License

By contributing, you agree that your contributions will be licensed under the same license as the project (MIT).

## Additional Resources

- [Engineering Principles](docs/principles.md)
- [Code Quality Workflow](docs/code-quality.md)
- [WordPress SDK Guide](docs/guides/core-wordpress-sdk.md)
- [Implementation Plans](docs/plans/)

Thank you for contributing to JOOservices Platform! 🚀
