# Plan – Categories Management

Status: Active  
Owner: TBD  
Created: 2025-11-11  
Updated: 2025-11-11

## Summary
- Deliver a full CRUD experience for managing WordPress categories inside JOOwp, including API endpoints, UI, and audit coverage.

## Objectives
- Provide secured API endpoints to list, create, update, and delete categories via the WordPress SDK.
- Surface a Vue page under the home navigation that lists categories with pagination, search, and inline status indicators.
- Ensure every mutation emits audit logs and respects the remember-token flow.
- Achieve comprehensive unit + feature test coverage for controllers, services, and UI behaviours.

## Tasks
- [ ] Finalise requirements: category fields, validation rules, user roles.
- [x] Design API contract (`Modules/Core` or dedicated module) for categories CRUD using the SDK.
- [x] Implement FormRequests with DocBlocks and strict typing.
- [x] Add controller & service layer with ActionLogger hooks and API response envelope.
- [x] Extend WordPress SDK if new endpoints or query parameters are required.
- [ ] Replace placeholder UI with final table/CRUD layout (current placeholder cards misaligned with design).
- [ ] Create Vue page (`Categories/Index.vue`) with table, search, and “Remember” token handling.
- [ ] Add Inertia route and navigation entry from the home screen quick links.
- [ ] Write unit tests (FormRequest, service, controller) and feature tests (API endpoints + UI smoke).
- [ ] Update documentation (home guide + dedicated module readme) once feature is complete.

## Notes
- Reuse existing Core module unless categories evolve into a separate domain.
- Respect dark theme aesthetic, use Bootstrap input groups for the toolbar, and apply consistent gap/padding so the Categories UI mirrors Tags.
- Track and resolve UI bugs found during placeholder stage (dropdown alignment, responsive behaviour) before marking the plan complete.

## Progress
- 2025-11-11: WordPress SDK extended with category CRUD helpers plus CategoryService wired to ActionLogger.
- 2025-11-11: `/api/v1/wordpress/categories` endpoints shipped with strict FormRequests and feature/unit coverage.
- 2025-11-11: Inertia `Taxonomy/Categories/Index` page delivers search, pagination, remember-token guardrail, and inline create/update/delete flows.
