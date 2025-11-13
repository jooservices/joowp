# Plan – WordPress Module Extraction

Status: Completed  
Priority: P1  
Owner: Platform Engineering  
Created: 2025-11-12  
Updated: 2025-11-12  
Target: 2025-11-12  
Epic: Modular Architecture

## Summary
Extract WordPress-specific functionality from Core module into dedicated WordPress module following nwidart/laravel-modules conventions. Improves separation of concerns and prepares for additional domain modules (AI, Product, etc.).

**Scope:** WordPress controllers, models, migrations, routes, and configuration only. Core SDK remains shared for reusability across modules.

## Dependencies
- nwidart/laravel-modules package (already installed)
- Existing Core module WordPress functionality
- WordPress SDK must remain in Core for shared access

## Objectives
- Achieve clean domain separation with WordPress business logic in dedicated module
- Maintain 100% API backward compatibility during extraction
- Preserve all existing test coverage and quality gates
- Enable future domain modules to follow same pattern

## Tasks
- [x] Audit existing WordPress assets in Core module
  - DoD: Complete inventory of controllers, routes, requests, models, migrations, tests
  - DoD: Identify shared vs WordPress-specific components
  - DoD: Plan extraction without breaking existing integrations
  - Estimated: 2 hours

- [x] Create WordPress module scaffold  
  - DoD: Module structure follows nwidart conventions (config, providers, routes, resources)
  - DoD: Service provider properly registers module services
  - DoD: Module enabled in modules_statuses.json
  - DoD: Autoloading configured in composer.json
  - Estimated: 1 hour

- [x] Migrate WordPress-specific routes and controllers
  - DoD: All WordPress API routes moved to WordPress module
  - DoD: Controllers updated with proper namespacing
  - DoD: Route registration works through WordPress service provider
  - DoD: API endpoints remain accessible at same URLs
  - Estimated: 3 hours

- [x] Relocate FormRequests and Resources
  - DoD: All WordPress validation moved to WordPress module
  - DoD: API Resources for WordPress entities properly namespaced  
  - DoD: Container bindings updated for new locations
  - DoD: Validation rules remain identical
  - Estimated: 2 hours

- [x] Move WpToken model and related database assets
  - DoD: WpToken model moved to WordPress module
  - DoD: Migrations relocated with proper dependencies
  - DoD: Seeders and factories updated
  - DoD: Database relationships still function correctly
  - Estimated: 2 hours

- [x] Update service providers and container bindings
  - DoD: WordPress service provider registers all module services
  - DoD: SDK contract binding points to Core implementation
  - DoD: Token resolver points to WordPress WpToken model
  - DoD: Configuration properly merged from WordPress module
  - Estimated: 3 hours

- [x] Migrate and update test coverage
  - DoD: All WordPress tests moved to WordPress module test directory
  - DoD: Test namespacing updated for new module structure
  - DoD: Coverage reports show same or improved coverage percentages
  - DoD: Both unit and feature tests pass completely
  - Estimated: 4 hours

- [x] Run complete quality validation
  - DoD: Pint, PHPCS, PHPMD, PHPStan all pass without violations
  - DoD: PHPUnit test suite passes with 100% success rate
  - DoD: npm run typecheck passes for frontend components
  - DoD: No regressions in API functionality or response format
  - Estimated: 2 hours

- [x] Update documentation and architectural diagrams
  - DoD: Module responsibilities documented in guides directory
  - DoD: Service provider patterns updated in principles
  - DoD: API documentation reflects new module organization
  - DoD: README files updated for new structure
  - Estimated: 2 hours

**Total Estimated Effort:** 21 hours (~3 days for 1 developer)

## Success Metrics
- **API Compatibility:** 100% backward compatibility maintained (no breaking changes)
- **Test Coverage:** Same or improved coverage percentages post-migration  
- **Quality Gates:** All quality tools pass without violations
- **Module Isolation:** WordPress module can be disabled without affecting Core functionality
- **Documentation:** Complete module extraction documented for future reference

## Risks & Mitigations
| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Breaking API compatibility | High | Low | Comprehensive API testing before/after migration |
| Service provider conflicts | Medium | Low | Careful service binding and namespace management |
| Test coverage loss | Medium | Low | Move tests with code, verify coverage reports |
| Documentation drift | Low | Medium | Update docs as part of migration tasks |

## Related Plans
- `docs/architecture/principles.md#modular-architecture` - Domain-driven modularity principle (#5)
- Future: AI module extraction following same pattern
- Future: Product module creation

## Notes
- SDK remains in Core module at `Modules/Core/Services/WordPress` for shared access
- Configuration moved to `config('wordpress.api')` with Core fallbacks maintained
- Module discovery and autoloading handled by existing composer.json patterns
- Sets precedent for future domain module extractions (AI, Product, etc.)
- All WordPress-specific business logic now properly isolated from Core infrastructure
