# ADR-2025-11-13: LM Studio API Enablement Guardrails

**Status:** Accepted  
**Date:** 2025-11-13  
**Related Plan:** `docs/plans/technical/2025-11-13-lm-studio-sdk.md`

## Context
We are building a Core SDK so Laravel and Vue surfaces can talk to LM Studio instances running on developer machines or edge nodes. The SDK must remain stable across LM Studio releases, protect local workstations from unauthenticated LAN access, and provide deterministic behaviour for streaming/chat, audio, and image flows. During Phase 0 research we identified repeated failure modes—API surface drift, missing authentication, and brittle streaming—that require formal guardrails before implementation begins.

## Decision
1. **Streaming transport** – Standardize on HTTP Server-Sent Events (SSE) for `/v1/chat/completions` streaming. Implement two retries (250 ms backoff) before downgrading to buffered responses and emit a `StreamingDowngraded` event so the frontend can reflect the fallback. WebSocket support is deferred until LM Studio publishes a GA socket endpoint.  
2. **Authentication posture** – Require a bearer token (`LM_STUDIO_API_KEY`) outside `APP_ENV=local`. When no key is provided in non-local environments, bootstrap should abort with a descriptive error. Allow optional extra headers through `LM_STUDIO_EXTRA_HEADERS` for experimental builds, but keep disabled by default.  
3. **Version detection & floor** – Support LM Studio versions ≥0.2.18, with 0.2.20+ preferred. Health checks must read `lmstudio_version`; if missing, inspect the `x-lmstudio-version` header or `/v1/models` metadata. Reject older versions using an `UnsupportedVersionException` before binding the SDK.  
4. **Feature flagging** – Gate audio (STT/TTS) and image generation endpoints behind `features.lmstudio.*` toggles. Defaults remain `false` until infra owners validate throughput and storage costs.

## Consequences
- ✅ Predictable streaming behaviour and telemetry for downgrades simplifies frontend UX and debugging.  
- ✅ Enforcing API keys outside local environments reduces accidental exposure when engineers bind LM Studio to `0.0.0.0`.  
- ✅ Version gating prevents runtime breakage when engineers use outdated desktop builds, surfacing remediation instructions early.  
- ✅ Feature flags let us incrementally roll out heavier endpoints while keeping the base chat/embedding surfaces lightweight.  
- ⚠️ SSE-only streaming may limit advanced features (binary payloads) until WebSocket support lands.  
- ⚠️ Mandatory API keys outside local adds setup friction; documentation must highlight configuration steps in onboarding.

## Alternatives Considered
- **Immediate WebSocket support:** Rejected due to unstable beta APIs and lack of server documentation; SSE is battle-tested and compatible with Laravel’s HTTP client.  
- **Allow unauthenticated LAN access:** Rejected because many developers bind LM Studio to 0.0.0.0; we would be responsible for any unauthenticated inference endpoints.  
- **Support LM Studio <0.2.18:** Rejected because older builds lack the OpenAI-compatible REST API and health metadata, making SDK maintenance costly.  
- **Enable audio/image features by default:** Deferred to avoid surprising resource consumption and to keep initial SDK footprint minimal.
