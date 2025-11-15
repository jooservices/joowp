# Security Best Practices Guide

Complete guide to implementing security measures following project security-first principles.

> **Related Documentation:**
> - [Security First Principles](../architecture/principles.md#19-security-first) - Security requirements
> - [Third-Party Integration Security](../architecture/principles.md#8-third-party-integration-security) - API proxy requirements

---

## Overview

This project follows **Security First** principle:
- **Input sanitization** - All user data validated and cleaned
- **SQL injection prevention** - No raw queries, use query builder
- **CSRF protection** - All state-changing endpoints protected
- **Rate limiting** - All public APIs have rate limits
- **Secure credential management** - No hardcoded secrets

---

## Input Validation

### FormRequest Validation

```php
<?php

declare(strict_types=1);

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreProductRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string|\Illuminate\Contracts\Validation\ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'sanitize'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'email' => ['required', 'email', 'max:255'],
        ];
    }
}
```

### Service Layer Validation

```php
public function create(array $data): Product
{
    // Additional business validation
    if (empty($data['name'])) {
        throw new \InvalidArgumentException('Product name is required');
    }
    
    // Sanitize input
    $data['name'] = trim(strip_tags($data['name']));
    $data['description'] = $data['description'] ? strip_tags($data['description']) : null;
    
    return $this->repository->create($data);
}
```

---

## SQL Injection Prevention

### ✅ DO: Use Query Builder

```php
// ✅ GOOD: Parameterized queries
$products = DB::table('products')
    ->where('category_id', $categoryId)
    ->where('price', '>', $minPrice)
    ->get();

// ✅ GOOD: Eloquent ORM
$products = Product::where('category_id', $categoryId)
    ->where('price', '>', $minPrice)
    ->get();
```

### ❌ DON'T: Raw Queries

```php
// ❌ BAD: Raw SQL with string concatenation
$products = DB::select("SELECT * FROM products WHERE category_id = {$categoryId}");

// ❌ BAD: Raw SQL without parameter binding
$products = DB::select("SELECT * FROM products WHERE name = '{$name}'");
```

---

## CSRF Protection

### Laravel Automatic CSRF

Laravel automatically protects all POST, PUT, DELETE, and PATCH routes with CSRF tokens.

**For API routes:**
- Use `auth:sanctum` middleware (token-based auth)
- CSRF not required for stateless API authentication

**For web routes:**
- CSRF token automatically included in forms
- Verify token on submission

---

## Rate Limiting

### API Rate Limiting

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
});

// More restrictive for expensive operations
Route::middleware(['auth:sanctum', 'throttle:10,1'])->group(function () {
    Route::post('/products/export', [ProductController::class, 'export']);
});
```

### Custom Rate Limiting

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

---

## Authentication & Authorization

### Sanctum Token Authentication

```php
// Generate token
$token = $user->createToken('api-token')->plainTextToken;

// Use token in requests
// Header: Authorization: Bearer {token}
```

### Authorization Checks

```php
public function update(Product $product, UpdateProductRequest $request): JsonResponse
{
    // Check authorization
    if (! $request->user()->can('update', $product)) {
        return ApiResponse::error(
            code: 'product.update_forbidden',
            message: 'You do not have permission to update this product',
            status: 403
        );
    }
    
    $product = $this->service->update($product, $request->validated());
    
    return ProductResource::make($product)->response();
}
```

---

## Credential Management

### Environment Variables

```bash
# .env
WP_URL=https://soulevil.com
WORDPRESS_API_USERNAME=admin
WORDPRESS_API_PASSWORD=secure_password_here
LM_STUDIO_API_KEY=secret_key_here
```

### Configuration Files

```php
// config/wordpress.php
return [
    'api' => [
        'url' => env('WP_URL', 'https://soulevil.com'),
        'username' => env('WORDPRESS_API_USERNAME'),
        'password' => env('WORDPRESS_API_PASSWORD'),
    ],
];
```

### ❌ NEVER

- ❌ **Never hardcode credentials** in code
- ❌ **Never commit `.env` file** to version control
- ❌ **Never expose credentials** in error messages
- ❌ **Never log credentials** in application logs

---

## Data Sanitization

### HTML Sanitization

```php
// Strip HTML tags
$clean = strip_tags($input);

// Allow specific tags
$clean = strip_tags($input, '<p><br><strong>');

// Use HTMLPurifier for complex sanitization
use HTMLPurifier;
$purifier = new HTMLPurifier();
$clean = $purifier->purify($input);
```

### SQL Injection Prevention

```php
// ✅ GOOD: Parameter binding
DB::table('users')
    ->where('email', $email)
    ->first();

// ✅ GOOD: Eloquent (automatic parameter binding)
User::where('email', $email)->first();
```

---

## Secure Headers

### Security Headers Middleware

```php
// app/Http/Middleware/SecurityHeaders.php
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        
        return $response;
    }
}
```

---

## Best Practices

### ✅ DO

- **Validate all inputs** - Use FormRequest for validation
- **Sanitize user data** - Strip HTML, escape special characters
- **Use parameterized queries** - Never concatenate SQL
- **Protect CSRF** - Laravel handles this automatically
- **Rate limit APIs** - Prevent abuse and DoS
- **Store credentials securely** - Use environment variables
- **Check authorization** - Verify user permissions
- **Log security events** - Monitor suspicious activity

### ❌ DON'T

- **Don't trust user input** - Always validate and sanitize
- **Don't use raw SQL** - Use query builder or Eloquent
- **Don't hardcode secrets** - Use environment variables
- **Don't expose internals** - Don't leak system details in errors
- **Don't skip authorization** - Always check permissions
- **Don't log sensitive data** - Mask passwords, tokens, secrets

---

## Related Documentation

- [Security First Principles](../architecture/principles.md#19-security-first) - Security requirements
- [Third-Party Integration Security](../architecture/principles.md#8-third-party-integration-security) - API proxy requirements

