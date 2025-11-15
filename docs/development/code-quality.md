# Code Quality Workflow

This project enforces a layered quality pipeline where **Laravel Pint** defines the canonical coding style and PSR-12 compliance baseline. Additional static analysis tools must respect Pint's formatting choices and focus on structural or logical issues.

## Tooling Stack

- **Laravel Pint** (`composer lint:pint`): primary style fixer using the `laravel` preset with project-specific rules in `pint.json`.
- **PHP_CodeSniffer** (`composer lint:phpcs`): validates PSR-12 adherence against `phpcs.xml`, aligned with Pint's formatting to catch deviations without conflicting on auto-formatting.
- **PHPMD** (`composer analyze:phpmd`): enforces SOLID-friendly design and cleanliness rules defined in `phpmd.xml`.
- **PHPStan** (`composer analyze:phpstan`): performs maximum-level static analysis using `phpstan.neon.dist`.

Run the full quality pipeline with:

```bash
composer lint
```

This executes the tools in priority order (Pint → PHP_CodeSniffer → PHPMD → PHPStan). Fix style findings with Pint first, then address any remaining issues reported by the other analyzers. Commits must not be merged unless this pipeline is green at the default settings.

## Non-Negotiable Rules

- **Type safety everywhere**: declare parameter and return types on all methods. If an integration point forces `mixed`, leave an inline justification (comment + ticket) explaining why.
- **Static analysis suppressions need context**: if you must mute PHPStan/PHPMD, add a nearby comment referencing the decision record or issue link so reviewers understand the trade-off.

## Update: Commit Message Standards and AI Workflow

The sections related to commit message standards and AI-specific workflows have been moved to their respective documents:

1. **Commit Message Standards**:
   - Now located in `docs/guides/guidelines.md`.
   - Refer to this document for detailed rules and examples.

2. **AI Commit Execution Workflow**:
   - Now located in `docs/ai-workflow.md`.
   - This section applies to all AI agents and outlines the rules for commit execution.

This document will continue to focus solely on code quality tools and processes.

