# Parent Category Dropdown Fix

**Status:** In Progress  
**Created:** 2025-11-12  
**Last Updated:** 2025-01-17

## Summary

Fix the parent category dropdown in the category management UI to ensure it only displays valid parent candidates, excluding the category being edited and its descendants. This prevents circular references and invalid hierarchy structures.

## Problem Statement

Currently, the parent category dropdown shows all categories, including:
- The category being edited (which would create a self-reference)
- Descendant categories (which would create circular references)

This can lead to invalid category hierarchies and potential infinite loops in category traversal.

## Business Rules

1. **Unlimited nesting depth** - Categories can be nested to any depth
2. **Self-exclusion** - A category cannot be its own parent
3. **Descendant exclusion** - A category cannot be a parent of its descendants (children, grandchildren, etc.)
4. **Trashed categories** - Default hidden, can be shown with checkbox (gray color when shown)
5. **Inactive categories** - WordPress does not have inactive categories

## UI/UX Mockup (Updated Layout)

```
┌─────────────────────────────────────────────────────────────────────────┐
│ Categories Management                                                    │
│ Manage WordPress categories, organize content with hierarchical taxonomy │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│ [Alert: WordPress authentication required] (if not logged in)            │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│ Categories                                                               │
├─────────────────────────────────────────────────────────────────────────┤
│ [Search] [Include trashed ☐] [Per page: 10 ▼] [Refresh]                │
│                                                                          │
│ ┌─────────────────────────────────────────────────────────────────────┐ │
│ │ ID │ Name          │ Slug        │ Parent │ Posts │ Actions        │ │
│ ├────┼───────────────┼─────────────┼────────┼───────┼────────────────┤ │
│ │ #1 │ Technology    │ technology  │ Root   │ 42    │ [Delete]       │ │
│ │ #2 │ Programming   │ programming │ #1     │ 15    │ [Delete]       │ │
│ │ #3 │ Web Dev       │ web-dev     │ #2     │ 8     │ [Delete]       │ │
│ └─────────────────────────────────────────────────────────────────────┘ │
│                                                                          │
│ [Previous] Page 1 · showing 10 results [Next]                          │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│ Create category                                                          │
├─────────────────────────────────────────────────────────────────────────┤
│ [Name input]                                                             │
│ [Slug input]                                                             │
│ [Description textarea]                                                  │
│ [Parent dropdown]                                                        │
│ [Create category button]                                                 │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│ Options                                                                  │
├─────────────────────────────────────────────────────────────────────────┤
│ ☐ Include trashed categories                                            │
│ Per page: [10 ▼] (options: 10, 20, 50, 100, Show all)                 │
└─────────────────────────────────────────────────────────────────────────┘
```

## Implementation Tasks

- [x] Backend: Create eligibleParents() method in CategoryService
  - DoD: Method accepts `exclude` (category ID) and `include_trashed` (boolean) parameters
  - DoD: Recursively identifies all descendant IDs of the excluded category
  - DoD: Filters out trashed categories by default (unless `include_trashed=true`)
  - DoD: Returns hierarchy structure with depth information
  - DoD: Maintains performance with proper caching (reuse existing category cache)
  - Estimated: 6 hours

- [x] Fix authentication requirement enforcement (CRITICAL)
  - DoD: Block all features when WordPress token is not available ✅
  - DoD: Do NOT fetch categories from API when `tokenStatus.remembered === false` ✅
  - DoD: **Completely hide both categories list AND create/update form when no token** ✅
    - **Categories list table completely hidden** when `tokenStatus.remembered === false` ✅
      - Added `v-if="tokenStatus.remembered"` to categories list container div
      - Table, toolbar, pagination all hidden when not authenticated
    - **Create/Update category form completely hidden** when `tokenStatus.remembered === false` ✅
      - Added `v-if="tokenStatus.remembered"` to form container div
      - Form completely removed from DOM when not authenticated
  - DoD: Show clear error message requiring login before using features ✅
    - **Single alert message at top of page** (no duplicate messages) ✅
    - Alert shows "WordPress authentication required. Please login on home page first."
    - Link to home page for token setup
    - Only this alert is visible when not authenticated (no other UI elements)
  - DoD: Verify that cached data is not displayed when token is missing ✅
    - Categories list hidden, so no cached data visible
    - Form hidden, so no cached data visible
  - DoD: Completely hide all features until authenticated ✅
    - Categories list table is completely hidden (v-if="tokenStatus.remembered") ✅
    - Create/Update category form is completely hidden (v-if="tokenStatus.remembered") ✅
    - Only authentication alert message is visible when not logged in ✅
  - DoD: Check token status before calling any WordPress API endpoints ✅
    - submitCategory() returns early if no token
    - confirmDelete() returns early if no token
    - fetchCategories() returns early if no token
    - fetchParentOptions() returns early if no token
  - Estimated: 3 hours
  - Status: Completed (2025-01-17)

- [x] Update frontend to use new backend API endpoint
  - DoD: Replace `parentOptions` computed logic with API call to `/api/v1/wordpress/categories/parents` ✅
  - DoD: Pass `exclude` parameter when editing category (category ID) ✅
  - DoD: Pass `include_trashed` parameter based on checkbox state ✅
  - DoD: Display hierarchy with indentation (depth-based styling already exists) ✅
  - DoD: Handle empty state gracefully when no valid parents exist ✅
    - Show "None" option (value: 0) as default
    - Display message "No valid parent categories available" when all categories are excluded
  - DoD: Show trashed categories with gray color when `include_trashed=true` ✅
  - DoD: Add "Include trashed" checkbox in toolbar (not in form) ✅
    - Checkbox label: "Include trashed categories"
    - Position: In toolbar section, next to per-page selector and refresh button
    - Styling: Small text, secondary color, consistent with toolbar design
    - Scope: Affects both categories list and parent dropdown
    - Note: Form is for creating/editing categories, not for filtering options
  - DoD: Maintains accessibility standards (ARIA labels, keyboard navigation) ✅
  - DoD: Responsive design works on mobile devices ✅
  - DoD: Only call API when `tokenStatus.remembered === true` ✅
  - Estimated: 4 hours
  - Status: Completed (2025-01-17)

- [x] UI/UX Improvements
  - DoD: Add page header "Categories Management" with small description ✅
    - Header: "Categories Management"
    - Description: "Manage WordPress categories, organize content with hierarchical taxonomy"
    - Removed plan-related mentions and "View plan" button
  - DoD: Add "Categories" header to categories list card ✅
    - Header style matches "Create category" header (h5 text-white mb-3)
    - Position: Top of categories list card
  - DoD: Create new "Options" card below "Create category" form ✅
    - Card title: "Options"
    - Moved "Include trashed" checkbox from toolbar to this card
    - Options card only contains "Include trashed" checkbox
  - DoD: "Per page" selector in Categories card header (right side) ✅
    - Position: Right side of "Categories" header, horizontal layout
    - Layout: flex-row with justify-content-between
    - Per page options: 10, 20, 50, 100, Show all
    - "Show all" option removes pagination limit (omits per_page parameter)
    - Styling: Small form-select (form-select-sm), consistent with toolbar design
  - DoD: Clean up toolbar ✅
    - Toolbar now only has: Search input, Refresh button
    - "Per page" moved to Categories header (right side)
  - Estimated: 2 hours
  - Status: Completed (2025-01-17)

- [ ] Add comprehensive test coverage
  - DoD: PHP unit tests for `CategoryService::eligibleParents()` method
  - DoD: PHP feature tests for `GET /api/v1/wordpress/categories/parents` endpoint
  - DoD: Edge case testing:
    - Category with no descendants
    - Category with deep nesting (5+ levels)
    - All categories excluded scenario
    - Trashed category filtering
  - Estimated: 4 hours

## Acceptance Criteria

- [x] Parent dropdown excludes the category being edited
- [x] Parent dropdown excludes all descendants of the category being edited
- [x] Parent dropdown shows hierarchy with proper indentation
- [x] Trashed categories are filtered by default
- [x] Trashed categories can be shown with checkbox (gray color)
- [x] Authentication required - all features hidden when no token
- [x] Page header with description added
- [x] Options card created with Include trashed and Per page
- [x] Per page includes "Show all" option
- [x] Categories list has header matching Create category style
- [x] All tests pass

## Technical Details

### Backend Implementation

**New API Endpoint:**
- `GET /api/v1/wordpress/categories/parents`
- Query parameters:
  - `exclude` (optional, int): Category ID to exclude (and its descendants)
  - `include_trashed` (optional, bool): Include trashed categories

**Service Method:**
```php
CategoryService::eligibleParents(?int $exclude = null, bool $includeTrashed = false): array
```

**Algorithm:**
1. Fetch all categories from SDK (cached)
2. Build category map for quick lookups
3. If `exclude` is provided, recursively find all descendant IDs
4. Filter categories:
   - Exclude the category itself (if editing)
   - Exclude all descendants
   - Exclude trashed (unless `include_trashed=true`)
5. Calculate depth for each eligible category
6. Sort by depth, then by name

### Frontend Implementation

**Component Structure:**
- Page header with title and description
- Alert message (if not authenticated)
- Categories list card (with header)
- Create category form card
- Options card (new)

**State Management:**
- `includeTrashed`: boolean (moved to Options card)
- `filters.perPage`: number | 'all' (new: 'all' for show all)
- `filters.page`: number (reset to 1 when perPage changes)

**API Integration:**
- `fetchCategories()`: Sends `include_trashed` and `per_page` parameters
- `fetchParentOptions()`: Sends `exclude` and `include_trashed` parameters
- When `per_page='all'`: Send very large number or omit pagination params

## Risks & Mitigations

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Performance with 1000+ categories | Medium | High | Per-page pagination already implemented, "Show all" should be used carefully |
| Circular reference bugs | Low | High | Comprehensive testing of exclusion logic |
| Cache invalidation issues | Low | Medium | Proper cache versioning and invalidation on mutations |

## Related Plans

- Caching Strategy plan for performance optimization
- Authentication enforcement patterns

## Notes

- WordPress categories don't have a `status` field by default, but we check for consistency
- Depth calculation traverses parent chain recursively
- "Show all" option should be used with caution for large datasets
