# Project Principles

This document consolidates the core requirements and working agreements for the project. Every new contribution must respect these principles without exception.

## Platform
- Base stack: Laravel 12.
- Runtime: PHP 8.4 (minimum); avoid features incompatible with 8.4 and prefer 8.4 language improvements where appropriate.
- Frontend stack: Vue 3 powered by Vite; all UI work must mount through Vue entry points implemented with **TypeScript-only** code (no plain JavaScript).
- UI toolkit: Latest Bootstrap and Font Awesome; centralize styling utilities and icons with these libraries.
- Architecture must be strictly object-oriented and adhere to SOLID principles at all levels (services, controllers, console commands, etc.).
- Adopt a **dark theme aesthetic** across all user-facing surfaces and shared assets.

## Modular Structure
- Use `nwidart/laravel-modules` to encapsulate business domains.
- Each distinct business area must live in its own module; scope each module to a single, cohesive business capability.
- The `Core` module supplies cross-cutting services (e.g., integrations, SDKs, shared utilities) for other modules to consume—never duplicate shared logic elsewhere.
- Modules collaborate only through well-defined contracts or Core-provided services; avoid leaking internal module details.

## Code Quality Workflow
- Laravel Pint with the `laravel` preset (see `pint.json`) defines the canonical coding standard and must be run first.
- Supporting tools must align with Pint’s formatting:
  - PHP_CodeSniffer (`composer lint:phpcs`) using `phpcs.xml`.
  - PHPMD (`composer analyze:phpmd`) using `phpmd.xml`.
  - PHPStan (`composer analyze:phpstan`) using `phpstan.neon.dist` at maximum level.
- Run the full pipeline with `composer lint` before proposing changes; resolve issues in the order Pint → PHPCS → PHPMD → PHPStan.
- Mandatory type safety: every method must declare concrete parameter and return types. `mixed` is forbidden unless a documented edge case (inline comment + ticket reference) makes it unavoidable.
- Static analysis gates: no merge if PHPStan or PHPMD reports errors. Any suppression must be accompanied by a code comment explaining the rationale and referencing the tracking issue or decision record.

## Testing
- Golden path coverage: each module must ship at least one integration test that exercises its primary business workflow end-to-end.
- **Unit coverage is mandatory**: every class, feature, and function must have accompanying unit tests—no exceptions or deferrals.
- Prefer Laravel’s testing helpers and real collaborators. Mocks and fakes are allowed only for narrowly scoped scenarios (e.g., unavoidable external integrations) and must be justified on a case-by-case basis.
- Every mutation (CRUD or equivalent state change) must emit a structured audit log capturing actor identity, timestamp, fields changed, and the before & after payload. This requirement is non-negotiable and must be enforced in implementation and code review.

## Working Agreements
- Always write code, comments, commit messages, and documentation in English, regardless of communication language.
- Every new feature must be accompanied by an updated or new guide under `/docs`; never ship functionality without documentation.
- Review all documentation under `/docs` before starting any task.
- Follow Laravel’s official standards and conventions rigorously; align new code with the latest framework guidance and best practices.
- Favor design patterns that reinforce SOLID (e.g., interfaces, dependency inversion, factories, strategy objects).
- Avoid tight coupling to framework facades inside domain logic; prefer constructor-injected abstractions.
- Keep modules independent by communicating only through explicit contracts or shared services published by `Core`.

