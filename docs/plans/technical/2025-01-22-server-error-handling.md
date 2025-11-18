# Server Error Handling Strategy

**Status:** Pending  
**Created:** 2025-01-22  
**Last Updated:** 2025-01-22

## Summary

Implement a comprehensive, application-wide strategy for handling server errors, external API failures, network issues, and service unavailability. This ensures graceful degradation, clear user feedback, and robust error recovery across all services (WordPress, LM Studio, and future integrations).

## Problem Statement

Currently, server errors and external API failures are handled inconsistently across the application:

- **WordPress API errors** - Some controllers handle `WordPressRequestException`, but not all
- **LM Studio API errors** - Basic error handling exists, but could be improved
- **Network errors** - No retry logic or circuit breaker patterns
- **Timeout handling** - No explicit timeout configuration or handling
- **Frontend error handling** - Inconsistent error message display
- **Error monitoring** - Errors are logged but not systematically monitored
- **Graceful degradation** - Limited fallback behavior when services are unavailable

## Business Rules

1. **Graceful Degradation** - Application should continue functioning when external services are unavailable
2. **Clear Error Messages** - Users should receive clear, actionable error messages (no technical jargon)
3. **Proper HTTP Status Codes** - Return appropriate status codes (502 Bad Gateway, 503 Service Unavailable, 504 Gateway Timeout)
4. **Structured Error Responses** - All errors must follow `ApiResponse::error()` format
5. **Comprehensive Logging** - All server errors must be logged with context for monitoring
6. **No Silent Failures** - Errors should be visible to users, not hidden
7. **Retry Logic** - Implement retry strategies for transient failures
8. **Circuit Breaker** - Prevent cascading failures with circuit breaker pattern
9. **Timeout Management** - Configure appropriate timeouts for all external requests
10. **Monitoring & Alerting** - Track error rates and alert on critical failures

## Implementation Tasks

### Phase 1: Error Handling Foundation

- [ ] Create base exception classes for external services
  - DoD: `ExternalServiceException` base class with context support
  - DoD: `ConnectionException` for network errors
  - DoD: `TimeoutException` for timeout errors
  - DoD: `ServiceUnavailableException` for 503 errors
  - DoD: All exceptions include context (service name, endpoint, request details)
  - Estimated: 2 hours

- [ ] Standardize error response format
  - DoD: Document `ApiResponse::error()` usage patterns
  - DoD: Define standard error codes for each service
  - DoD: Create error code registry (e.g., `wordpress.*`, `lmstudio.*`, `external.*`)
  - DoD: Include `source_status` in error meta for external API errors
  - Estimated: 1 hour

- [ ] Create centralized error handler utility
  - DoD: `ErrorHandler` class with static methods
  - DoD: `handleExternalServiceError()` method
  - DoD: `mapHttpStatus()` method for status code mapping
  - DoD: `getUserFriendlyMessage()` method for error messages
  - Estimated: 2 hours

### Phase 2: HTTP Client Configuration

- [ ] Configure Guzzle HTTP client with timeouts and retries
  - DoD: Set connection timeout (5 seconds)
  - DoD: Set request timeout (30 seconds for normal requests, 60 seconds for long-running)
  - DoD: Implement retry logic with exponential backoff (max 3 retries)
  - DoD: Retry only on transient errors (5xx, connection errors, timeouts)
  - DoD: Configure retry delay: 1s, 2s, 4s
  - Estimated: 3 hours

- [ ] Create HTTP client factory
  - DoD: `HttpClientFactory` class
  - DoD: `forWordPress()` method with WordPress-specific config
  - DoD: `forLmStudio()` method with LM Studio-specific config
  - DoD: `forGeneral()` method for general API calls
  - DoD: All clients use consistent timeout and retry configuration
  - Estimated: 2 hours

### Phase 3: Circuit Breaker Pattern

- [ ] Implement circuit breaker for external services
  - DoD: `CircuitBreaker` class with state management (closed, open, half-open)
  - DoD: Configurable failure threshold (default: 5 failures)
  - DoD: Configurable timeout period (default: 60 seconds)
  - DoD: Automatic state transitions
  - DoD: Per-service circuit breaker instances
  - Estimated: 4 hours

- [ ] Integrate circuit breaker with WordPress SDK
  - DoD: Wrap WordPress API calls with circuit breaker
  - DoD: Return appropriate error when circuit is open
  - DoD: Log circuit breaker state changes
  - Estimated: 2 hours

- [ ] Integrate circuit breaker with LM Studio SDK
  - DoD: Wrap LM Studio API calls with circuit breaker
  - DoD: Return appropriate error when circuit is open
  - DoD: Log circuit breaker state changes
  - Estimated: 2 hours

### Phase 4: Controller Error Handling

- [ ] Update all WordPress controllers with consistent error handling
  - DoD: `TagController` - Handle `WordPressRequestException` in all methods
  - DoD: `CategoryController` - Handle `WordPressRequestException` in all methods
  - DoD: Future WordPress controllers follow same pattern
  - DoD: Use `ErrorHandler::handleExternalServiceError()` utility
  - DoD: Return `ApiResponse::error()` with appropriate status codes
  - Estimated: 3 hours

- [ ] Update LM Studio controllers with improved error handling
  - DoD: `LmStudioController` - Enhanced error handling for all methods
  - DoD: Handle `LmStudioException` with proper status codes
  - DoD: Handle circuit breaker open state
  - DoD: Use `ErrorHandler::handleExternalServiceError()` utility
  - Estimated: 2 hours

- [ ] Create base controller trait for error handling
  - DoD: `HandlesExternalServiceErrors` trait
  - DoD: `handleServiceError()` method
  - DoD: `handleCircuitBreakerOpen()` method
  - DoD: Controllers can use trait for consistent error handling
  - Estimated: 2 hours

### Phase 5: Frontend Error Handling

- [ ] Create centralized error handler utility (TypeScript)
  - DoD: `utils/errorHandler.ts` with `handleApiError()` function
  - DoD: Handle structured `ApiResponse::error()` format
  - DoD: Handle network errors (no response)
  - DoD: Handle timeout errors
  - DoD: Display user-friendly messages
  - DoD: Show retry buttons for transient errors
  - Estimated: 3 hours

- [ ] Update all Vue components with consistent error handling
  - DoD: `Tags/Index.vue` - Use centralized error handler
  - DoD: `Categories/Index.vue` - Use centralized error handler
  - DoD: `LmStudio` components - Use centralized error handler
  - DoD: Show empty states with error messages
  - DoD: Provide retry functionality
  - Estimated: 4 hours

- [ ] Create error display components
  - DoD: `ErrorAlert.vue` component for displaying errors
  - DoD: `EmptyState.vue` component with error message support
  - DoD: `RetryButton.vue` component for retry actions
  - Estimated: 2 hours

### Phase 6: Retry Logic & Resilience

- [ ] Implement retry middleware for HTTP client
  - DoD: Retry on 5xx errors (except 501, 505)
  - DoD: Retry on connection errors
  - DoD: Retry on timeout errors
  - DoD: Exponential backoff with jitter
  - DoD: Max retries: 3
  - DoD: Retry delay: 1s, 2s, 4s
  - Estimated: 3 hours

- [ ] Add request/response logging for debugging
  - DoD: Log request details (method, URL, headers, body)
  - DoD: Log response details (status, headers, body)
  - DoD: Log only on errors or when debug mode is enabled
  - DoD: Mask sensitive data (tokens, passwords)
  - Estimated: 2 hours

### Phase 7: Monitoring & Alerting

- [ ] Enhance error logging with structured data
  - DoD: Log service name, endpoint, method, status code
  - DoD: Log request/response details (masked)
  - DoD: Log circuit breaker state changes
  - DoD: Log retry attempts
  - DoD: Use appropriate log levels (error, warning, info)
  - Estimated: 2 hours

- [ ] Create error metrics tracking
  - DoD: Track error rates per service
  - DoD: Track error types (5xx, timeout, connection)
  - DoD: Track circuit breaker state changes
  - DoD: Store metrics in database or monitoring system
  - Estimated: 3 hours

- [ ] Set up alerting for critical errors
  - DoD: Alert on high error rates (>10% in 5 minutes)
  - DoD: Alert on circuit breaker opening
  - DoD: Alert on service downtime (>5 minutes)
  - DoD: Configure alert channels (email, Slack, etc.)
  - Estimated: 2 hours

### Phase 8: Testing

- [ ] Create unit tests for error handling utilities
  - DoD: Test `ErrorHandler` class methods
  - DoD: Test `CircuitBreaker` state transitions
  - DoD: Test HTTP client retry logic
  - DoD: Test timeout handling
  - Estimated: 4 hours

- [ ] Create feature tests for error scenarios
  - DoD: Test controller error handling
  - DoD: Test circuit breaker integration
  - DoD: Test retry logic
  - DoD: Mock external service failures
  - Estimated: 4 hours

- [ ] Create frontend tests for error states
  - DoD: Test error message display
  - DoD: Test empty state with error
  - DoD: Test retry functionality
  - Estimated: 2 hours

### Phase 9: Documentation

- [ ] Document error handling patterns
  - DoD: Update `ai-workflow/guides/error-handling.md` with new patterns
  - DoD: Document circuit breaker usage
  - DoD: Document retry logic configuration
  - DoD: Document error code registry
  - Estimated: 2 hours

- [ ] Create error handling decision tree
  - DoD: Flowchart for error handling decisions
  - DoD: When to retry vs. when to fail fast
  - DoD: When to use circuit breaker
  - Estimated: 1 hour

## Error Response Format

### Success Response
```json
{
  "ok": true,
  "code": "service.resource.operation",
  "status": 200,
  "message": "Operation completed successfully.",
  "data": {...},
  "meta": {...}
}
```

### Error Response (External Service Error)
```json
{
  "ok": false,
  "code": "service.resource.operation_failed",
  "status": 502,
  "message": "Unable to complete operation. The service may be temporarily unavailable.",
  "data": null,
  "meta": {
    "source_status": 500,
    "error_message": "External API request failed: ...",
    "service": "wordpress",
    "endpoint": "/wp/v2/tags",
    "retry_after": 60
  }
}
```

### Error Response (Circuit Breaker Open)
```json
{
  "ok": false,
  "code": "service.circuit_breaker_open",
  "status": 503,
  "message": "Service is temporarily unavailable. Please try again later.",
  "data": null,
  "meta": {
    "service": "wordpress",
    "circuit_state": "open",
    "retry_after": 60
  }
}
```

## HTTP Status Code Mapping

| Error Type | HTTP Status | Meaning |
|------------|-------------|---------|
| External service 500 | 502 Bad Gateway | External server error |
| External service 503 | 503 Service Unavailable | External service temporarily unavailable |
| Connection timeout | 504 Gateway Timeout | External service not responding |
| Network error | 502 Bad Gateway | Cannot reach external service |
| Circuit breaker open | 503 Service Unavailable | Service is down, circuit breaker preventing requests |
| Invalid request (400) | 400 Bad Request | Invalid request (pass through) |
| Unauthorized (401) | 401 Unauthorized | Authentication required (pass through) |
| Not found (404) | 404 Not Found | Resource not found (pass through) |
| Validation error | 422 Unprocessable Entity | Validation failed (Laravel automatic) |

## Error Code Registry

### WordPress Errors
- `wordpress.tags.list_failed`
- `wordpress.tags.create_failed`
- `wordpress.tags.update_failed`
- `wordpress.tags.delete_failed`
- `wordpress.categories.list_failed`
- `wordpress.categories.create_failed`
- `wordpress.categories.update_failed`
- `wordpress.categories.delete_failed`
- `wordpress.circuit_breaker_open`

### LM Studio Errors
- `lmstudio.infer.failed`
- `lmstudio.models.list_failed`
- `lmstudio.connection_failed`
- `lmstudio.timeout`
- `lmstudio.circuit_breaker_open`

### General External Service Errors
- `external.service_error`
- `external.connection_failed`
- `external.timeout`
- `external.circuit_breaker_open`

## Circuit Breaker Configuration

### Default Configuration
- **Failure Threshold:** 5 failures
- **Timeout Period:** 60 seconds
- **Half-Open Retry:** 1 request
- **Success Threshold (Half-Open):** 1 success

### Per-Service Configuration
- **WordPress:** 5 failures, 60s timeout
- **LM Studio:** 3 failures, 30s timeout (more sensitive)

## Retry Strategy

### Retry Conditions
- ✅ 5xx errors (except 501, 505)
- ✅ Connection errors
- ✅ Timeout errors
- ❌ 4xx errors (client errors, no retry)
- ❌ 501 Not Implemented
- ❌ 505 HTTP Version Not Supported

### Retry Configuration
- **Max Retries:** 3
- **Retry Delays:** 1s, 2s, 4s (exponential backoff)
- **Jitter:** ±20% random jitter to prevent thundering herd

## Timeout Configuration

### Connection Timeout
- **Default:** 5 seconds
- **Purpose:** Time to establish TCP connection

### Request Timeout
- **Default:** 30 seconds
- **Long-running requests:** 60 seconds (e.g., LM Studio inference)
- **Purpose:** Time to receive complete response

## User-Facing Error Messages

### List Operations
- **Message:** "Unable to retrieve data. The service may be temporarily unavailable."
- **Action:** Show empty state with retry button

### Create/Update Operations
- **Message:** "Unable to save changes. The service may be temporarily unavailable."
- **Action:** Keep form data, show retry button

### Delete Operations
- **Message:** "Unable to delete. The service may be temporarily unavailable."
- **Action:** Show error, allow retry

### Circuit Breaker Open
- **Message:** "Service is temporarily unavailable. Please try again in a few minutes."
- **Action:** Disable retry button, show estimated retry time

## Technical Notes

### Exception Handling Pattern
```php
try {
    $result = $this->service->operation($params);
    return ApiResponse::success(...);
} catch (ExternalServiceException $exception) {
    return ErrorHandler::handleExternalServiceError($exception, 'service.resource.operation_failed');
} catch (CircuitBreakerOpenException $exception) {
    return ApiResponse::error(
        code: 'service.circuit_breaker_open',
        message: 'Service is temporarily unavailable. Please try again later.',
        meta: [
            'service' => $exception->getService(),
            'circuit_state' => 'open',
            'retry_after' => $exception->getRetryAfter(),
        ],
        status: 503
    );
}
```

### Frontend Error Handling Pattern
```typescript
try {
    const response = await window.axios.get('/api/v1/service/resource');
    if (!response.data.ok) {
        handleApiError(response.data);
        return;
    }
    // Handle success
} catch (error: unknown) {
    handleApiError(error);
}
```

### Circuit Breaker Usage
```php
$circuitBreaker = CircuitBreaker::for('wordpress');

try {
    $result = $circuitBreaker->call(function () use ($sdk) {
        return $sdk->tags();
    });
} catch (CircuitBreakerOpenException $exception) {
    // Handle circuit breaker open
}
```

## Risks & Mitigations

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Too many retries causing load | High | Medium | Limit retries, use exponential backoff, implement circuit breaker |
| Circuit breaker false positives | Medium | Low | Tune failure threshold, monitor metrics |
| Users see technical error messages | Medium | Low | Use user-friendly messages, hide technical details |
| Error handling breaks existing functionality | High | Low | Comprehensive testing, gradual rollout |
| Too many error logs | Low | Medium | Use appropriate log levels, implement log rotation |
| Performance impact from retries | Medium | Low | Limit retries, use async retries where possible |

## Dependencies

- Guzzle HTTP client
- Laravel logging system
- Vue 3, TypeScript, Axios
- `ApiResponse` helper class
- Existing exception classes (`WordPressRequestException`, `LmStudioException`)

## Acceptance Criteria

1. ✅ All external service errors are handled gracefully
2. ✅ Error responses follow `ApiResponse::error()` format
3. ✅ Appropriate HTTP status codes are returned (502, 503, 504)
4. ✅ User-friendly error messages are displayed
5. ✅ Frontend handles error responses without breaking
6. ✅ Empty states are shown when data cannot be retrieved
7. ✅ Circuit breaker prevents cascading failures
8. ✅ Retry logic handles transient failures
9. ✅ All errors are logged with context
10. ✅ Error metrics are tracked and monitored
11. ✅ Tests cover error handling scenarios
12. ✅ Documentation is updated with new patterns

## Estimated Total Time

- Error handling foundation: ~5 hours
- HTTP client configuration: ~5 hours
- Circuit breaker implementation: ~8 hours
- Controller error handling: ~7 hours
- Frontend error handling: ~9 hours
- Retry logic & resilience: ~5 hours
- Monitoring & alerting: ~7 hours
- Testing: ~10 hours
- Documentation: ~3 hours
- **Total: ~59 hours**

## Implementation Priority

### Phase 1 (Critical - Week 1)
- Error handling foundation
- HTTP client configuration
- Controller error handling (WordPress, LM Studio)

### Phase 2 (High Priority - Week 2)
- Frontend error handling
- Retry logic
- Basic monitoring

### Phase 3 (Medium Priority - Week 3)
- Circuit breaker implementation
- Enhanced monitoring & alerting
- Comprehensive testing

### Phase 4 (Low Priority - Week 4)
- Documentation updates
- Performance optimization
- Advanced features

