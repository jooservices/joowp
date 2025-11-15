# Error Handling Guide

Complete guide to exception handling, error responses, and logging strategies following project standards.

> **Related Documentation:**
> - [Defensive Programming Principles](../architecture/principles.md#20-defensive-programming) - Fail fast and explicit error handling
> - [API Response Standards](../reference/standards.md#api-response-standards) - Error response format
> - [Audit Logging Standards](../reference/standards.md#audit-logging-standards) - Error logging requirements

---

## Overview

This project follows **Fail Fast** and **Explicit Error Handling** principles:
- **Validate early** - Check preconditions at method entry
- **Fail fast** - Return/throw immediately when conditions aren't met
- **Explicit errors** - Never silently fail or return null unexpectedly
- **Structured responses** - Consistent error format across all endpoints

---

## Exception Hierarchy

### Base Exception Classes

```php
// Base exception for SDK errors
class LmStudioException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        private readonly array $context = [],
    ) {
        parent::__construct($message, $code, $previous);
    }
    
    public function getContext(): array
    {
        return $this->context;
    }
}

// Specific exception types
class ConnectionException extends LmStudioException {}
class ValidationException extends LmStudioException {}
class StreamingException extends LmStudioException {}
```

### Exception Best Practices

```php
// ✅ GOOD: Specific exception with context
throw new ValidationException(
    'Invalid model ID format',
    0,
    null,
    ['model_id' => $modelId, 'expected_format' => 'string']
);

// ❌ BAD: Generic exception without context
throw new \Exception('Error');
```

---

## Service Layer Error Handling

### Validation Errors

```php
public function create(array $data): Product
{
    // Fail fast: Validate at method entry
    if (empty($data['name'])) {
        throw new \InvalidArgumentException('Product name is required');
    }
    
    if ($this->isDuplicateName($data['name'])) {
        throw new \InvalidArgumentException('Product name already exists');
    }
    
    if ($data['price'] > $this->getMaxAllowedPrice()) {
        throw new \InvalidArgumentException(
            sprintf('Price exceeds maximum allowed: %d', $this->getMaxAllowedPrice())
        );
    }
    
    return $this->repository->create($data);
}
```

### External Service Errors

```php
public function syncWithExternalApi(Product $product): array
{
    try {
        $response = $this->externalSdk->updateProduct($product->external_id, [
            'name' => $product->name,
            'price' => $product->price,
        ]);
        
        return $response;
    } catch (\GuzzleHttp\Exception\RequestException $e) {
        // Log the error with context
        $this->logger->log(
            operation: 'product.sync_failed',
            actor: auth()->user(),
            before: [],
            after: [],
            metadata: [
                'error' => $e->getMessage(),
                'product_id' => $product->id,
                'status_code' => $e->getResponse()?->getStatusCode(),
            ]
        );
        
        // Don't expose internal details to caller
        throw new \RuntimeException('Failed to sync with external service');
    }
}
```

### Guard Clauses (Fail Fast Pattern)

```php
public function processPayment(Order $order, float $amount): void
{
    // Guard: Fail fast if preconditions not met
    if ($amount <= 0) {
        throw new \InvalidArgumentException('Amount must be greater than zero');
    }
    
    if ($order->status !== 'pending') {
        throw new \InvalidArgumentException('Order must be in pending status');
    }
    
    if ($order->total !== $amount) {
        throw new \InvalidArgumentException('Amount does not match order total');
    }
    
    // Proceed with payment processing
    $this->paymentGateway->charge($order, $amount);
}
```

---

## Controller Error Handling

### Let FormRequest Handle Validation

```php
// ✅ GOOD: FormRequest automatically handles validation
public function store(StoreProductRequest $request): JsonResponse
{
    // If validation fails, Laravel automatically returns 422 with errors
    // No need to manually handle validation errors here
    
    $product = $this->service->create($request->validated());
    return ProductResource::make($product)->response()->setStatusCode(201);
}
```

### Handle Service Exceptions

```php
public function store(StoreProductRequest $request): JsonResponse
{
    try {
        $product = $this->service->create($request->validated());
        return ProductResource::make($product)->response()->setStatusCode(201);
    } catch (\InvalidArgumentException $e) {
        // Business logic error - return 400
        return ApiResponse::error(
            code: 'product.invalid_data',
            message: $e->getMessage(),
            status: 400
        );
    } catch (\RuntimeException $e) {
        // External service error - return 503
        return ApiResponse::error(
            code: 'product.service_unavailable',
            message: 'Service temporarily unavailable',
            status: 503
        );
    }
}
```

### SDK Exception Handling

```php
public function infer(LmStudioInferenceRequest $request): JsonResponse
{
    if (! $this->featureEnabled()) {
        return ApiResponse::error(
            code: 'lmstudio.disabled',
            message: 'LM Studio integration is disabled.',
            status: 503
        );
    }

    try {
        $result = $this->inferenceService->start($request->toDto());
    } catch (LmStudioException $exception) {
        return ApiResponse::error(
            code: 'lmstudio.error',
            message: $exception->getMessage(),
            meta: $exception->getContext(),
            status: 502
        );
    }

    return ApiResponse::success(
        code: 'lmstudio.infer.accepted',
        message: 'Inference started successfully.',
        data: $result,
        status: 202
    );
}
```

### Error Response Helper Methods

```php
final class CategoryController extends Controller
{
    private function errorFromException(\Exception $exception): JsonResponse
    {
        $code = match (true) {
            $exception instanceof \InvalidArgumentException => 'category.invalid_data',
            $exception instanceof \RuntimeException => 'category.service_error',
            default => 'category.error',
        };
        
        $status = match (true) {
            $exception instanceof \InvalidArgumentException => 400,
            $exception instanceof \RuntimeException => 503,
            default => 500,
        };
        
        return ApiResponse::error(
            code: $code,
            message: $exception->getMessage(),
            status: $status
        );
    }
}
```

---

## Frontend Error Handling

### Axios Error Handling

```typescript
try {
    const response = await axios.post('/api/v1/products', productData);
    toast.success('Product created successfully');
    // Handle success...
} catch (error) {
    if (axios.isAxiosError(error)) {
        const response = error.response;
        
        if (response) {
            // Server responded with error
            const { code, message, meta } = response.data;
            
            if (response.status === 422) {
                // Validation errors
                const errors = meta?.errors || {};
                Object.entries(errors).forEach(([field, messages]) => {
                    toast.error(`${field}: ${Array.isArray(messages) ? messages[0] : messages}`);
                });
            } else if (response.status === 503) {
                // Service unavailable
                toast.error(message || 'Service temporarily unavailable', {
                    autoClose: false // Sticky for fatal errors
                });
            } else {
                // Other errors
                toast.error(message || 'An error occurred');
            }
        } else {
            // Network error
            toast.error('Network error. Please check your connection.');
        }
    } else {
        // Unknown error
        toast.error('An unexpected error occurred');
    }
}
```

### Error Response Structure

```typescript
interface ApiErrorResponse {
    ok: false;
    code: string;        // e.g., 'product.invalid_data'
    status: number;     // HTTP status code
    message: string;     // Human-readable message
    data: {};           // Empty object for errors
    meta?: {
        errors?: Record<string, string[]>; // Validation errors
        [key: string]: unknown;            // Additional context
    };
}
```

### Centralized Error Handler

```typescript
// utils/errorHandler.ts
export function handleApiError(error: unknown): void {
    if (axios.isAxiosError(error)) {
        const response = error.response;
        
        if (!response) {
            toast.error('Network error. Please check your connection.');
            return;
        }
        
        const { code, message, meta } = response.data;
        
        switch (response.status) {
            case 400:
                toast.error(message || 'Invalid request');
                break;
            case 401:
                toast.error('Please log in to continue');
                // Redirect to login
                break;
            case 403:
                toast.error('You do not have permission to perform this action');
                break;
            case 404:
                toast.error('Resource not found');
                break;
            case 422:
                // Validation errors
                const errors = meta?.errors || {};
                Object.entries(errors).forEach(([field, messages]) => {
                    toast.error(`${field}: ${Array.isArray(messages) ? messages[0] : messages}`);
                });
                break;
            case 503:
                toast.error(message || 'Service temporarily unavailable', {
                    autoClose: false
                });
                break;
            default:
                toast.error(message || 'An error occurred');
        }
    } else {
        toast.error('An unexpected error occurred');
    }
}

// Usage
try {
    await axios.post('/api/v1/products', data);
} catch (error) {
    handleApiError(error);
}
```

---

## Error Logging

### Logging Service Errors

```php
public function syncWithExternalApi(Product $product): array
{
    try {
        return $this->externalSdk->updateProduct($product->external_id, $data);
    } catch (\GuzzleHttp\Exception\RequestException $e) {
        // Log to external channel (14d retention)
        Log::channel('external')->error('External API sync failed', [
            'operation' => 'product.sync',
            'product_id' => $product->id,
            'endpoint' => $this->externalSdk->getEndpoint(),
            'status_code' => $e->getResponse()?->getStatusCode(),
            'error' => $e->getMessage(),
        ]);
        
        throw new \RuntimeException('Failed to sync with external service');
    }
}
```

### Logging Domain Errors

```php
public function delete(Product $product): void
{
    try {
        $this->repository->delete($product);
        
        // Log domain mutation (30d retention)
        $this->logger->log(
            operation: 'product.deleted',
            actor: auth()->user(),
            before: $product->toArray(),
            after: [],
            metadata: ['deleted_at' => now()->toIso8601String()]
        );
    } catch (\Exception $e) {
        // Log error
        Log::error('Failed to delete product', [
            'product_id' => $product->id,
            'error' => $e->getMessage(),
        ]);
        
        throw $e;
    }
}
```

---

## HTTP Status Code Guidelines

### Status Code Mapping

| Exception Type | HTTP Status | Use Case |
|----------------|-------------|----------|
| `InvalidArgumentException` | 400 | Invalid input data, business rule violation |
| `ValidationException` | 422 | FormRequest validation failed |
| `UnauthenticatedException` | 401 | Missing or invalid authentication |
| `AuthorizationException` | 403 | Authenticated but not authorized |
| `ModelNotFoundException` | 404 | Resource not found |
| `RuntimeException` (external) | 502/503 | External service error |
| `RuntimeException` (internal) | 500 | Internal server error |

### Status Code Examples

```php
// 400 - Bad Request (business logic error)
return ApiResponse::error(
    code: 'product.invalid_price',
    message: 'Price must be greater than zero',
    status: 400
);

// 401 - Unauthorized
return ApiResponse::error(
    code: 'auth.required',
    message: 'Authentication required',
    status: 401
);

// 403 - Forbidden
return ApiResponse::error(
    code: 'product.delete_forbidden',
    message: 'You do not have permission to delete this product',
    status: 403
);

// 404 - Not Found
return ApiResponse::error(
    code: 'product.not_found',
    message: 'Product not found',
    status: 404
);

// 422 - Validation Failed (FormRequest handles this automatically)
// No manual handling needed - Laravel returns 422 automatically

// 502 - Bad Gateway (external service error)
return ApiResponse::error(
    code: 'external.service_error',
    message: 'External service temporarily unavailable',
    status: 502
);

// 503 - Service Unavailable (feature disabled, maintenance)
return ApiResponse::error(
    code: 'lmstudio.disabled',
    message: 'LM Studio integration is disabled',
    status: 503
);
```

---

## Best Practices

### ✅ DO

- **Fail fast** - Validate and throw early in method execution
- **Use specific exceptions** - `InvalidArgumentException` for validation, `RuntimeException` for system errors
- **Include context** - Add relevant data to exception messages and context
- **Log errors** - Always log errors before re-throwing or returning error responses
- **Use guard clauses** - Check preconditions at method entry
- **Return structured errors** - Use `ApiResponse::error()` for consistent format
- **Mask sensitive data** - Never expose passwords, tokens, or internal details in error messages

### ❌ DON'T

- **Don't silently fail** - Always throw exceptions or return error responses
- **Don't return null** - Throw exceptions instead of returning null for error cases
- **Don't expose internals** - Don't leak internal implementation details in error messages
- **Don't catch and ignore** - Always handle exceptions appropriately
- **Don't use generic exceptions** - Use specific exception types
- **Don't log sensitive data** - Mask passwords, tokens, and secrets in logs

---

## Related Documentation

- [Defensive Programming Principles](../architecture/principles.md#20-defensive-programming) - Fail fast principle
- [API Response Standards](../reference/standards.md#api-response-standards) - Error response format
- [Audit Logging Standards](../reference/standards.md#audit-logging-standards) - Error logging requirements
- [Development Guidelines](../development/guidelines.md#error-handling-patterns) - More examples

