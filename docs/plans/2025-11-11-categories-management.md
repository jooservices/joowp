# Plan – Categories Management

Status: Active  
Owner: TBD  
Created: 2025-11-11  
Updated: 2025-11-12

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
- [x] Replace placeholder UI with final table/CRUD layout (current placeholder cards misaligned with design).
- [x] Create Vue page (`Categories/Index.vue`) with table, search, and “Remember” token handling.
- [x] Add Inertia route and navigation entry from the home screen quick links.
- [x] Write unit tests (FormRequest, service, controller) and feature tests (API endpoints + UI smoke).
- [ ] Update documentation (home guide + dedicated module readme) once feature is complete.

## Notes
- Reuse existing Core module unless categories evolve into a separate domain.
- The shipped UI already mirrors the dark aesthetic and input-group spec; any future tweaks should extend the shared components rather than re-implementing per page.
- Continue to track minor UI bugs and parity gaps versus the Tags plan so both taxonomy experiences remain aligned.

## Progress
- 2025-11-11: WordPress SDK extended with category CRUD helpers plus CategoryService wired to ActionLogger.
- 2025-11-11: `/api/v1/wordpress/categories` endpoints shipped with strict FormRequests and feature/unit coverage.
- 2025-11-11: Inertia `Taxonomy/Categories/Index` page delivers search, pagination, remember-token guardrail, and inline create/update/delete flows.
- 2025-11-12: Applied AdminLTE-inspired dark palette to taxonomy categories for richer contrast and accent colors.
- 2025-11-12: Tightened toolbar refresh button sizing and added Font Awesome icons to category actions for visual clarity.
- 2025-11-12: Swapped parent ID input for contextual dropdown with self-guarded tree options (console warnings resolved) and mirrored WordPress-style hierarchy in the listing table, including ID column and formatted parent/description outputs.
- 2025-11-12: Refined table layout with sortable headers, icon-driven actions, corrected pagination summary, and WordPress-style tree default with posts column + delete affordance.
- 2025-11-12: Fixed navbar active-state detection to respect query strings on taxonomy pages.
