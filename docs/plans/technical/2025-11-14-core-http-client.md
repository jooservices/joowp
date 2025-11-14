# Plan – Core HTTP Client Abstraction

Status: Active  
Priority: P1  
Owner: Platform Engineering  
Created: 2025-11-14  
Updated: 2025-11-14  
Target: 2025-11-21  
Epic: Core Infrastructure

## Summary
Introduce a reusable HTTP client in `Modules/Core` that wraps Guzzle with platform-standard configuration, logging, retries, and security guards so any module can consume external APIs through a consistent abstraction.

**Scope:** Core module infrastructure only (contracts, implementation, config, tests, docs). Does not include specific service integrations (e.g. LM Studio, WordPress) beyond updating them to use the new client once available.

## Dependencies
- `guzzlehttp/guzzle` (already required by the project)
- Existing logging infrastructure (`App\Logging\ActionLogger`, `external` log channel)
- Configuration pipeline (`config/` + env variables)

## Objectives
- Provide a type-safe Core HTTP client contract and implementation wrapping Guzzle with sane defaults (timeouts, retries, TLS verification).
- Centralize logging, metric hooks, request/response sanitization, and error mapping for all outbound HTTP calls.
- Offer convenience helpers for common patterns (JSON requests, form-data, streaming, SSE).
- Ensure strict test coverage (unit + integration with fakes) and documentation for developer usage.

## Milestones
- **Phase 0 – Design & Requirements**
  - Document client responsibilities, configuration options, and integration touchpoints.
  - Align with existing principles (fail fast, audit logging, defensive programming).

- **Phase 1 – Core Client Implementation**
  - Scaffold contract, concrete class, request/response DTOs, and config file.
  - Implement middleware stack (retry, logging, metrics, auth header injection).

- **Phase 2 – Testing & Documentation**
  - Add unit and integration tests with HTTP fakes.
  - Document usage guidelines, config reference, and migration steps for modules.

## Tasks
- [ ] Phase 0 – Requirements Capture
  - DoD: Decision record in `docs/decisions/` summarizing responsibilities, constraints, and adoption plan.
  - DoD: Config schema drafted (`config/http.php` outline with env variables).
  - Estimated: 4 hours

- [ ] Phase 1 – Implementation
  - DoD: `Modules/Core/Services/Http/Contracts/HttpClientContract.php` defining typed methods (`request`, `getJson`, `postJson`, `stream`, etc.).
  - DoD: `Modules/Core/Services/Http/HttpClient.php` wrapping Guzzle with middleware (retry, logging, metrics, auth, headers).
  - DoD: `config/http.php` with defaults (`base_uri`, `timeout`, `retries`, `verify`, logging toggles).
  - DoD: Service provider binds contract to implementation and publishes config.
  - Estimated: 8 hours

- [ ] Phase 1 – Middleware & Features
  - DoD: Retry/backoff strategy configurable via config.
  - DoD: Request/response logging sanitized for sensitive data.
  - DoD: Metrics hooks emitting duration/status for observability.
  - DoD: Support for streaming (SSE) and file uploads.
  - Estimated: 6 hours

- [ ] Phase 2 – Testing & Documentation
  - DoD: PHPUnit unit tests covering contract methods, retry logic, error mapping.
  - DoD: Integration tests using `Http::fake()` or Guzzle mock handler.
  - DoD: Developer guide in `docs/technical/` explaining usage, config, and migration steps.
  - DoD: Update relevant plans/modules to reference the new client.
  - Estimated: 6 hours

## Success Metrics
- 100% of new external HTTP integrations use `HttpClientContract`.
- Core HTTP client logic achieves ≥95% PHPUnit coverage.
- All quality gates (`composer lint`, `composer test:coverage-check`, `npm run typecheck`, `npm run build`) pass after implementation.
- Documentation provides clear instructions for other modules to adopt the client.

## Risks & Mitigations
| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Misconfigured retries causing duplicate requests | Medium | Medium | Expose conservative defaults, document idempotency requirements |
| Logging sensitive data | High | Low | Implement sanitizers + allowlist fields before logging |
| Existing modules not migrated | Medium | Medium | Provide migration guide and update key modules as follow-up tasks |
| Performance overhead from middleware | Low | Medium | Allow selective toggling of heavy middleware (metrics/logging) |

## Notes
- This plan is independent of the LM Studio SDK work but will be a prerequisite for future SDK integrations.
- Coordinate with DevOps for observability requirements (e.g., pushing metrics to Prometheus).
- Consider adding an “auth providers” registry for per-service credentials if needed later.

## Related Plans
- `docs/plans/technical/2025-11-14-core-http-client-adoption.md` – Refactor existing integrations to use the new client.

