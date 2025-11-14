# Component Story – LM Studio Streaming Demo

**Date:** 2025-11-15  
**Audience:** Frontend engineers validating LM Studio integrations

## Scenario

Use the `/ai/lmstudio/demo` Inertia page to drive the new LM Studio Pinia store and confirm that:

1. Models can be fetched through `GET /api/v1/ai/lmstudio/models`
2. Inference requests trigger `POST /api/v1/ai/lmstudio/infer`
3. Streamed chunks append live output, with retry/offline toggles

## Steps

1. Log into the admin SPA and navigate to `/ai/lmstudio/demo`
2. Toggle **Use mock stream** to exercise the fallback path (no backend events required)
3. Submit the default prompts and observe streaming chunks populate the console panel
4. Disable the mock stream, run the command again, and subscribe to `lmstudio.inference.{jobId}` via Echo/SSE to see real payloads
5. Trigger `Retry stream` to verify reconnection logic and error rendering

## Expected Output

- Pinia store state reflects current job ID, retry count, and chunk buffer
- Errors (offline, timeouts, permission issues) display inside the control panel without crashing the page
- Coverage is maintained by the Vitest suite: `npm run test:frontend`

## Notes

- Demo uses the exported `createMockStreamFactory()` for offline experiences
- When LM Studio feature flag is disabled the API returns the standard `lmstudio.disabled` envelope; the UI surfaces this in-line
