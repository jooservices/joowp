# Plan – AI Content Suite

Status: Active  
Owner: TBD  
Created: 2025-11-11  
Updated: 2025-11-11

## Summary
- Coordinate AI-powered workflows that augment the editorial experience: fact checking, assisted generation, tone alignment, and post-publication insights. All features must respect the local-AI policy defined in `docs/ai-dev-workflow-2025-en.md`.

## Objectives
- Provide a reusable AI orchestration layer that proxies all requests through approved local models (Ollama, Copilot, Cursor) with audit trails.
- Ship “Generate Content” helpers inside the post editor (outline, intro paragraph, meta description) with manual review requirements.
- Implement fact-checking modules that highlight citations, verify claims, and produce a review checklist prior to publishing.
- Offer optional AI-driven post summaries for internal teams (product, marketing) without auto-publishing.

## Tasks
- [ ] Design AI service contracts (e.g., `ContentAiService`) abstracting model selection, prompt templates, and logging.
- [ ] Build backend endpoints for:
  - [ ] Generating outlines, intros, meta descriptions.
  - [ ] Running fact-check requests with structured outputs (claims, sources, confidence).
  - [ ] Producing internal summaries (bullet digest, action points).
- [ ] Implement FormRequests + policies ensuring only authorised roles trigger AI actions.
- [ ] Add Vue components:
  - [ ] “Generate content” sidebar in Posts editor with prompt controls.
  - [ ] Fact-check panel showing claim list, source suggestions, manual verification toggles.
  - [ ] Summary export modal (copy, download).
- [ ] Log all AI interactions (prompt, model, tokens/time, user) to `action` channel for audit compliance.
- [ ] Create unit + feature tests for the AI endpoints and front-end flows (mocking AI responses).
- [ ] Update `docs/guides/core-wordpress-sdk.md` or dedicated content guide with AI workflow usage.
- [ ] Coordinate with DevOps to ensure local model availability and resource monitoring.

## Notes
- Never auto-publish AI-generated content; editors must accept each suggestion.
- Ensure prompts avoid leaking sensitive data—strip secrets and apply context windows carefully.
- Consider future enhancements: translation assistance, tone/style scoring, accessibility checks.

