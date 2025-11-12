# Plan – Tags Management

Status: Active  
Owner: TBD  
Created: 2025-11-11  
Updated: 2025-11-11

## Summary
- Build a parallel workflow to manage WordPress tags from within JOOwp, mirroring the categories experience with tailored validation and UI.

## Objectives
- Expose API endpoints for listing, creating, updating, and deleting tags via the WordPress SDK.
- Deliver a Vue page (`Tags/Index.vue`) with filters, tag counts, and quick actions.
- Ensure mutations log via `ActionLogger` and reuse the common remember-token behaviour.
- Reach full unit + feature coverage, including integration tests covering happy-path flows.

## Tasks
- [ ] Confirm required tag attributes (name, slug, description, visibility).
- [ ] Add API routes + controller/service layer for tags, returning the standard envelope.
- [ ] Implement FormRequests with strict types and DocBlocks.
- [ ] Extend SDK if additional query parameters or endpoints are needed.
- [ ] Create shared UI utilities (table, pagination, search) that can be reused by both categories and tags.
- [ ] Replace placeholder cards with the final table layout and ensure responsive consistency with the Categories page.
- [ ] Implement the Tags Vue page with dark theme styling and toast-driven feedback.
- [ ] Wire navigation entry from the home screen and/or sidebar.
- [ ] Add unit + feature tests for tag APIs and UI interactions.
- [ ] Update documentation (home screen guide, dedicated readmes) upon completion.

## Notes
- Reuse shared components from the categories plan to avoid duplication and fix alignment gaps discovered in the first iteration.
- Consider extracting a shared “TaxonomyService” interface if both plans converge on similar logic.

