# Plan – AI Content Suite

Status: Active  
Priority: P2  
Owner: Viet Vu <jooservices@gmail.com>  
Created: 2025-11-11  
Updated: 2025-11-13  
Target: 2025-12-15  
Epic: AI Features

## Summary
Coordinate AI-powered workflows that augment the editorial experience: fact checking, assisted generation, tone alignment, and post-publication insights. All features must respect the local-AI policy defined in `docs/development/ai-workflow.md`.

**Scope:** Phase 1 - Core content generation only (outline, intro, meta). Phase 2 will include fact-checking and summaries.

## Dependencies
- Ollama installation on development/production servers
- WordPress SDK authentication (wp_tokens table)
- Action logging infrastructure (`ActionLogger`)

## Objectives
- Provide a reusable AI orchestration layer that proxies all requests through approved local models (Ollama, Copilot, Cursor) with audit trails.
- Ship “Generate Content” helpers inside the post editor (outline, intro paragraph, meta description) with manual review requirements.
- Implement fact-checking modules that highlight citations, verify claims, and produce a review checklist prior to publishing.
- Offer optional AI-driven post summaries for internal teams (product, marketing) without auto-publishing.

## Tasks
- [ ] Design AI service contracts (e.g., `ContentAiService`) abstracting model selection, prompt templates, and logging.
  - DoD: Interface created with all method signatures defined
  - DoD: PHPDoc complete for all methods
  - DoD: Contract follows Laravel service pattern from `docs/architecture/flow.md`
  - Estimated: 4 hours
- [ ] Build backend endpoints for:
  - [ ] Generating outlines, intros, meta descriptions.
    - DoD: POST `/api/v1/ai/generate` endpoint implemented
    - DoD: FormRequest validation for content type, context, tone
    - DoD: Returns ApiResponse envelope with generated content
    - DoD: 90% test coverage with mocked AI responses
    - Estimated: 8 hours
  - [ ] Running fact-check requests with structured outputs (claims, sources, confidence).
    - DoD: POST `/api/v1/ai/fact-check` endpoint implemented
    - DoD: Returns structured JSON with claims array
    - DoD: Integration test with sample content
    - Estimated: 12 hours
  - [ ] Producing internal summaries (bullet digest, action points).
    - DoD: POST `/api/v1/ai/summarize` endpoint implemented
    - DoD: Supports multiple summary formats (bullets, executive, technical)
    - DoD: Feature test with 3 different content types
    - Estimated: 6 hours
- [ ] Implement FormRequests + policies ensuring only authorised roles trigger AI actions.
  - DoD: FormRequest classes for all AI endpoints with validation rules
  - DoD: Policy checks user has 'use-ai-features' permission
  - DoD: 100% test coverage (simple validation logic)
  - Estimated: 3 hours
- [ ] Add Vue components:
  - [ ] "Generate content" sidebar in Posts editor with prompt controls.
    - DoD: Component renders in post editor with toggle button
    - DoD: Form includes content type selector, tone slider, context input
    - DoD: Shows loading state during generation
    - DoD: Inserts generated content on user approval
    - Estimated: 12 hours
  - [ ] Fact-check panel showing claim list, source suggestions, manual verification toggles.
    - DoD: Panel displays claims in expandable list
    - DoD: Each claim shows confidence score and suggested sources
    - DoD: User can mark claims as verified/disputed
    - Estimated: 16 hours
  - [ ] Summary export modal (copy, download).
    - DoD: Modal opens with generated summary preview
    - DoD: Copy to clipboard button functional
    - DoD: Download as .txt and .md formats
    - Estimated: 4 hours
- [ ] Log all AI interactions (prompt, model, tokens/time, user) to `action` channel for audit compliance.
  - DoD: Every AI request logs operation, user, prompt (sanitized), response length, duration
  - DoD: Uses `ActionLogger` from Core module
  - DoD: Logs stored in `storage/logs/action.log` with proper rotation
  - Estimated: 2 hours
- [ ] Create unit + feature tests for the AI endpoints and front-end flows (mocking AI responses).
  - DoD: Unit tests for all service methods (95% coverage target)
  - DoD: Feature tests for all API endpoints (90% coverage)
  - DoD: Vue component tests for user interactions
  - DoD: All tests pass in CI pipeline
  - Estimated: 10 hours
- [ ] Update `docs/guides/wordpress-sdk.md` or create dedicated AI guide with workflow usage.
  - DoD: Guide includes setup instructions for Ollama
  - DoD: Documents all API endpoints with request/response examples
  - DoD: Includes frontend component usage examples
  - DoD: Security best practices documented
  - Estimated: 4 hours
- [ ] Coordinate with DevOps to ensure local model availability and resource monitoring.
  - DoD: Ollama installed and running on staging server
  - DoD: Production deployment plan approved
  - DoD: Monitoring alerts configured for AI service health
  - DoD: Resource usage baselines established
  - Estimated: 6 hours

**Total Estimated Effort:** 87 hours (~2-3 weeks for 1 developer)

## Success Metrics
- **Performance:** AI generation completes in < 10 seconds for 95% of requests
- **Adoption:** 50% of content editors try AI features within first month
- **Quality:** 80% of generated content accepted with minor edits
- **Reliability:** 99% uptime for AI service endpoints
- **Security:** Zero instances of sensitive data leakage in prompts

## Risks & Mitigations
| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Ollama performance issues | High | Medium | Load test early, implement request queuing |
| Poor content quality | Medium | High | Extensive prompt engineering, user feedback loop |
| Resource exhaustion | High | Low | Rate limiting, request timeouts, monitoring |
| User distrust of AI | Medium | Medium | Clear labeling, manual review required, transparency |

## Related Plans
- `docs/features/wordpress/posts-management.md` - Post editor integration point
- `docs/technical/caching-strategy.md` - May cache AI responses for similar prompts
- Phase 2: Fact-checking expansion (separate plan TBD)

## Notes
- Never auto-publish AI-generated content; editors must accept each suggestion.
- Ensure prompts avoid leaking sensitive data—strip secrets and apply context windows carefully.
- Consider future enhancements: translation assistance, tone/style scoring, accessibility checks.

