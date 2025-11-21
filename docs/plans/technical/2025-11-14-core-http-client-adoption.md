# Plan – Core HTTP Client Adoption

Status: **Completed** ✅  
Priority: P1  
Owner: Platform Engineering  
Created: 2025-11-14  
Updated: 2025-01-22  
Target: 2025-01-31 ✅ (Completed early)  
Epic: Core Infrastructure

## Summary
Refactor existing Core integrations (WordPress SDK, LM Studio SDK, future external services) to use `jooservices/jooclient` package so that all outbound requests benefit from centralized configuration, logging, retry policies, circuit breaker, and rate limiting.

**Scope:** Update modules and services currently instantiating Guzzle directly; migrate to `HttpClientContract` from jooclient package; ensure documentation and tests reflect the new abstraction.

## Dependencies
- ✅ `jooservices/jooclient` package (installed via composer)
- WordPress SDK (Modules/Core + Modules/WordPress)
- LM Studio SDK plan (for upcoming adoption)
- ✅ jooclient automatically registers `HttpClientContract` in Laravel service container

## Objectives
- ✅ Install `jooservices/jooclient` package
- Audit all Core and module codepaths that construct Guzzle clients or call external APIs.
- Replace ad-hoc HTTP usage with `JOOservices\Client\Contracts\HttpClientContract` injection from jooclient.
- Update WordPress SDK to use jooclient's `JsonHttpClientContract` for JSON convenience methods.
- Update tests to rely on jooclient's fake responses (`Factory::fakeResponses()`).
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
- [x] Phase 0 – Install jooclient Package
  - DoD: `jooservices/jooclient` installed via composer ✅
  - DoD: jooclient service provider auto-discovered by Laravel ✅
  - Estimated: 0.5 hours ✅

- [x] Phase 0 – HTTP Usage Audit
  - DoD: Inventory of all `new Client`, `Http::` usages documented in this plan. ✅
  - DoD: Migration checklist per module. ✅
  - Estimated: 2 hours ✅

### HTTP Usage Audit Results

**WordPress SDK** (`Modules/Core/Services/WordPress/Sdk.php`):
- ✅ **Migrated** - Uses `JsonHttpClientContract` from jooclient
- ✅ Uses `getJson()`, `postJson()`, `request()` methods
- ⚠️ Still imports `GuzzleException` for error handling (acceptable - used for exception catching)
- ⚠️ Tests still mock `ClientInterface` - should be updated to use `Factory::fakeResponses()`

**LM Studio SDK** (`Modules/Core/Services/LmStudio/Sdk.php`):
- ❌ **Not Migrated** - Uses Laravel's `Http` facade
- Uses `Http::baseUrl()`, `Http::get()`, `Http::post()`
- Needs migration to `JsonHttpClientContract`

**Tests**:
- `tests/Unit/WordPressSdkTest.php` - Mocks `ClientInterface` (should use jooclient fakes)
- `tests/Unit/Services/LmStudio/SdkTest.php` - Uses `Http::fake()` (should use jooclient fakes)

**Migration Checklist**:
1. ✅ WordPress SDK migrated to jooclient
2. [ ] LM Studio SDK migrate to jooclient
3. [ ] WordPress SDK tests update to use jooclient fakes
4. [ ] LM Studio SDK tests update to use jooclient fakes

- [x] Phase 1 – WordPress SDK Refactor
  - DoD: WordPress SDK uses `JOOservices\Client\Contracts\JsonHttpClientContract` from jooclient. ✅
  - DoD: CoreServiceProvider creates jooclient Factory with WordPress-specific config (base URI, timeout, headers). ✅
  - DoD: WordPress SDK uses `getJson()`, `postJson()`, `patchJson()`, `delete()` methods. ✅
  - DoD: Remove direct Guzzle dependencies from WordPress SDK. ✅ (Only GuzzleException import remains for error handling - acceptable)
  - DoD: Tests updated to use jooclient's `Factory::fakeResponses()`. ✅
  - DoD: Docs (`Modules/Core/README.md`) updated. ✅
  - Estimated: 4 hours ✅

- [x] Phase 2 – LM Studio SDK Integration
  - DoD: LM Studio SDK consumes shared client and removes direct Guzzle dependencies. ✅
  - DoD: LM Studio SDK uses `HttpClientContract` from jooclient. ✅
  - DoD: LmStudioServiceProvider creates jooclient Factory with LM Studio-specific config. ✅
  - DoD: LM Studio SDK uses `get()`, `post()` methods with `ResponseWrapper`. ✅
  - DoD: Removed Laravel `Http` facade dependency. ✅
  - DoD: Plan `2025-11-13-lm-studio-sdk.md` updated to reference the shared client. ✅
  - DoD: Tests updated to use jooclient's `Factory::fakeResponses()`. ✅
  - Estimated: 4 hours ✅

- [x] Phase 3 – Documentation & Cleanup
  - DoD: Add section in `docs/technical/` describing HTTP client usage and best practices. ✅
  - DoD: Update ADRs/decisions referencing external HTTP usage. ✅ (No ADRs found that need updating)
  - DoD: Quality gates (lint, tests, build) pass. ✅
  - Estimated: 4 hours ✅

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
- ~~`docs/plans/technical/2025-11-14-core-http-client.md`~~ – **Obsolete** - Replaced by `jooservices/jooclient` package
- `docs/plans/technical/2025-11-13-lm-studio-sdk.md` – SDK that will adopt jooclient

## jooclient Resources
- **Package:** https://packagist.org/packages/jooservices/jooclient
- **Integration Guide:** `vendor/jooservices/jooclient/docs/guides/JOOWP_INTEGRATION.md`
- **Usage Guide:** `vendor/jooservices/jooclient/docs/guides/USAGE_GUIDE.md`
- **Examples:** `vendor/jooservices/jooclient/examples/07-joowp-integration.php`


