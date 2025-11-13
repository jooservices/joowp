# Plan – Parent Category Dropdown Fix

Status: Ready  
Priority: P2  
Owner: TBD  
Created: 2025-11-12  
Updated: 2025-11-12  
Target: 2025-11-20  
Epic: WordPress Features

## Summary
Fix the parent category selector to only display valid parent candidates and present a clear hierarchy. Currently, child categories incorrectly appear in the parent dropdown, causing UI confusion and potential data integrity issues.

**Scope:** Parent dropdown filtering only. Bulk category operations and advanced hierarchy management are excluded from this phase.

## Dependencies
- WordPress SDK with category endpoints (completed)
- Category management UI components (existing)
- Database with proper category hierarchy structure

## Objectives
- Eliminate invalid parent selections (no circular references or child-to-parent assignments)
- Improve UX with visual hierarchy indicators (indentation or tree structure)
- Maintain dropdown performance for large category datasets (>1000 categories)
- Prevent data corruption from invalid parent-child relationships

## Tasks
- [ ] Audit current category data flow and API endpoints
  - DoD: Document current API endpoint, transformers, and Vue components involved
  - DoD: Identify root cause of child categories appearing in parent dropdown
  - DoD: Create before/after screenshots of current behavior
  - Estimated: 3 hours

- [ ] Define business rules for parent eligibility
  - DoD: Document maximum nesting depth allowed
  - DoD: Define handling of trashed/inactive categories
  - DoD: Clarify self-exclusion and descendant exclusion rules
  - DoD: Get stakeholder sign-off on rules
  - Estimated: 2 hours

- [ ] Update backend queries to filter eligible parents
  - DoD: API only returns categories that can legally be parents
  - DoD: Excludes current category and all its descendants
  - DoD: Filters out trashed/inactive categories
  - DoD: Maintains performance with proper database indexing
  - Estimated: 6 hours

- [ ] Enhance Vue component for hierarchy display
  - DoD: Shows indentation or tree structure for nested categories
  - DoD: Handles empty state gracefully when no valid parents exist
  - DoD: Maintains accessibility standards (ARIA labels, keyboard navigation)
  - DoD: Responsive design works on mobile devices
  - Estimated: 8 hours

- [ ] Add comprehensive test coverage
  - DoD: PHP feature tests for API filtering logic
  - DoD: Vue component tests for UI behavior
  - DoD: Edge case testing (circular refs, deep nesting, empty states)
  - DoD: Performance test with 1000+ categories
  - Estimated: 6 hours

- [ ] Staging validation and documentation
  - DoD: Test with realistic production dataset
  - DoD: Before/after screenshots for release notes
  - DoD: QA validation checklist completed
  - DoD: User documentation updated if needed
  - Estimated: 3 hours

**Total Estimated Effort:** 28 hours (~1 week for 1 developer)

## Success Metrics
- **Accuracy:** 100% elimination of invalid parent selections
- **Performance:** Dropdown loads in <500ms for datasets up to 1000 categories
- **UX:** User testing shows 90% find hierarchy visualization clear and helpful
- **Quality:** Zero reported data corruption issues post-deployment

## Risks & Mitigations
| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Performance degradation with large datasets | Medium | Medium | Implement pagination or virtualization for 500+ categories |
| Complex nested data breaks UI | High | Low | Extensive edge case testing, fallback to flat list |
| Caching invalidation issues | Medium | Low | Review cache strategy with platform team |
| User confusion with new hierarchy display | Low | Medium | A/B testing, user feedback collection |

## Related Plans
- `docs/technical/caching-strategy.md` - Coordinate cache invalidation for category changes
- Phase 2: Bulk category operations and advanced management (Q1 2026)

## Notes
- Reference reported issue screenshot when available: `docs/assets/issues/parent-category-dropdown.png`
- Coordinate with caching strategy team for proper cache invalidation
- Consider future enhancement: drag-and-drop category reordering
- Must maintain backward compatibility with existing category API consumers

