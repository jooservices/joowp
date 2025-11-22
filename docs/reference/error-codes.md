# Error Code Registry

This document defines the standard error codes used throughout the application for consistent error handling and user messaging.

## Format

Error codes follow the pattern: `{service}.{resource}.{operation}_{result}`

- **service**: The service name (e.g., `wordpress`, `lmstudio`, `external`)
- **resource**: The resource being accessed (e.g., `tags`, `categories`, `models`)
- **operation**: The operation being performed (e.g., `list`, `create`, `update`, `delete`)
- **result**: The outcome (e.g., `failed`, `timeout`, `unauthorized`)

## WordPress Errors

### Tags
- `wordpress.tags.list` - Successfully retrieved tags
- `wordpress.tags.list_failed` - Failed to retrieve tags
- `wordpress.tags.create` - Successfully created tag
- `wordpress.tags.create_failed` - Failed to create tag
- `wordpress.tags.update` - Successfully updated tag
- `wordpress.tags.update_failed` - Failed to update tag
- `wordpress.tags.delete` - Successfully deleted tag
- `wordpress.tags.delete_failed` - Failed to delete tag

### Categories
- `wordpress.categories.list` - Successfully retrieved categories
- `wordpress.categories.list_failed` - Failed to retrieve categories
- `wordpress.categories.create` - Successfully created category
- `wordpress.categories.create_failed` - Failed to create category
- `wordpress.categories.update` - Successfully updated category
- `wordpress.categories.update_failed` - Failed to update category
- `wordpress.categories.delete` - Successfully deleted category
- `wordpress.categories.delete_failed` - Failed to delete category
- `wordpress.categories.parents` - Successfully retrieved parent categories

### Authentication
- `wordpress.token.exchange` - Successfully exchanged token
- `wordpress.token.exchange_failed` - Failed to exchange token
- `wordpress.token.invalid` - Invalid or expired token

### Circuit Breaker
- `wordpress.circuit_breaker_open` - WordPress service circuit breaker is open

## LM Studio Errors

### Models
- `lmstudio.models.list` - Successfully retrieved models
- `lmstudio.models.list_failed` - Failed to retrieve models

### Inference
- `lmstudio.infer.success` - Successfully completed inference
- `lmstudio.infer.failed` - Failed to complete inference
- `lmstudio.infer.timeout` - Inference request timed out

### Health Check
- `lmstudio.health.check` - Health check successful
- `lmstudio.health.check_failed` - Health check failed

### Connection
- `lmstudio.connection_failed` - Failed to connect to LM Studio
- `lmstudio.timeout` - Request to LM Studio timed out

### Circuit Breaker
- `lmstudio.circuit_breaker_open` - LM Studio service circuit breaker is open

## General External Service Errors

### Service Errors
- `external.service_error` - Generic external service error
- `external.connection_failed` - Failed to connect to external service
- `external.timeout` - Request to external service timed out
- `external.service_unavailable` - External service is unavailable

### Circuit Breaker
- `external.circuit_breaker_open` - External service circuit breaker is open

## HTTP Status Code Mapping

| Error Code Pattern | HTTP Status | Meaning |
|-------------------|-------------|---------|
| `*.list_failed` (500) | 502 | Bad Gateway - External server error |
| `*.list_failed` (503) | 503 | Service Unavailable |
| `*.timeout` | 504 | Gateway Timeout |
| `*.connection_failed` | 502 | Bad Gateway - Cannot reach service |
| `*.circuit_breaker_open` | 503 | Service Unavailable - Circuit breaker open |
| `*.unauthorized` | 401 | Unauthorized |
| `*.forbidden` | 403 | Forbidden |
| `*.not_found` | 404 | Not Found |

## Usage Examples

### Backend Controller

```php
try {
    $tags = $this->service->list($filters);
    return ApiResponse::success(
        code: 'wordpress.tags.list',
        message: 'Tags retrieved successfully.',
        data: ['items' => $tags]
    );
} catch (WordPressRequestException $exception) {
    return ErrorHandler::handleExternalServiceError(
        exception: $exception,
        errorCode: 'wordpress.tags.list_failed',
        additionalMeta: ['filters' => $filters]
    );
}
```

### Frontend Error Handling

```typescript
try {
    const response = await window.axios.get('/api/v1/wordpress/tags');
    if (!response.data.ok) {
        handleApiError(response.data);
        return;
    }
    // Handle success
} catch (error: unknown) {
    handleApiError(error);
}
```

## Adding New Error Codes

When adding new error codes:

1. Follow the naming convention: `{service}.{resource}.{operation}_{result}`
2. Document the error code in this file
3. Update error code mapping if needed
4. Ensure user-friendly messages are defined in `ErrorHandler::getUserFriendlyMessage()`
5. Add tests for error handling scenarios

