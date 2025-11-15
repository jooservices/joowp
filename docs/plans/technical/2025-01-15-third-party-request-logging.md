# Third-Party Request Logging Implementation

**Owner:** Unassigned  
**Status:** Planning  
**Priority:** Medium  
**Created:** 2025-01-15  
**Related:** [Core HTTP Client Plan](./2025-11-14-core-http-client.md)

---

## Objective

Implement standardized third-party request logging with standard format to:
- Log complete request/response information for all third-party API calls
- Enable debugging, audit trail, and request replay
- Separate from General Log (local log) for easier querying/filtering
- Ensure consistent format across all SDKs (WordPress, LM Studio, future SDKs)

---

## Background

### Current State

Currently, SDKs are logging to the `external` channel with inconsistent formats:

**WordPress SDK:**
- Logs dispatch, failure, and response separately
- Missing full payload/response
- Non-standard format

**LM Studio SDK:**
- Logs telemetry with `payload_keys` (missing full payload)
- Missing full response body
- Non-standard format

**LmStudioController:**
- Logs errors to `external` channel
- Flexible format, no standard structure

### Problem

1. **Inconsistent:** Each SDK uses different log format
2. **Missing information:** No full payload/response for debugging
3. **Hard to query:** Non-standard format makes searching/filtering difficult
4. **Hard to replay:** Insufficient information to replay requests
5. **Mixed with General Log:** Third-party requests mixed with general errors/info

---

## Solution

### 1. Create New Log Channel

**Channel:** `third-party-requests`  
**File:** `storage/logs/third-party-requests.log`  
**Retention:** 14 days (configurable)

### 2. Define Standard Format

**MANDATORY Fields:**
```php
[
    'timestamp' => '2025-01-15T10:30:45.123Z',  // ISO 8601
    'service' => 'lmstudio|wordpress|twitter|...',
    'method' => 'GET|POST|PUT|DELETE|PATCH',
    'endpoint' => '/v1/models',  // Full endpoint path
    'base_url' => 'http://localhost:1234',
    'status_code' => 200,
    'duration_ms' => 125,
    'success' => true|false,
]
```

**REQUEST Fields (MANDATORY):**
```php
'request' => [
    'headers' => [...],  // Sanitized (mask tokens/passwords)
    'query_params' => [...],  // Query string parameters
    'payload' => [...],  // Full request body (sanitized)
    'content_type' => 'application/json',
]
```

**RESPONSE Fields (MANDATORY):**
```php
'response' => [
    'headers' => [...],  // Response headers
    'body' => [...],  // Full response body (sanitized)
    'content_type' => 'application/json',
    'size_bytes' => 2048,  // Response size
]
```

**ERROR Fields (if applicable):**
```php
'error' => [
    'message' => 'Connection timeout',
    'type' => 'ConnectionException',
    'code' => 0,
    'context' => [...],  // Additional error context
]
```

**METADATA Fields (OPTIONAL but recommended):**
```php
'metadata' => [
    'user_id' => 123,  // If triggered by user
    'request_id' => 'uuid-here',  // Correlation ID
    'retry_count' => 0,  // If retried
    'ip_address' => '127.0.0.1',  // Client IP
]
```

### 3. Create Helper Service/Class

**Location:** `Modules/Core/Services/ThirdPartyRequestLogger.php`

**Responsibilities:**
- Enforce standard format
- Sanitize sensitive data (tokens, passwords)
- Calculate duration
- Format request/response data
- Handle errors

**Interface:**
```php
interface ThirdPartyRequestLoggerContract
{
    public function logRequest(
        string $service,
        string $method,
        string $endpoint,
        string $baseUrl,
        array $requestData,
        ?array $responseData = null,
        ?\Throwable $exception = null,
        float $startedAt,
        array $metadata = []
    ): void;
}
```

### 4. Integration Points

**Core HTTP Client:**
- Integrate logger into Core HTTP Client (after implementation)
- Auto-log all requests through Core HTTP Client

**Existing SDKs:**
- WordPress SDK: Refactor to use ThirdPartyRequestLogger
- LM Studio SDK: Refactor to use ThirdPartyRequestLogger
- Future SDKs: Use ThirdPartyRequestLogger from the start

---

## Implementation Plan

### Phase 0: Research & Design
- [ ] Review existing logging patterns
- [ ] Define exact format structure
- [ ] Design sanitization strategy
- [ ] Design helper service interface

### Phase 1: Infrastructure
- [ ] Add `third-party-requests` channel to `config/logging.php`
- [ ] Create `ThirdPartyRequestLogger` service class
- [ ] Implement sanitization methods
- [ ] Add unit tests for logger

### Phase 2: Core HTTP Client Integration
- [ ] Integrate logger into Core HTTP Client (after Core HTTP Client is implemented)
- [ ] Ensure all requests through Core HTTP Client are logged
- [ ] Add tests for logging integration

### Phase 3: SDK Migration
- [ ] Refactor WordPress SDK to use ThirdPartyRequestLogger
- [ ] Refactor LM Studio SDK to use ThirdPartyRequestLogger
- [ ] Update tests for both SDKs
- [ ] Verify log format consistency

### Phase 4: Documentation & Cleanup
- [ ] Update docs with examples
- [ ] Remove old logging code
- [ ] Update migration guide
- [ ] Add monitoring/alerting recommendations

---

## Technical Details

### Sanitization Strategy

**Mask:**
- `Authorization` headers → `Bearer [MASKED]`
- `password` fields → `[MASKED]`
- `token` fields → `[MASKED]`
- `api_key` fields → `[MASKED]`
- `secret` fields → `[MASKED]`

**Preserve:**
- All other headers
- Request/response structure
- Error messages
- Status codes

### Performance Considerations

- **Async logging:** Consider queue-based logging for high-volume APIs
- **Size limits:** Truncate very large payloads (>1MB) with note
- **Sampling:** Optional sampling for high-frequency endpoints

### Security Considerations

- **Never log:** Passwords, tokens, secrets (always mask)
- **PII:** Consider masking PII in logs (GDPR compliance)
- **Retention:** 14 days default, configurable per service

---

## Success Criteria

- [ ] All third-party requests logged with standard format
- [ ] Full request/response data available in logs
- [ ] Consistent format across all SDKs
- [ ] Sensitive data properly masked
- [ ] Logs searchable/filterable by service, method, endpoint
- [ ] Unit tests coverage >90%
- [ ] Documentation complete

---

## Dependencies

- [Core HTTP Client Plan](./2025-11-14-core-http-client.md) - Should integrate logger into Core HTTP Client
- [Core HTTP Client Adoption Plan](./2025-11-14-core-http-client-adoption.md) - SDKs will use logger via Core HTTP Client

---

## Notes

- This plan should be implemented AFTER Core HTTP Client is ready
- Logger can be used standalone before Core HTTP Client integration
- Consider creating a separate `ThirdPartyRequestLogger` service vs integrating directly into Core HTTP Client
- May need to add log rotation/archival strategy for high-volume services

