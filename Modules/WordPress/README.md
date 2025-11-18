# WordPress Module

## Overview

This module contains every WordPress-specific application layer that sits on top of the shared SDK exposed by `Modules/Core`:

- HTTP controllers for `POST|GET|DELETE /api/v1/wordpress/token`, category CRUD under `/api/v1/wordpress/categories`, and tag CRUD under `/api/v1/wordpress/tags`.
- Strongly typed `FormRequest` classes for token + taxonomy payloads, keeping validation logic close to the endpoints.
- `Modules\WordPress\Models\WpToken` plus the migration `database/migrations/2025_11_11_000001_create_wp_tokens_table.php` to persist remembered JWTs.
- `Modules\WordPress\Services\CategoryService` and `Modules\WordPress\Services\TagService`, which orchestrate SDK calls and structured action logging.
- Module-scoped configuration in `config/config.php` (`config('wordpress.api')`) so WordPress connectivity can be tuned without touching Core.

## Routes

Routes are defined in `routes/api.php` and automatically mounted beneath Laravel’s `api` prefix. They retain the historical URIs and names (`api.core.wordpress.*`) so SPA clients and tests continue to work without changes.

## Dependencies

- Consumes `Modules\Core\Services\WordPress\Contracts\SdkContract` for all upstream calls.
- Relies on `App\Logging\ActionLogger` to capture immutable audit trails each time categories and tags mutate.
- Shares the remembered-token resolver with Core: once the module stores a token, the SDK will automatically attach it to outbound requests.

## Testing

- Feature coverage for the HTTP surface lives in `tests/Feature/WordPressTokenTest.php`, `tests/Feature/WordPressCategoryApiTest.php`, and `tests/Feature/WordPressTagApiTest.php`.
- The category and tag domain logic is unit tested via `tests/Unit/WordPressCategoryServiceTest.php` and `tests/Unit/WordPressTagServiceTest.php`.
- Run `php artisan test` plus the standard quality gates (`composer lint`, `npm run typecheck`) before shipping changes.
