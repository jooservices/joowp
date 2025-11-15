# RESTful API Design Guide

Complete guide to designing RESTful APIs following project standards, security-first principles, and modern best practices.

> **Related Documentation:**
> - [API Response Standards](../reference/standards.md#api-response-standards) - Response format requirements
> - [API Versioning Guide](api-versioning.md) - Versioning implementation
> - [Security Best Practices](security-best-practices.md) - Authentication, authorization, rate limiting
> - [Security First Principles](../architecture/principles.md#23-security-first) - Security requirements

---

## Overview

This project follows **RESTful API design principles** with:
- **Security-first approach** - UUID identifiers, proper authentication, rate limiting
- **Consistent conventions** - Resource naming, URL structure, HTTP methods
- **Standardized responses** - Envelope pattern with consistent error handling
- **Versioning support** - Semantic versioning with backward compatibility

---

## Resource Identifier Policy

### ✅ MUST: Use UUID for All Public API Endpoints

**Security and Privacy Rules:**

1. ✅ **MUST:** Use UUID for all public API resource identifiers
2. ✅ **MUST:** Route parameter name: `{uuid}` (not `{id}`)
3. ✅ **MUST:** Model primary key: `uuid` (string, UUID type)
4. ✅ **MUST:** Validate UUID format in FormRequest/Route model binding
5. ❌ **FORBIDDEN:** Using integer `id` in public API routes
6. ❌ **FORBIDDEN:** Using `{id}` as route parameter name
7. ⚠️ **ALLOWED:** Integer `id` for internal-only endpoints (requires justification)

### Why UUID?

- **Security:** Prevents enumeration attacks (cannot guess next ID)
- **Privacy:** Does not expose business metrics (user count, order count)
- **Non-sequential:** Cannot scan through IDs
- **Distributed:** Safe for multi-database/multi-service architectures
- **URL-safe:** No special characters requiring encoding

### Examples

```php
// ✅ CORRECT: UUID in routes
Route::get('/products/{uuid}', [ProductController::class, 'show']);
Route::put('/products/{uuid}', [ProductController::class, 'update']);
Route::delete('/products/{uuid}', [ProductController::class, 'destroy']);

// ✅ CORRECT: UUID in controller
public function show(string $uuid): JsonResponse
{
    $product = Product::where('uuid', $uuid)->firstOrFail();
    return ProductResource::make($product)->response();
}

// ❌ WRONG: Integer ID
Route::get('/products/{id}', [ProductController::class, 'show']);  // FORBIDDEN

// ❌ WRONG: Route parameter name
Route::get('/products/{productId}', [ProductController::class, 'show']);  // Use {uuid}
```

### UUID Validation

```php
// FormRequest validation
final class ShowProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'uuid' => ['required', 'uuid'],
        ];
    }
}

// Route model binding with UUID
// app/Providers/RouteServiceProvider.php
Route::bind('uuid', function (string $value) {
    return Product::where('uuid', $value)->firstOrFail();
});
```

---

## Resource Naming Conventions

### URL Structure

**Format:** `/api/v{version}/{resource}/{uuid}/{sub-resource}/{uuid}`

**Rules:**
- ✅ **MUST:** Use plural nouns for resource names (`products`, `users`, `categories`)
- ✅ **MUST:** Use kebab-case for multi-word resources (`product-categories`, `user-preferences`)
- ✅ **MUST:** Use lowercase for all resource names
- ✅ **MUST:** Include version in URL path (`/api/v1/`, `/api/v2/`)
- ❌ **FORBIDDEN:** Singular resource names (`/api/v1/product`)
- ❌ **FORBIDDEN:** CamelCase or snake_case in URLs

### Examples

```php
// ✅ CORRECT: Plural, kebab-case, versioned
GET    /api/v1/products
POST   /api/v1/products
GET    /api/v1/products/{uuid}
PUT    /api/v1/products/{uuid}
PATCH  /api/v1/products/{uuid}
DELETE /api/v1/products/{uuid}

// ✅ CORRECT: Nested resources
GET    /api/v1/products/{uuid}/reviews
POST   /api/v1/products/{uuid}/reviews
GET    /api/v1/products/{uuid}/reviews/{uuid}

// ✅ CORRECT: Multi-word resources
GET    /api/v1/product-categories
GET    /api/v1/user-preferences

// ❌ WRONG: Singular
GET    /api/v1/product  // FORBIDDEN

// ❌ WRONG: No version
GET    /api/products  // FORBIDDEN

// ❌ WRONG: CamelCase
GET    /api/v1/productCategories  // FORBIDDEN
```

---

## HTTP Methods Usage

### Standard RESTful Methods

| Method | Purpose | Idempotent | Safe | Status Code |
|--------|---------|------------|------|-------------|
| `GET` | Retrieve resource(s) | ✅ Yes | ✅ Yes | 200 |
| `POST` | Create new resource | ❌ No | ❌ No | 201 |
| `PUT` | Full update (replace) | ✅ Yes | ❌ No | 200 |
| `PATCH` | Partial update | ✅ Yes | ❌ No | 200 |
| `DELETE` | Delete resource | ✅ Yes | ❌ No | 204 |

### Rules

- ✅ **MUST:** Use `GET` for read-only operations (no side effects)
- ✅ **MUST:** Use `POST` for creating new resources
- ✅ **MUST:** Use `PUT` for full resource replacement (all fields required)
- ✅ **MUST:** Use `PATCH` for partial updates (only changed fields)
- ✅ **MUST:** Use `DELETE` for resource deletion
- ❌ **FORBIDDEN:** Using `GET` for state-changing operations
- ❌ **FORBIDDEN:** Using `POST` for updates (use PUT/PATCH)

### PUT vs PATCH Decision

**Use PUT when:**
- Replacing entire resource
- All fields are required
- Client sends complete resource representation

**Use PATCH when:**
- Updating specific fields only
- Partial updates are common
- Client sends only changed fields

```php
// ✅ PUT: Full update (all fields required)
PUT /api/v1/products/{uuid}
{
    "name": "Updated Product",
    "description": "Updated description",
    "price": 99.99,
    "category_id": "uuid-here"
}

// ✅ PATCH: Partial update (only changed fields)
PATCH /api/v1/products/{uuid}
{
    "price": 89.99
}
```

---

## HTTP Status Codes

### Standard Status Codes

| Code | Meaning | Usage | Response Body |
|------|---------|-------|---------------|
| `200` | OK | Successful GET, PUT, PATCH | Resource data |
| `201` | Created | Successful POST (resource created) | Created resource |
| `204` | No Content | Successful DELETE | Empty body |
| `400` | Bad Request | Invalid request format, business logic error | Error details |
| `401` | Unauthorized | Missing or invalid authentication | Error message |
| `403` | Forbidden | Authenticated but not authorized | Error message |
| `404` | Not Found | Resource not found | Error message |
| `422` | Unprocessable Entity | Validation failed (FormRequest) | Validation errors |
| `429` | Too Many Requests | Rate limit exceeded | Rate limit info |
| `500` | Internal Server Error | Server error | Error message (no stack trace) |

### Rules

- ✅ **MUST:** Use `Response::HTTP_OK` (200) for successful GET, PUT, PATCH
- ✅ **MUST:** Use `Response::HTTP_CREATED` (201) for successful POST (resource created)
- ✅ **MUST:** Use `Response::HTTP_NO_CONTENT` (204) for successful DELETE (no response body)
- ✅ **MUST:** Use `Response::HTTP_BAD_REQUEST` (400) for business logic errors (invalid state, rule violation)
- ✅ **MUST:** Use `Response::HTTP_UNAUTHORIZED` (401) for missing/invalid authentication
- ✅ **MUST:** Use `Response::HTTP_FORBIDDEN` (403) for authorization failures (authenticated but not allowed)
- ✅ **MUST:** Use `Response::HTTP_NOT_FOUND` (404) for resource not found
- ✅ **MUST:** Use `Response::HTTP_UNPROCESSABLE_ENTITY` (422) for validation errors (FormRequest handles automatically)
- ✅ **MUST:** Use `Response::HTTP_TOO_MANY_REQUESTS` (429) for rate limit exceeded
- ✅ **MUST:** Use `Response::HTTP_INTERNAL_SERVER_ERROR` (500) for unexpected server errors
- ❌ **FORBIDDEN:** Using magic numbers (200, 201, 404, etc.) instead of constants
- ❌ **FORBIDDEN:** Returning stack traces or sensitive error details in production

**Required Import:**
```php
use Illuminate\Http\Response;
```

### Examples

```php
use Illuminate\Http\Response;
use App\Http\Responses\ApiResponse;

// ✅ 200: Successful GET
return ProductResource::make($product)
    ->response()
    ->setStatusCode(Response::HTTP_OK);

// ✅ 201: Resource created
return ProductResource::make($product)
    ->response()
    ->setStatusCode(Response::HTTP_CREATED);

// ✅ 204: Resource deleted
return response()->noContent();  // Automatically uses Response::HTTP_NO_CONTENT

// ✅ 400: Business logic error
return ApiResponse::error(
    code: 'product.out_of_stock',
    message: 'Product is out of stock',
    status: Response::HTTP_BAD_REQUEST
);

// ✅ 401: Unauthorized
return ApiResponse::error(
    code: 'auth.unauthorized',
    message: 'Authentication required',
    status: Response::HTTP_UNAUTHORIZED
);

// ✅ 403: Forbidden
return ApiResponse::error(
    code: 'auth.forbidden',
    message: 'Insufficient permissions',
    status: Response::HTTP_FORBIDDEN
);

// ✅ 404: Resource not found
// Laravel automatically returns Response::HTTP_NOT_FOUND if Model::findOrFail() fails

// ✅ 422: Validation failed
// Laravel automatically returns Response::HTTP_UNPROCESSABLE_ENTITY if FormRequest validation fails

// ✅ 429: Rate limit exceeded
return ApiResponse::error(
    code: 'rate_limit.exceeded',
    message: 'Too many requests',
    status: Response::HTTP_TOO_MANY_REQUESTS
);

// ✅ 500: Internal server error
return ApiResponse::error(
    code: 'server.error',
    message: 'An unexpected error occurred',
    status: Response::HTTP_INTERNAL_SERVER_ERROR
);
```

---

## Query Parameters

### Pagination

**Standard Pagination Parameters:**

- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 15, max: 100)

**Response Format:**

```json
{
  "ok": true,
  "code": "products.list",
  "status": 200,
  "message": "Products retrieved successfully",
  "data": [...],
  "meta": {
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 150,
      "last_page": 10,
      "from": 1,
      "to": 15
    }
  }
}
```

**Implementation:**

```php
// Controller
public function index(Request $request): JsonResponse
{
    $perPage = min((int) $request->get('per_page', 15), 100);
    $products = Product::paginate($perPage);
    
    return ApiResponse::success(
        code: 'products.list',
        message: 'Products retrieved successfully',
        data: ProductResource::collection($products->items()),
        meta: ['pagination' => [
            'current_page' => $products->currentPage(),
            'per_page' => $products->perPage(),
            'total' => $products->total(),
            'last_page' => $products->lastPage(),
            'from' => $products->firstItem(),
            'to' => $products->lastItem(),
        ]]
    );
}
```

### Filtering

**Standard Filter Parameters:**

- `filter[field]=value` - Filter by field
- `filter[field][operator]=value` - Filter with operator (eq, ne, gt, gte, lt, lte, like, in)

**Examples:**

```
GET /api/v1/products?filter[status]=active
GET /api/v1/products?filter[price][gte]=100
GET /api/v1/products?filter[category_id][in]=uuid1,uuid2,uuid3
GET /api/v1/products?filter[name][like]=laptop
```

**Implementation:**

```php
// FormRequest
final class IndexProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'filter.status' => ['sometimes', 'string', 'in:active,inactive'],
            'filter.price.gte' => ['sometimes', 'numeric', 'min:0'],
            'filter.price.lte' => ['sometimes', 'numeric', 'min:0'],
            'filter.category_id' => ['sometimes', 'uuid'],
            'filter.name.like' => ['sometimes', 'string', 'max:255'],
        ];
    }
}

// Controller
public function index(IndexProductRequest $request): JsonResponse
{
    $query = Product::query();
    
    if ($request->has('filter.status')) {
        $query->where('status', $request->input('filter.status'));
    }
    
    if ($request->has('filter.price.gte')) {
        $query->where('price', '>=', $request->input('filter.price.gte'));
    }
    
    if ($request->has('filter.name.like')) {
        $query->where('name', 'like', '%' . $request->input('filter.name.like') . '%');
    }
    
    $products = $query->paginate(15);
    // ...
}
```

### Sorting

**Standard Sort Parameters:**

- `sort` - Field to sort by (default: `created_at`)
- `order` - Sort direction: `asc` or `desc` (default: `desc`)

**Examples:**

```
GET /api/v1/products?sort=name&order=asc
GET /api/v1/products?sort=price&order=desc
GET /api/v1/products?sort=created_at&order=desc  // Default
```

**Implementation:**

```php
// FormRequest
final class IndexProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sort' => ['sometimes', 'string', 'in:name,price,created_at,updated_at'],
            'order' => ['sometimes', 'string', 'in:asc,desc'],
        ];
    }
}

// Controller
public function index(IndexProductRequest $request): JsonResponse
{
    $sort = $request->get('sort', 'created_at');
    $order = $request->get('order', 'desc');
    
    $products = Product::query()
        ->orderBy($sort, $order)
        ->paginate(15);
    // ...
}
```

### Field Selection

**Standard Field Selection:**

- `fields` - Comma-separated list of fields to include

**Example:**

```
GET /api/v1/products?fields=uuid,name,price
```

**Note:** Field selection is typically handled by Resource classes, not query parameters.

---

## Authentication & Authorization

### Authentication

**Requirements:**
- ✅ **MUST:** All protected endpoints require authentication
- ✅ **MUST:** Use Laravel Sanctum or Passport for API tokens
- ✅ **MUST:** Validate token on every request
- ✅ **MUST:** Return `401 Unauthorized` for missing/invalid tokens
- ❌ **FORBIDDEN:** Session-based authentication for API endpoints

### Authorization

**Requirements:**
- ✅ **MUST:** Use Laravel Policies for resource authorization
- ✅ **MUST:** Check authorization in Controller (after authentication)
- ✅ **MUST:** Return `403 Forbidden` for authorization failures
- ✅ **MUST:** Include authorization logic in FormRequest `authorize()` method

**Example:**

```php
// Policy
final class ProductPolicy
{
    public function view(User $user, Product $product): bool
    {
        return $user->can('view', $product);
    }
    
    public function update(User $user, Product $product): bool
    {
        return $user->id === $product->user_id;
    }
}

// Controller
public function update(UpdateProductRequest $request, string $uuid): JsonResponse
{
    $product = Product::where('uuid', $uuid)->firstOrFail();
    
    // Authorization checked in FormRequest::authorize()
    $this->authorize('update', $product);
    
    // Update logic...
}

// FormRequest
final class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = Product::where('uuid', $this->route('uuid'))->firstOrFail();
        return $this->user()->can('update', $product);
    }
}
```

---

## Rate Limiting

### Requirements

- ✅ **MUST:** Implement rate limiting on all public API endpoints
- ✅ **MUST:** Use Laravel's rate limiter middleware
- ✅ **MUST:** Return `429 Too Many Requests` when limit exceeded
- ✅ **MUST:** Include rate limit headers in response

### Configuration

```php
// app/Providers/RouteServiceProvider.php
Route::middleware(['api', 'throttle:api'])->group(function () {
    Route::prefix('v1')->group(function () {
        Route::apiResource('products', ProductController::class);
    });
});

// config/services.php
'rate_limiter' => [
    'api' => [
        'max_attempts' => 60,
        'decay_minutes' => 1,
    ],
    'api_strict' => [
        'max_attempts' => 10,
        'decay_minutes' => 1,
    ],
],
```

### Rate Limit Headers

```php
// Middleware automatically adds headers:
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1640995200
```

### Error Response

```php
use Illuminate\Http\Response;

// 429 Too Many Requests
return ApiResponse::error(
    code: 'rate_limit.exceeded',
    message: 'Too many requests. Please try again later.',
    status: Response::HTTP_TOO_MANY_REQUESTS,
    meta: [
        'retry_after' => 60,  // seconds
        'limit' => 60,
        'remaining' => 0,
    ]
);
```

---

## CORS Configuration

### Requirements

- ✅ **MUST:** Configure CORS for API endpoints
- ✅ **MUST:** Restrict allowed origins in production
- ✅ **MUST:** Allow necessary HTTP methods and headers
- ❌ **FORBIDDEN:** Allowing all origins (`*`) in production

### Configuration

```php
// config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
    'allowed_origins' => env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000'),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
    'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining'],
    'max_age' => 3600,
    'supports_credentials' => false,
];
```

---

## Complete CRUD Example

### Routes

```php
// routes/api.php
Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::apiResource('products', ProductController::class);
});
```

### Controller

```php
<?php

declare(strict_types=1);

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Product\Http\Requests\IndexProductRequest;
use Modules\Product\Http\Requests\StoreProductRequest;
use Modules\Product\Http\Requests\UpdateProductRequest;
use Modules\Product\Http\Resources\ProductResource;
use Modules\Product\Models\Product;
use Modules\Product\Services\ProductService;

final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {
    }

    public function index(IndexProductRequest $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 15), 100);
        $sort = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');
        
        $products = Product::query()
            ->orderBy($sort, $order)
            ->paginate($perPage);
        
        return ApiResponse::success(
            code: 'products.list',
            message: 'Products retrieved successfully',
            data: ProductResource::collection($products->items()),
            meta: ['pagination' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ]]
        );
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated());
        
        return ProductResource::make($product)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(string $uuid): JsonResponse
    {
        $product = Product::where('uuid', $uuid)->firstOrFail();
        
        return ProductResource::make($product)->response();
    }

    public function update(UpdateProductRequest $request, string $uuid): JsonResponse
    {
        $product = Product::where('uuid', $uuid)->firstOrFail();
        $this->authorize('update', $product);
        
        $product = $this->productService->update($product, $request->validated());
        
        return ProductResource::make($product)->response();
    }

    public function destroy(string $uuid): JsonResponse
    {
        $product = Product::where('uuid', $uuid)->firstOrFail();
        $this->authorize('delete', $product);
        
        $this->productService->delete($product);
        
        return response()->noContent();  // 204
    }
}
```

---

## Best Practices Summary

### ✅ DO

- Use UUID for all resource identifiers
- Use plural nouns for resource names
- Use kebab-case for multi-word resources
- Include version in URL path
- Use appropriate HTTP methods (GET, POST, PUT, PATCH, DELETE)
- Return appropriate status codes
- Implement pagination, filtering, sorting
- Use FormRequest for validation
- Use Policies for authorization
- Implement rate limiting
- Configure CORS properly
- Use standardized ApiResponse envelope

### ❌ DON'T

- Use integer IDs in public API routes
- Use singular resource names
- Use GET for state-changing operations
- Return stack traces in production
- Allow all origins in CORS
- Skip authentication/authorization
- Skip rate limiting
- Mix response formats

---

## Related Documentation

- [API Response Standards](../reference/standards.md#api-response-standards) - Response format requirements
- [API Versioning Guide](api-versioning.md) - Versioning implementation
- [Security Best Practices](security-best-practices.md) - Authentication, authorization, rate limiting
- [Laravel Components & Patterns](laravel-components-patterns.md) - Controller, FormRequest, Resource patterns
- [Error Handling Guide](error-handling.md) - Error response patterns

