# Core Module

## Overview

The `Core` module owns cross-cutting services that power the rest of JOOwp. Today it delivers:

- Shared WordPress integration primitives (HTTP clients, contracts, token resolver).
- Action logging helpers for CRUD auditing.
- Base service providers/config wiring consumed by feature modules (for example, the WordPress module).

## WordPress integration

- `Services/WordPress/Sdk.php` wraps the WordPress REST API using `jooservices/jooclient` package and exposes helpers for posts, taxonomies, and JWT token exchange.
- The SDK uses `JOOservices\Client\Contracts\JsonHttpClientContract` from jooclient for optimized JSON operations (`getJson()`, `postJson()`, etc.).
- The SDK is registered in `app/Providers/CoreServiceProvider.php` and bound to the `Modules\Core\Services\WordPress\Contracts\SdkContract` interface.
- All outbound calls benefit from jooclient's features:
  - **Automatic retry logic** (3 retries with exponential backoff for 5xx errors)
  - **Built-in logging** (configurable via jooclient config)
  - **Data sanitization** (GDPR & PCI-DSS compliant)
  - **Circuit breaker** and **rate limiting** support
- Custom logging via `external` log channel masks sensitive payloads (passwords, JWTs).
- The `TokenResolver` automatically attaches stored JWT tokens (now saved by the WordPress module) to outbound requests when available.
- SDK configuration now lives under `config('wordpress.api')`, keeping Core free of feature-specific settings while still supporting the historical `core.wordpress` keys for backward compatibility.

## WordPress module hand-off

- All WordPress-specific HTTP layers (controllers, routes, requests, models, migrations, and tests) now live in `Modules/WordPress`. The module consumes the SDK through dependency injection and persists remembered tokens via `Modules\WordPress\Models\WpToken`.
- `/api/v1/wordpress/*` endpoints are registered by the WordPress module, preserving the existing URIs and route names (`api.core.wordpress.*`).
- Feature UIs such as `resources/js/Pages/Taxonomy/Categories/Index.vue` continue to call the same endpoints; no SPA changes were required for the extraction.

## Testing

Run the full suite before shipping changes:

```bash
composer lint:phpcs
composer analyze:phpmd
composer analyze:phpstan
npm run typecheck
php artisan test
```

All new services and form requests must include unit coverage; API workflows require feature tests.

## Documentation

- **HTTP Client (jooclient)**: 
  - Integration guide: `vendor/jooservices/jooclient/docs/guides/JOOWP_INTEGRATION.md`
  - Usage guide: `vendor/jooservices/jooclient/docs/guides/USAGE_GUIDE.md`
  - Package: https://packagist.org/packages/jooservices/jooclient
- **Core HTTP Client Adoption Plan**: `docs/plans/technical/2025-11-14-core-http-client-adoption.md`
- Taxonomy plan: `docs/plans/technical/` (check for category-related plans)
- WordPress SDK: See `ai-workflow/guides/restful-api-design.md` for REST API patterns
- Master documentation: `ai-workflow/README.md`
- Project-specific docs: `docs/README.md`
