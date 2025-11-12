# Core Module

## Overview

The `Core` module owns cross-cutting services that power the rest of JOOwp. Today it delivers:

- Shared WordPress integration primitives (HTTP clients, contracts, token resolver).
- Action logging helpers for CRUD auditing.
- Taxonomy APIs (categories for now; tags to follow the existing plan).
- Seeders for bootstrap users and remembered WordPress tokens.

## WordPress integration

- `Services/WordPress/Sdk.php` wraps the WordPress REST API using Guzzle and exposes helpers for posts, taxonomies, and JWT token exchange.
- The SDK is registered in `app/Providers/CoreServiceProvider.php` and bound to the `Modules\Core\Services\WordPress\Contracts\SdkContract` interface.
- All outbound calls use the `external` log channel and mask sensitive payloads (passwords, JWTs).
- The `TokenResolver` automatically attaches stored JWT tokens to outbound requests when available.

## Taxonomy – Categories

- Routes live in `routes/api.php` under the `/api/v1/wordpress` namespace.
- `Http/Controllers/WordPress/TokenController.php` manages token CRUD.
- `Http/Controllers/WordPress/CategoryController.php` (and supporting requests/services) provide list/create/update/delete operations that proxy to WordPress via the SDK while respecting remembered tokens.
- `Models/WpToken.php` plus the migration `database/migrations/2025_11_11_000001_create_wp_tokens_table.php` persist remembered JWTs.
- The Vue categories page at `resources/js/Pages/Taxonomy/Categories/Index.vue` mirrors WordPress’ hierarchy by default and allows column sorting when needed. Form requests enforce validation with DocBlocks and strict types.

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

- Taxonomy plan: `docs/plans/2025-11-11-categories-management.md`
- WordPress SDK guide: `docs/guides/core-wordpress-sdk.md`
- Home screen guide: `docs/guides/home-screen.md`
