## Core WordPress SDK

The Core module ships with a reusable SDK for talking to the [WordPress REST API](https://developer.wordpress.org/rest-api/reference/). The SDK wraps common endpoints with a type-safe, testable service based on Guzzle so application code stays focused on business logic, while the WordPress module owns the HTTP controllers, requests, and persistence around API workflows.

### Configuration

The SDK reads its configuration from `config('wordpress.api')` (and falls back to the same environment variables used previously inside `Modules\Core`). Provide the following environment variables to customise connectivity:

| Variable | Description | Default |
| --- | --- | --- |
| `WP_URL` | Canonical base URL of the WordPress site (no trailing slash). Every API request derives from this host by appending `/wp-json/…`. In shared environments this must be `https://soulevil.com`. | `https://soulevil.com` |
| `WORDPRESS_API_TIMEOUT` | Request timeout (seconds). | `10` |
| `WORDPRESS_API_USER_AGENT` | Custom user agent header for audit/compliance. | `CoreWordPressSdk/1.0` |
| `WORDPRESS_API_NAMESPACE` | API namespace segment. | `wp/v2` |

The SDK automatically transforms `WP_URL` into the REST base (`{WP_URL}/wp-json/`), ensuring UI links, previews, background jobs, and server-side integrations all target the same domain.

### Service binding

`Modules\Core\Providers\CoreServiceProvider` registers the SDK in the container as `Modules\Core\Services\WordPress\Contracts\SdkContract`. The binding uses a dedicated `GuzzleHttp\Client` instance configured with the options above.

```php
use Modules\Core\Services\WordPress\Contracts\SdkContract;

final readonly class FetchLatestPosts
{
    public function __construct(private SdkContract $sdk)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function __invoke(): array
    {
        return $this->sdk->posts(['per_page' => 5]);
    }
}
```

The contract exposes convenience helpers for the most common content resources:

| Method | Target endpoint |
| --- | --- |
| `posts(array $query = [])` | `/wp/v2/posts` |
| `post(int $id, array $query = [])` | `/wp/v2/posts/{id}` |
| `pages(array $query = [])` | `/wp/v2/pages` |
| `media(array $query = [])` | `/wp/v2/media` |
| `categories(array $query = [])` | `/wp/v2/categories` |
| `tags(array $query = [])` | `/wp/v2/tags` |
| `users(array $query = [])` | `/wp/v2/users` |
| `search(array $query = [])` | `/wp/v2/search` |
| `get(string $resource, array $query = [])` | Generic getter for any resource under the configured namespace. |
| `token(string $username, string $password)` | `/jwt-auth/v1/token` |

All responses are returned as decoded associative arrays. Transport failures and malformed JSON are normalised to `Modules\Core\Services\WordPress\Exceptions\WordPressRequestException` for consistent error handling.

- Frontend credentials are posted to the platform’s own API (`POST /api/v1/wordpress/token`)—never directly to WordPress. The WordPress module’s controller validates payloads, calls `SdkContract::token()`, persists the raw response inside the `wp_tokens` table, and returns a high-level status message to the browser.
- The navbar login form exposes a **Remember token** switch. When enabled, the resulting JWT is upserted (never duplicated) into the `wp_tokens` table; otherwise the token is used transiently and not persisted.
- `GET /api/v1/wordpress/token` returns the current token status (`remembered`, `masked_token`, and `username`) so SPA clients can hide credential input once a token is stored. `DELETE /api/v1/wordpress/token` forgets the remembered token and re-enables manual sign-in.
- The `wp_tokens` schema stores the original username, the issued JWT, and the entire response body (JSON) for auditing or later refresh workflows. Subsequent WordPress SDK calls automatically attach the most recently remembered token as a `Bearer` header through the shared resolver inside the Core service provider.
- Every outbound call to WordPress (including the JWT exchange) is logged via the dedicated `external` log channel. Request logs capture HTTP method, URI, and sanitized payloads (passwords masked); response logs mask sensitive tokens but retain enough structure for traceability.

### API response envelope

All HTTP responses from the Core module are wrapped in a consistent JSON envelope so SPA clients never need to rely on transport status codes:

```json
{
  "ok": false,
  "code": "wordpress.token_failed",
  "status": 502,
  "message": "WordPress API returned 502 (Bad Gateway)",
  "data": null,
  "meta": {
    "source_status": 502,
    "remembered": false
  }
}
```

- `ok`: boolean success flag (`true` on success, `false` otherwise).
- `code`: machine-readable identifier scoped by domain/action.
- `status`: logical status code (success responses may still report `201`, even though the HTTP transport status remains 200).
- `message`: human-readable summary for UI toasts/logs.
- `data`: payload for successful outcomes, or `null` on failure.
- `meta`: optional diagnostics (upstream status codes, request identifiers, whether the token was remembered, etc.).

Laravel helpers in `App\Http\Responses\ApiResponse` generate this shape (see `ApiResponse::success()` / `ApiResponse::error()`).

### Testing strategy

`tests/Unit/WordPressSdkTest.php` demonstrates the recommended approach: mock the `ClientInterface` for narrow “transport” scenarios and validate higher-level workflows with real HTTP calls in dedicated integration tests if required. This keeps the SDK simple, SOLID-compliant, and fully covered by automated tests.
