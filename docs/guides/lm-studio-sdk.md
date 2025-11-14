# LM Studio SDK Guide

Complete guide for integrating and using the LM Studio SDK for local AI model interaction.

## Overview

The LM Studio SDK provides a type-safe interface to interact with locally-hosted AI models through LM Studio's OpenAI-compatible API.

**Key Features:**
- ✅ Type-safe chat completion API
- ✅ Streaming support for real-time responses
- ✅ Model management and health checks
- ✅ Automatic error handling and retry logic
- ✅ Full OpenAI API compatibility

## Installation Status

> **Note:** This guide is in development. The LM Studio SDK is currently being implemented.
> 
> **Implementation Plan:** [2025-11-13-lm-studio-sdk.md](../plans/technical/2025-11-13-lm-studio-sdk.md)
> 
> **Current Phase:** Phase 0 - Foundation (Contract + DTO scaffolding complete)

## Quick Start (Coming Soon)

Once implementation is complete, basic usage will look like:

```php
use Modules\LmStudio\Services\Contracts\SdkContract;

class ExampleService
{
    public function __construct(
        private readonly SdkContract $lmStudio
    ) {}
    
    public function generateContent(string $prompt): string
    {
        $response = $this->lmStudio->chatCompletion([
            'model' => 'local-model',
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
        ]);
        
        return $response->choices[0]->message->content;
    }
}
```

## Architecture

### Contract-Based Design

All SDK interactions go through `Modules\LmStudio\Services\Contracts\SdkContract`:

```php
interface SdkContract
{
    public function chatCompletion(array $params): ChatCompletionResponse;
    public function streamChatCompletion(array $params): Generator;
    public function listModels(): ModelsListResponse;
    public function health(): bool;
}
```

### Service Registration

SDK is registered as singleton in `LmStudioServiceProvider`:

```php
$this->app->singleton(SdkContract::class, function ($app) {
    return new Sdk(
        client: $app->make(ClientInterface::class),
        config: config('lmstudio')
    );
});
```

## Configuration

LM Studio SDK configuration in `config/lmstudio.php`:

```php
return [
    'base_url' => env('LM_STUDIO_BASE_URL', 'http://localhost:1234/v1'),
    'timeout' => env('LM_STUDIO_TIMEOUT', 30),
    'default_model' => env('LM_STUDIO_DEFAULT_MODEL', 'local-model'),
];
```

### Environment Variables

```bash
# .env
LM_STUDIO_BASE_URL=http://localhost:1234/v1
LM_STUDIO_TIMEOUT=30
LM_STUDIO_DEFAULT_MODEL=your-model-name
```

## Usage Examples

### Chat Completion

```php
// Coming soon - will be added during Phase 1 implementation
```

### Streaming Responses

```php
// Coming soon - will be added during Phase 2 implementation
```

### Model Management

```php
// Coming soon - will be added during Phase 2 implementation
```

## Error Handling

```php
// Coming soon - will be added during implementation
```

## Testing

### Unit Tests

```php
// Coming soon - will be added during implementation
```

### Integration Tests

```php
// Coming soon - will be added during implementation
```

## Best Practices

### 1. Always Use the Contract

✅ **CORRECT:**
```php
public function __construct(
    private readonly SdkContract $lmStudio
) {}
```

❌ **WRONG:**
```php
public function __construct(
    private readonly Sdk $lmStudio  // Concrete class
) {}
```

### 2. Handle Errors Gracefully

```php
// Coming soon - will be added during implementation
```

### 3. Never Call SDK from Frontend

✅ **CORRECT:** Always proxy through Laravel API
```php
Route::post('/api/v1/ai/generate', [AiController::class, 'generate']);
```

❌ **WRONG:** Direct frontend calls to LM Studio
```javascript
// Never do this - security risk
axios.post('http://localhost:1234/v1/chat/completions', {...});
```

## API Reference

### Chat Completion Parameters

Coming soon - will document all available parameters during implementation.

### Response Structure

Coming soon - will document response DTOs during implementation.

## Troubleshooting

### Common Issues

**Issue:** LM Studio not responding
- **Solution:** Verify LM Studio is running on correct port
- **Check:** `curl http://localhost:1234/v1/health`

**Issue:** Model not found
- **Solution:** Load model in LM Studio UI first
- **Check:** Available models via `/v1/models` endpoint

## Related Documentation

- [**Implementation Plan**](../plans/technical/2025-11-13-lm-studio-sdk.md) - Detailed implementation roadmap
- [**Architecture Decision**](../decisions/2025-11-13-lm-studio-api.md) - Why we chose LM Studio
- [**AI Workflow**](../ai-workflow.md) - AI development process

## Implementation Roadmap

This guide will be completed in phases:

- [x] **Phase 0:** Foundation (Contract + DTO scaffolding) - **COMPLETE**
- [ ] **Phase 1:** Chat completion API
- [ ] **Phase 2:** Streaming support
- [ ] **Phase 3:** Model management
- [ ] **Phase 4:** Advanced features

**Last Updated:** 2025-11-14  
**Status:** Guide skeleton created, awaiting Phase 1 implementation
