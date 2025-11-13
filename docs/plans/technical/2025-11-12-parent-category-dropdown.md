# Plan – Parent Category Dropdown Fix

Status: Active  
Owner: TBD  
Created: 2025-11-12  
Updated: 2025-11-12

## Summary
- Ensure the parent category selector only lists valid parent candidates and presents a clear hierarchy.

## Objectives
- Audit current category APIs/queries to understand why child categories appear in the parent dropdown.
- Limit the dataset for the parent selector to categories that can legally act as parents (e.g., exclude current record, descendants, and disallowed types).
- Deliver a UX that visually distinguishes nesting while keeping the list performant for large category counts.
- Add automated coverage to guard against regressions in future category features.

## Tasks
- [ ] Trace the data flow powering the parent dropdown (API endpoint, transformers, Vue store/components) and document current behaviour.
- [ ] Align business rules with stakeholders (maximum depth, excluded statuses, handling of trashed categories).
- [ ] Update backend queries/resources to serve only eligible parent categories, including necessary self/descendant filtering.
- [ ] Refresh the Vue component to consume the filtered payload, maintain indentation, and guard against empty states.
- [ ] Add feature/integration tests (PHP + Vue unit/e2e as appropriate) covering the selector rules.
- [ ] Validate in staging with realistic datasets; capture before/after screenshots for release notes.

## Notes
- Reference the reported issue (screenshot in `docs/assets/issues/parent-category-dropdown.png` once added) when communicating with QA.
- Coordinate with caching strategy owners if category trees are cached; ensure invalidation is handled.

