# API Versioning Implementation Guide

Complete guide to implementing API versioning following semantic versioning and backward compatibility principles.

> **Related Documentation:**
> - [API Versioning Principles](../architecture/principles.md#13-api-versioning-discipline) - Semantic versioning and compatibility
> - [API Response Standards](../reference/standards.md#api-response-standards) - Response format requirements

---

## Overview

This project follows **Semantic Versioning** for API changes:
- **MAJOR** version: Breaking changes (incompatible API changes)
- **MINOR** version: New features (backward compatible)
- **PATCH** version: Bug fixes (backward compatible)

**Requirements:**
- Maintain at least 2 versions simultaneously
- Provide deprecation warnings before removal
- Support backward compatibility

---

## Version Routing

### Route Structure

```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
});

Route::prefix('v2')->group(function () {
    Route::get('/products', [ProductV2Controller::class, 'index']);
    Route::post('/products', [ProductV2Controller::class, 'store']);
});
```

### Version Detection

```php
// Middleware to detect API version
final class ApiVersionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $version = $request->header('API-Version', 'v1');
        
        // Validate version
        if (! in_array($version, ['v1', 'v2'], true)) {
            return ApiResponse::error(
                code: 'api.unsupported_version',
                message: 'Unsupported API version',
                status: 400
            );
        }
        
        $request->attributes->set('api_version', $version);
        
        return $next($request);
    }
}
```

---

## Backward Compatibility

### Maintaining Old Versions

```php
// v1 Controller (keep for backward compatibility)
final class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        // Original v1 implementation
        return ApiResponse::success(
            code: 'product.list',
            message: 'Products retrieved successfully',
            data: ['items' => $this->service->list()],
        );
    }
}

// v2 Controller (new implementation)
final class ProductV2Controller extends Controller
{
    public function index(): JsonResponse
    {
        // Enhanced v2 implementation with new features
        $products = $this->service->list();
        
        return ApiResponse::success(
            code: 'product.list',
            message: 'Products retrieved successfully',
            data: [
                'items' => ProductV2Resource::collection($products),
                'meta' => [
                    'total' => count($products),
                    'version' => 'v2',
                ],
            ],
        );
    }
}
```

### Shared Service Layer

```php
// Services can be shared across versions
final class ProductService
{
    // Service implementation remains the same
    // Only controllers change between versions
}
```

---

## Deprecation Process

### Adding Deprecation Warnings

```php
final class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $response = ApiResponse::success(
            code: 'product.list',
            message: 'Products retrieved successfully',
            data: ['items' => $this->service->list()],
        );
        
        // Add deprecation warning
        $response->header('Deprecation', 'true');
        $response->header('Sunset', '2026-01-01');
        $response->header('Link', '<https://api.example.com/docs/v2>; rel="successor-version"');
        
        return $response;
    }
}
```

### Deprecation Timeline

1. **Announcement** (3 months before removal):
   - Add deprecation headers to responses
   - Update documentation
   - Notify API consumers

2. **Warning Period** (1 month before removal):
   - Increase deprecation warnings
   - Provide migration guide
   - Offer support for migration

3. **Removal**:
   - Remove deprecated endpoints
   - Update version documentation

---

## Version Negotiation

### Client-Supported Versions

```typescript
// Frontend API client
interface ApiClientConfig {
    preferredVersion: string;
    supportedVersions: string[];
}

class ApiClient {
    private config: ApiClientConfig = {
        preferredVersion: 'v2',
        supportedVersions: ['v1', 'v2'],
    };

    async request<T>(endpoint: string): Promise<T> {
        const response = await axios.get(endpoint, {
            headers: {
                'API-Version': this.config.preferredVersion,
                'Accept-Version': this.config.supportedVersions.join(', '),
            },
        });

        // Check for deprecation warnings
        if (response.headers['deprecation'] === 'true') {
            console.warn('API version is deprecated', {
                sunset: response.headers['sunset'],
                successor: response.headers['link'],
            });
        }

        return response.data;
    }
}
```

---

## Best Practices

### ✅ DO

- **Maintain backward compatibility** - Don't break existing clients
- **Use semantic versioning** - Follow MAJOR.MINOR.PATCH
- **Provide deprecation warnings** - Give clients time to migrate
- **Document version changes** - Update API documentation
- **Support multiple versions** - Maintain at least 2 versions
- **Version in URL or header** - Consistent versioning strategy

### ❌ DON'T

- **Don't remove versions abruptly** - Always deprecate first
- **Don't break backward compatibility** - Use new version for breaking changes
- **Don't mix versioning strategies** - Choose URL or header, not both
- **Don't skip deprecation warnings** - Always warn before removal

---

## Related Documentation

- [API Versioning Principles](../architecture/principles.md#13-api-versioning-discipline) - Semantic versioning requirements
- [API Response Standards](../reference/standards.md#api-response-standards) - Response format

