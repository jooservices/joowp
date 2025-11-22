# WordPress API Error Handling & Server Downtime

**Status:** Pending  
**Created:** 2025-01-22  
**Last Updated:** 2025-01-22

## Summary

Implement comprehensive error handling for WordPress API requests to gracefully handle server downtime, network issues, and API errors. This ensures the application provides clear feedback to users when WordPress is unavailable and prevents unhandled exceptions from breaking the UI.

## Problem Statement

Currently, when WordPress API is unavailable (e.g., Cloudflare down, server maintenance, network issues), the application throws unhandled `WordPressRequestException` exceptions, causing:
- 500 Internal Server Error responses
- Poor user experience with unclear error messages
- No graceful degradation or fallback behavior
- Frontend receives unhandled exceptions instead of structured error responses

## Business Rules

1. **Graceful Degradation** - Application should handle WordPress unavailability gracefully
2. **Clear Error Messages** - Users should receive clear, actionable error messages
3. **Proper HTTP Status Codes** - Return appropriate status codes (502 Bad Gateway, 503 Service Unavailable)
4. **Structured Error Responses** - All errors should follow `ApiResponse::error()` format
5. **Logging** - All WordPress API failures should be logged for monitoring
6. **No Silent Failures** - Errors should be visible to users, not hidden

## Implementation Tasks

### Phase 1: Controller Error Handling

- [ ] Update `TagController` to handle `WordPressRequestException`
  - DoD: All methods (`index`, `store`, `update`, `destroy`) wrapped in try-catch
  - DoD: Returns `ApiResponse::error()` with appropriate status codes
  - DoD: Includes `source_status` from WordPress API in error meta
  - DoD: Provides user-friendly error messages
  - Estimated: 1 hour

- [ ] Update `CategoryController` to handle `WordPressRequestException`
  - DoD: All methods (`index`, `store`, `update`, `destroy`, `parents`) wrapped in try-catch
  - DoD: Returns `ApiResponse::error()` with appropriate status codes
  - DoD: Includes `source_status` from WordPress API in error meta
  - DoD: Provides user-friendly error messages
  - Estimated: 1 hour

### Phase 2: Frontend Error Handling

- [ ] Update `Tags/Index.vue` to handle API errors gracefully
  - DoD: Displays error messages from `ApiResponse::error()` format
  - DoD: Shows user-friendly message when WordPress is unavailable
  - DoD: Handles 502/503 status codes appropriately
  - DoD: Prevents UI from breaking on API errors
  - DoD: Shows empty state with error message instead of crashing
  - Estimated: 2 hours

- [ ] Update `Categories/Index.vue` to handle API errors gracefully
  - DoD: Same error handling as Tags page
  - DoD: Consistent error message display
  - Estimated: 1 hour

### Phase 3: Error Response Standardization

- [ ] Define standard error codes for WordPress API failures
  - DoD: `wordpress.tags.list_failed`
  - DoD: `wordpress.tags.create_failed`
  - DoD: `wordpress.tags.update_failed`
  - DoD: `wordpress.tags.delete_failed`
  - DoD: `wordpress.categories.list_failed`
  - DoD: `wordpress.categories.create_failed`
  - DoD: `wordpress.categories.update_failed`
  - DoD: `wordpress.categories.delete_failed`
  - Estimated: 30 minutes

- [ ] Document error response format
  - DoD: Update API documentation with error response examples
  - DoD: Document status code meanings (502, 503, etc.)
  - Estimated: 30 minutes

### Phase 4: Monitoring & Logging

- [ ] Ensure WordPress API errors are logged
  - DoD: Verify `WordPressRequestException` is logged in external channel
  - DoD: Log includes request details, response status, error message
  - DoD: Logs are searchable for monitoring
  - Estimated: 30 minutes

- [ ] Add error rate monitoring (optional)
  - DoD: Track WordPress API error rates
  - DoD: Alert on high error rates
  - Estimated: 2 hours (optional)

### Phase 5: Testing

- [ ] Add tests for error handling in controllers
  - DoD: Test `TagController` error handling
  - DoD: Test `CategoryController` error handling
  - DoD: Mock `WordPressRequestException` in tests
  - DoD: Verify correct status codes and error messages
  - Estimated: 2 hours

- [ ] Add frontend tests for error states
  - DoD: Test error message display
  - DoD: Test empty state with error
  - Estimated: 1 hour

## Error Response Format

### Success Response
```json
{
  "ok": true,
  "code": "wordpress.tags.list",
  "status": 200,
  "message": "Tags retrieved successfully.",
  "data": {
    "items": [...]
  },
  "meta": {
    "filters": {...}
  }
}
```

### Error Response (Server Down)
```json
{
  "ok": false,
  "code": "wordpress.tags.list_failed",
  "status": 502,
  "message": "Unable to retrieve tags from WordPress. The server may be temporarily unavailable.",
  "data": {
    "items": []
  },
  "meta": {
    "source_status": 500,
    "error_message": "WordPress API request [GET wp/v2/tags] failed: Server error..."
  }
}
```

## HTTP Status Code Mapping

| WordPress API Status | Our Response Status | Meaning |
|---------------------|-------------------|---------|
| 500 Internal Server Error | 502 Bad Gateway | WordPress server error |
| 503 Service Unavailable | 503 Service Unavailable | WordPress temporarily unavailable |
| Connection timeout | 504 Gateway Timeout | WordPress not responding |
| Network error | 502 Bad Gateway | Cannot reach WordPress |
| 400 Bad Request | 400 Bad Request | Invalid request (pass through) |
| 401 Unauthorized | 401 Unauthorized | Authentication required (pass through) |
| 404 Not Found | 404 Not Found | Resource not found (pass through) |

## User-Facing Error Messages

### List Operations
- **Message**: "Unable to retrieve tags from WordPress. The server may be temporarily unavailable."
- **Action**: Show empty state with retry button

### Create Operations
- **Message**: "Unable to create tag in WordPress. The server may be temporarily unavailable."
- **Action**: Keep form data, show retry button

### Update Operations
- **Message**: "Unable to update tag in WordPress. The server may be temporarily unavailable."
- **Action**: Keep form data, show retry button

### Delete Operations
- **Message**: "Unable to delete tag from WordPress. The server may be temporarily unavailable."
- **Action**: Show error, allow retry

## Technical Notes

### Exception Handling Pattern
```php
try {
    $result = $this->service->operation($params);
    return ApiResponse::success(...);
} catch (WordPressRequestException $exception) {
    $sourceStatus = $exception->sourceStatus();
    return ApiResponse::error(
        code: 'wordpress.resource.operation_failed',
        message: 'User-friendly message',
        meta: [
            'source_status' => $sourceStatus,
            'error_message' => $exception->getMessage(),
        ],
        data: null,
        status: $sourceStatus ?? 502
    );
}
```

### Frontend Error Handling Pattern
```typescript
try {
    const response = await window.axios.get('/api/v1/wordpress/tags');
    if (!response.data.ok) {
        // Handle error response
        pushAlert('danger', response.data.message);
        tags.value = [];
    } else {
        tags.value = response.data.data.items;
    }
} catch (error: unknown) {
    // Handle network/exception errors
    pushAlert('danger', extractErrorMessage(error));
    tags.value = [];
}
```

## Risks & Mitigations

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Users see technical error messages | Medium | Low | Use user-friendly messages, hide technical details |
| Error handling breaks existing functionality | High | Low | Comprehensive testing, gradual rollout |
| Too many error logs | Low | Medium | Use appropriate log levels, implement log rotation |
| Users don't know how to retry | Medium | Medium | Provide clear retry buttons and instructions |

## Dependencies

- `WordPressRequestException` from `Modules\Core\Services\WordPress\Exceptions`
- `ApiResponse` helper class
- Frontend error handling utilities

## Acceptance Criteria

1. ✅ All WordPress API endpoints handle `WordPressRequestException` gracefully
2. ✅ Error responses follow `ApiResponse::error()` format
3. ✅ Appropriate HTTP status codes are returned (502, 503, 504)
4. ✅ User-friendly error messages are displayed
5. ✅ Frontend handles error responses without breaking
6. ✅ Empty states are shown when data cannot be retrieved
7. ✅ All errors are logged for monitoring
8. ✅ Tests cover error handling scenarios

## Estimated Total Time

- Controller error handling: ~2 hours
- Frontend error handling: ~3 hours
- Error response standardization: ~1 hour
- Monitoring & logging: ~30 minutes
- Testing: ~3 hours
- **Total: ~9.5 hours**

