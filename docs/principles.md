# Project Principles

This document consolidates the core requirements and working agreements for the project. Every new contribution must respect these principles without exception.

## Platform
- Base stack: Laravel 12.
- Runtime: PHP 8.4 (minimum); avoid features incompatible with 8.4 and prefer 8.4 language improvements where appropriate.
- Frontend stack: Vue 3 powered by Vite; all UI work must mount through Vue entry points implemented with **TypeScript-only** code (no plain JavaScript).
- UI toolkit: Latest Bootstrap and Font Awesome; centralize styling utilities and icons with these libraries.
- Architecture must be strictly object-oriented and adhere to SOLID principles at all levels (services, controllers, console commands, etc.).
- Adopt a **dark theme aesthetic** across all user-facing surfaces and shared assets.
- Client applications never call third-party services directly; every request must hit our Laravel API, which then orchestrates any downstream integrations.

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
- **Always** run the full automated test suite (type checks, unit, feature, and integration tests) before declaring a task finished or creating a commit; “tests green” is a non-negotiable gate for every change.
- **Always run the full automated test suite (type checks + PHP tests) before marking a task complete or creating a commit.** No exceptions—“tests pass” is a gate for every change.

## Working Agreements
- Always write code, comments, commit messages, and documentation in English, regardless of communication language.
- Every new feature must be accompanied by an updated or new guide under `/docs`; never ship functionality without documentation.
- Review all documentation under `/docs` before starting any task.
- Treat the [WordPress REST API glossary](https://developer.wordpress.org/rest-api/glossary/) as mandatory reading and the canonical source of terminology for our API work; new endpoints, resources, and docs must align with its definitions.
- Do not commit or merge changes until they have been explicitly reviewed and approved by the project owner (ask for approval before finalising any commit).
- Always use descriptive, meaningful commit messages; commits should clearly communicate the change set without needing to read the diff.
- Always capture and persist structured logs (request metadata, sanitized payload, response summary) for every third-party API integration triggered by the platform.
- Follow Laravel’s official standards and conventions rigorously; align new code with the latest framework guidance and best practices.
- Favor design patterns that reinforce SOLID (e.g., interfaces, dependency inversion, factories, strategy objects).
- Avoid tight coupling to framework facades inside domain logic; prefer constructor-injected abstractions.
- Keep modules independent by communicating only through explicit contracts or shared services published by `Core`.
- Any asynchronous UI interaction (AJAX/SPA request) must indicate loading by disabling relevant controls, showing a spinner, and dimming the context to prevent duplicate submissions.
- Fatal errors surfaced to users must render as sticky toast notifications (fixed position, dismissible by the user). Non-fatal notices may auto-dismiss after a short delay but must still use the toast pattern for consistency.
- Binary user interactions (on/off choices) must use switch-style controls for clarity; avoid plain checkboxes for SPA toggles.
