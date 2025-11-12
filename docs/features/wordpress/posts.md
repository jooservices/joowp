# Plan – Posts Management

Status: Active  
Owner: TBD  
Created: 2025-11-11  
Updated: 2025-11-11

## Summary
- Deliver an end-to-end workflow for browsing, creating, editing, and scheduling WordPress posts directly from JOOwp, leveraging the remember-token infrastructure and shared taxonomy components.

## Objectives
- Allow editors to list posts with filtering by status, category, and tag, including pagination and search.
- Support drafting and publishing posts via the WordPress REST API, with validation and preview states.
- Enable scheduling (date/time) and basic analytics overview (views, engagement) via integrated API calls.
- Ensure audit logging, remember-token behavior, and comprehensive test coverage for all post mutations.
- Integrate AI-assisted authoring (content generation, fact checking, tone guidance) as optional workflows that never bypass manual review.

## Tasks
- [ ] Confirm WordPress post fields to manage (title, slug, content, excerpt, featured media, status, schedule).
- [ ] Extend the WordPress SDK with helper methods for post CRUD operations and status transitions.
- [ ] Design API endpoints (likely under a dedicated Taxonomy/Content module) returning the standard response envelope.
- [ ] Implement FormRequests with DocBlocks, typed validation, and scheduling rules.
- [ ] Build Vue pages:
  - [ ] `Posts/Index.vue` – table with filters, quick status badges, and remember-token guard rails.
  - [ ] `Posts/Edit.vue` – form with markdown/HTML editor, taxonomy selectors, schedule controls.
- [ ] Prototype AI content assistance hooks (generate outline, draft body, apply tone) with clear “human review required” messaging.
- [ ] Add fact-check pipeline (AI suggestion + manual verification checklist) before publishing.
- [ ] Integrate with categories and tags UI components for successful cross-navigation.
- [ ] Add unit + feature tests covering list, create, update, delete, and schedule; include browser smoke tests.
- [ ] Update documentation (`home-screen`, SDK guide) once functionality is production-ready.

## Notes
- Consider extracting shared form components for taxonomy selectors and scheduling controls.
- Ensure the remember-token summary warns when credentials expire or lack required scopes.
- Evaluate using queued jobs for publishing or syncing posts if synchronous API calls are insufficient.
- AI features must run through the local inference strategy defined in `docs/ai-dev-workflow-2025-en.md`; no external model calls without approval.

