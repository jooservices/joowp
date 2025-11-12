# Plan – WordPress Module Extraction

Status: Active  
Owner: TBD  
Created: 2025-11-12  
Updated: 2025-11-12

## Summary
- Stand up a dedicated `WordPress` Laravel module and relocate all WordPress-specific implementation (except the reusable SDK) out of `Core`.

## Objectives
- Create a first-class `WordPress` module following `nwidart/laravel-modules` conventions.
- Move controllers, requests, models, migrations, seeders, routes, config, and tests that are WordPress-specific into the new module.
- Keep the shared SDK in `Modules/Core` while ensuring dependency injection and service providers continue to function.
- Preserve existing API behaviour, logging, and testing coverage after the relocation.
- Update documentation and architectural diagrams to reflect the new module boundaries.

## Tasks
- [ ] Review current WordPress-related assets in `Modules/Core` (controllers, routes, requests, models, migrations, seeders, tests, views, configs).
- [ ] Scaffold `WordPress` module structure (config, service provider, routes, resources, database, tests directories).
- [ ] Move API routes and controllers into the new module and adjust namespaces/imports.
- [ ] Relocate form requests, resources, policies (if any), and update bindings/references.
- [ ] Relocate `WpToken` model, migrations, factories/seeders, and ensure module autoloading.
- [ ] Update service providers and container bindings to register the SDK contract pointing to `Modules/Core` implementation.
- [ ] Update tests to reference the new module namespaces and ensure coverage remains intact.
- [ ] Run full quality gates (Pint, PHPCS, PHPMD, PHPStan, PHPUnit, typecheck) to confirm zero regressions.
- [ ] Refresh documentation (`docs/guides`, `docs/principles`, relevant plans) to describe the new module responsibilities and interactions.

## Notes
- Audit existing documentation (principles, code quality, guides) before implementation to stay aligned with established rules.
- SDK remains inside `Modules/Core/Services/WordPress`; other WordPress-specific layers move to the new module.
- Ensure the new module is discoverable in `composer.json` autoload and `modules_statuses.json` as needed.
