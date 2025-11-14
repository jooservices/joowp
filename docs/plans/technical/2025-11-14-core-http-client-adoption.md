# Plan – Core HTTP Client Adoption

Status: Active  
Priority: P1  
Owner: Platform Engineering  
Created: 2025-11-14  
Updated: 2025-11-14  
Target: 2025-11-28  
Epic: Core Infrastructure

## Summary
Refactor existing Core integrations (WordPress SDK, LM Studio SDK, future external services) to use the new shared HTTP client so that all outbound requests benefit from centralized configuration, logging, and retry policies.

**Scope:** Update modules and services currently instantiating Guzzle or Laravel HTTP client directly; ensure documentation and tests reflect the new abstraction.

## Dependencies
- `docs/plans/technical/2025-11-14-core-http-client.md` (Core HTTP client must be implemented first)
- WordPress SDK (Modules/Core + Modules/WordPress)
- LM Studio SDK plan (for upcoming adoption)

## Objectives
- Audit all Core and module codepaths that construct Guzzle clients or call external APIs.
- Replace ad-hoc HTTP usage with `HttpClientContract` injection and consistent error handling.
- Update tests to rely on the shared client’s fakes/mocks.
- Ensure documentation (guides, plans, ADRs) reflects the migration.

## Milestones
- **Phase 0 – Audit & Migration Plan**
  - Identify all HTTP entry points and categorize by module.
  - Produce migration checklist per service.

- **Phase 1 – WordPress SDK Migration**
  - Swap WordPress SDK to consume `HttpClientContract`.
  - Adjust logging/token handling to rely on shared middleware.

- **Phase 2 – LM Studio SDK & Future Integrations**
  - Ensure LM Studio SDK (once implemented) consumes the shared client.
  - Provide template guidance for new SDKs/modules.

- **Phase 3 – Validation & Documentation**
  - Verify tests, quality gates, and docs updates.
  - Announce adoption guidelines in changelog/README.

## Tasks
- [ ] Phase 0 – HTTP Usage Audit
  - DoD: Inventory of all `new Client`, `Http::` usages documented in this plan.
  - DoD: Migration checklist per module.
  - Estimated: 4 hours

- [ ] Phase 1 – WordPress SDK Refactor
  - DoD: Core WordPress SDK uses `HttpClientContract` exclusively.
  - DoD: Tests updated to mock shared client.
  - DoD: Docs (`guides/core-wordpress-sdk.md`) updated.
  - Estimated: 6 hours

- [ ] Phase 2 – LM Studio SDK Integration
  - DoD: LM Studio SDK consumes shared client and removes direct Guzzle dependencies.
  - DoD: Plan `2025-11-13-lm-studio-sdk.md` updated to reference the shared client.
  - Estimated: 4 hours

- [ ] Phase 3 – Documentation & Cleanup
  - DoD: Add section in `docs/technical/` describing HTTP client usage and best practices.
  - DoD: Update ADRs/decisions referencing external HTTP usage.
  - DoD: Quality gates (lint, tests, build) pass.
  - Estimated: 4 hours

## Success Metrics
- No remaining direct `GuzzleHttp\Client` instantiations outside the shared client.
- All SDK/service tests use shared client fakes/mocks.
- Documentation clearly instructs developers to depend on `HttpClientContract`.

## Risks & Mitigations
| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Regression in existing SDKs during refactor | High | Medium | Add integration tests and migrate one module at a time |
| Modules bypass shared client | Medium | Medium | Enforce via code review and static analysis checks |
| Timeline overlap with LM Studio SDK implementation | Medium | Medium | Coordinate milestones; ensure client is ready before adoption |

## Related Plans
- `docs/plans/technical/2025-11-14-core-http-client.md` – foundational client implementation.
- `docs/plans/technical/2025-11-13-lm-studio-sdk.md` – SDK that will adopt the shared client.

