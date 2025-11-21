# HTTP Client Usage Guide

This guide describes how to use `jooservices/jooclient` package for all HTTP requests in joowp modules.

## Overview

All external HTTP requests should use `jooservices/jooclient` package via dependency injection. This provides:
- **Automatic retries** with exponential backoff
- **Centralized logging** (MongoDB, MySQL, Monolog)
- **Circuit breaker** pattern for resilience
- **Rate limiting** to prevent API abuse
- **Data sanitization** (GDPR & PCI-DSS compliant)
- **Performance metrics** tracking

## Package Information

- **Package**: https://packagist.org/packages/jooservices/jooclient
- **Version**: 1.3.0+
- **Integration Guide**: `vendor/jooservices/jooclient/docs/guides/JOOWP_INTEGRATION.md`
- **Usage Guide**: `vendor/jooservices/jooclient/docs/guides/USAGE_GUIDE.md`

## Dependency Injection

### Using HttpClientContract

Inject `HttpClientContract` into your service classes:

```php
<?php

declare(strict_types=1);

namespace Modules\WordPress\Services;

use JOOservices\Client\Contracts\HttpClientContract;

final class WordPressService
{
    public function __construct(
        private readonly HttpClientContract $httpClient
    ) {
    }

    public function fetchPosts(): array
    {
        $response = $this->httpClient->get('https://api.wordpress.com/posts');
        
        if ($response->isSuccess()) {
            return $response->getContent() ?? [];
        }
        
        return [];
    }
}
```

### Using JsonHttpClientContract (Recommended for JSON APIs)

For JSON APIs, use `JsonHttpClientContract` for convenience methods:

```php
<?php

declare(strict_types=1);

namespace Modules\WordPress\Services;

use JOOservices\Client\Contracts\JsonHttpClientContract;

final class WordPressService
{
    public function __construct(
        private readonly JsonHttpClientContract $httpClient
    ) {
    }

    public function fetchPosts(): array
    {
        // getJson() automatically decodes JSON and returns array|null
        $data = $this->httpClient->getJson('https://api.wordpress.com/posts');
        
        return $data ?? [];
    }

    public function createPost(array $data): array|null
    {
        // postJson() automatically sets Content-Type: application/json
        return $this->httpClient->postJson('https://api.wordpress.com/posts', $data);
    }
}
```

## Service Provider Configuration

### WordPress SDK Example

```php
// Modules/Core/app/Providers/CoreServiceProvider.php

use JOOservices\Client\Contracts\JsonHttpClientContract;
use JOOservices\Client\Factory\Factory;

$factory = (new Factory())
    ->addOptions([
        'base_uri' => 'https://example.com/wp-json/',
        'timeout' => 10.0,
        'headers' => [
            'Accept' => 'application/json',
            'User-Agent' => 'CoreWordPressSdk/1.0',
        ],
    ])
    ->enableRetries(3, 2, 500); // 3 retries, 2s delay, retry on 5xx

$client = $factory->make();
```

## Available Methods

### JSON Convenience Methods (JsonHttpClientContract)

```php
// GET JSON
$data = $httpClient->getJson('https://api.example.com/users');
// Returns: array|null (null on failure or non-array response)

// POST JSON
$result = $httpClient->postJson('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

// PUT/PATCH JSON
$updated = $httpClient->putJson('https://api.example.com/users/1', $data);
$patched = $httpClient->patchJson('https://api.example.com/users/1', $data);
```

### Standard HTTP Methods (HttpClientContract)

```php
// GET
$response = $httpClient->get('https://api.example.com/users');
if ($response->isSuccess()) {
    $data = $response->getContent(); // Auto-decodes JSON
}

// POST
$response = $httpClient->post('https://api.example.com/users', [
    'json' => ['name' => 'John'],
]);

// DELETE
$response = $httpClient->delete('https://api.example.com/users/1');
```

## Error Handling

### Using ResponseWrapper

```php
$response = $httpClient->get('https://api.example.com/users');

if ($response->isSuccess()) {
    $data = $response->getContent();
} else {
    $statusCode = $response->getStatusCode();
    $errorMessage = $response->getErrorMessage();
    // Handle error
}
```

### Exception Handling

```php
try {
    $data = $httpClient->getJson('https://api.example.com/users');
} catch (\GuzzleHttp\Exception\RequestException $e) {
    // Handle network errors, timeouts, etc.
    logger()->error('HTTP request failed', [
        'url' => $e->getRequest()->getUri(),
        'error' => $e->getMessage(),
    ]);
}
```

## Testing

### Using Factory::fakeResponses()

```php
use JOOservices\Client\Factory\Factory;
use GuzzleHttp\Psr7\Response;

$factory = (new Factory())
    ->fakeResponses([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            ['id' => 1, 'name' => 'User 1'],
        ])),
    ]);

$httpClient = $factory->make();

// Use in your service
$service = new WordPressService($httpClient);
$users = $service->fetchUsers();
```

### Mocking HttpClientContract

```php
use JOOservices\Client\Contracts\HttpClientContract;
use JOOservices\Client\Http\ResponseWrapper;

$mockClient = $this->mock(HttpClientContract::class);
$mockClient->shouldReceive('getJson')
    ->once()
    ->with('https://api.example.com/users')
    ->andReturn([['id' => 1, 'name' => 'User 1']]);

$this->app->instance(HttpClientContract::class, $mockClient);
```

## Best Practices

### 1. Always Use Dependency Injection

```php
// ✅ GOOD: Inject contract
public function __construct(
    private readonly JsonHttpClientContract $httpClient
) {}

// ❌ BAD: Direct instantiation
$client = new \GuzzleHttp\Client();
```

### 2. Use JSON Methods for JSON APIs

```php
// ✅ GOOD: Use getJson/postJson
$data = $httpClient->getJson('https://api.example.com/users');

// ❌ BAD: Manual JSON handling
$response = $httpClient->get('https://api.example.com/users');
$data = json_decode($response->getBody(), true);
```

### 3. Handle Errors Gracefully

```php
// ✅ GOOD: Check response
$data = $httpClient->getJson('https://api.example.com/users');
if ($data === null) {
    return [];
}

// ❌ BAD: Assume success
$data = $httpClient->getJson('https://api.example.com/users');
return $data['items']; // May fail if null
```

### 4. Configure Per-Service

Each service should have its own Factory configuration in the ServiceProvider:

```php
// WordPress SDK
$factory = (new Factory())
    ->addOptions(['base_uri' => config('wordpress.api.base_uri')])
    ->enableRetries(3, 2, 500);

// LM Studio SDK
$factory = (new Factory())
    ->addOptions(['timeout' => config('lmstudio.timeout')])
    ->enableRetries(2, 1, 500);
```

## Migration Checklist

When migrating existing code to jooclient:

- [ ] Replace `new \GuzzleHttp\Client()` with dependency injection
- [ ] Replace `Http::` facade with `HttpClientContract` injection
- [ ] Update service providers to create jooclient Factory
- [ ] Use `getJson()`/`postJson()` for JSON APIs
- [ ] Update tests to use `Factory::fakeResponses()`
- [ ] Remove direct Guzzle/Http dependencies

## Related Documentation

- **jooclient MongoDB Configuration**: `docs/guides/jooclient-mongodb-configuration.md`
- **Core HTTP Client Adoption Plan**: `docs/plans/technical/2025-11-14-core-http-client-adoption.md`
- **jooclient Integration Guide**: `vendor/jooservices/jooclient/docs/guides/JOOWP_INTEGRATION.md`

