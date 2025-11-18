# Plan – Parent Category Dropdown Fix

Status: Ready  
Priority: P2  
Owner: TBD  
Created: 2025-11-12  
Updated: 2025-01-17  
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

## Business Rules (Defined)

### Parent Eligibility Rules
- **Maximum Nesting Depth:** Unlimited (no depth limit)
- **Self-Exclusion:** Category being edited cannot appear in its own parent dropdown
- **Descendant Exclusion:** All descendants (children, grandchildren, etc.) of the category being edited are excluded to prevent circular references
- **Trashed Categories:**
  - Default: Not displayed in parent dropdown
  - Optional: Checkbox to toggle display of trashed categories
  - When displayed: Gray color styling to indicate trashed status
  - **Note:** Field name for trashed status needs to be verified from WordPress REST API documentation
- **Inactive Categories:** WordPress does not have inactive category status (verified)

### Example
When editing "Tech" (ID: 1) with children "Programming" (ID: 2) and "JavaScript" (ID: 3):
- ❌ Excluded: "Tech" (self), "Programming" (direct child), "JavaScript" (grandchild)
- ✅ Available: Root (0), other categories not in the descendant tree

## Tasks
- [x] Audit current category data flow and API endpoints
  - DoD: Document current API endpoint, transformers, and Vue components involved ✅
  - DoD: Identify root cause of child categories appearing in parent dropdown ✅
  - Root Cause: Frontend `parentOptions` computed property only disables the editing category itself but does not exclude its descendants
  - DoD: Create before/after screenshots of current behavior
  - Estimated: 3 hours
  - Status: Completed (2025-01-17)

- [x] Define business rules for parent eligibility
  - DoD: Document maximum nesting depth allowed ✅ (Unlimited - no limit)
  - DoD: Define handling of trashed/inactive categories ✅ (see Business Rules above)
  - DoD: Clarify self-exclusion and descendant exclusion rules ✅ (see Business Rules above)
  - DoD: Get stakeholder sign-off on rules ✅
  - Estimated: 2 hours
  - Status: Completed (2025-01-17)

- [ ] Implement backend API endpoint for eligible parent categories
  - DoD: Create new endpoint `GET /api/v1/wordpress/categories/parents`
  - DoD: Accept query parameters: `exclude` (int, optional), `include_trashed` (bool, default: false)
  - DoD: API returns only categories that can legally be parents
  - DoD: Excludes current category (if `exclude` provided) and all its descendants recursively
  - DoD: Filters out trashed categories by default (unless `include_trashed=true`)
  - DoD: Returns hierarchy structure with depth information
  - DoD: Maintains performance with proper caching (reuse existing category cache)
  - Estimated: 6 hours

- [ ] Fix authentication requirement enforcement (CRITICAL)
  - DoD: Block all features when WordPress token is not available
  - DoD: Do NOT fetch categories from API when `tokenStatus.remembered === false`
  - DoD: Disable all form inputs, buttons, and interactive elements when no token
  - DoD: Show clear error message requiring login before using features
  - DoD: Verify that cached data is not displayed when token is missing
  - DoD: Only allow read-only view or completely hide features until authenticated
  - DoD: Check token status before calling any WordPress API endpoints
  - Estimated: 3 hours

- [ ] Update frontend to use new backend API endpoint
  - DoD: Replace `parentOptions` computed logic with API call to `/api/v1/wordpress/categories/parents`
  - DoD: Pass `exclude` parameter when editing category (category ID)
  - DoD: Pass `include_trashed` parameter based on checkbox state
  - DoD: Display hierarchy with indentation (depth-based styling already exists)
  - DoD: Handle empty state gracefully when no valid parents exist
    - Show "None" option (value: 0) as default
    - Display message "No valid parent categories available" when all categories are excluded
  - DoD: Show trashed categories with gray color when `include_trashed=true`
  - DoD: Add "Include trashed" checkbox below "Parent" label and above dropdown selector
    - Checkbox label: "Include trashed categories"
    - Position: Between label and dropdown for clear visual hierarchy
    - Styling: Small text, secondary color, consistent with form design
  - DoD: Maintains accessibility standards (ARIA labels, keyboard navigation)
  - DoD: Responsive design works on mobile devices
  - DoD: Only call API when `tokenStatus.remembered === true`
  - Estimated: 4 hours

- [ ] Add comprehensive test coverage
  - DoD: PHP unit tests for `CategoryService::eligibleParents()` method
  - DoD: PHP feature tests for `GET /api/v1/wordpress/categories/parents` endpoint
  - DoD: Edge case testing:
    - Exclude root category
    - Exclude category with deep nesting (unlimited levels)
    - Exclude category with no descendants
    - Include/exclude trashed categories
    - Empty results when all categories are excluded
  - DoD: Performance test with 1000+ categories (verify caching works)
  - Estimated: 6 hours

- [ ] Staging validation and documentation
  - DoD: Test with realistic production dataset
  - DoD: Before/after screenshots for release notes
  - DoD: QA validation checklist completed
  - DoD: User documentation updated if needed
  - Estimated: 3 hours

**Total Estimated Effort:** 22 hours (~3 days for 1 developer)

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

## Implementation Details

### API Endpoint Design
**Endpoint:** `GET /api/v1/wordpress/categories/parents`

**Query Parameters:**
- `exclude` (int, optional): Category ID to exclude (self + all descendants)
- `include_trashed` (bool, optional, default: false): Include trashed categories in results

**Response Format:**
```json
{
  "code": "wordpress.categories.parents",
  "message": "Eligible parent categories retrieved successfully.",
  "data": {
    "items": [
      {
        "id": 1,
        "name": "Tech",
        "slug": "tech",
        "parent": 0,
        "depth": 0
      }
    ],
    "hierarchy": true
  }
}
```

### Files to Create/Modify
1. **New:** `Modules/WordPress/app/Http/Requests/ParentCategoriesRequest.php`
   - Validation rules for `exclude` and `include_trashed` parameters
2. **Modify:** `Modules/WordPress/app/Http/Controllers/CategoryController.php`
   - Add `parents()` method
3. **Modify:** `Modules/WordPress/app/Services/CategoryService.php`
   - Add `eligibleParents()` method with filtering logic
4. **Modify:** `Modules/WordPress/routes/api.php`
   - Add route: `Route::get('categories/parents', [CategoryController::class, 'parents'])`
5. **Modify:** `resources/js/Pages/Taxonomy/Categories/Index.vue`
   - **CRITICAL:** Fix authentication enforcement - block features when no token
   - Check `tokenStatus.remembered` before calling any API endpoints
   - Disable all form inputs and buttons when `tokenStatus.remembered === false`
   - Clear cached data when token is missing
   - Replace `parentOptions` computed with API call to `/api/v1/wordpress/categories/parents`
   - **UI Design:** Add "Include trashed" checkbox
     - Location: Below "Parent" label, above dropdown selector
     - Label: "Include trashed categories"
     - Styling: Small text (`text-secondary small`), checkbox with label
     - Behavior: Toggle `include_trashed` parameter when calling API
   - Handle empty state: Show "None" option + message "No valid parent categories available"
   - Only call parent categories API when token is available

### Algorithm: Descendant Exclusion
```php
function getDescendantIds(int $categoryId, array $allCategories): array
{
    $descendants = [];
    $children = array_filter($allCategories, fn($cat) => $cat['parent'] === $categoryId);
    
    foreach ($children as $child) {
        $descendants[] = $child['id'];
        $descendants = array_merge($descendants, getDescendantIds($child['id'], $allCategories));
    }
    
    return $descendants;
}
```

### Depth Calculation
- Depth is calculated from parent chain (WordPress REST API does not provide depth field)
- Algorithm: Traverse parent chain up to root (parent = 0) to calculate depth
- Used for hierarchy display and indentation in dropdown
- No maximum depth limit - categories can nest unlimited levels

### Trashed Category Detection
- **TODO:** Verify exact field name from WordPress REST API documentation
- Possible field names: `status`, `deleted`, or other
- Filter logic: Exclude categories where trashed field indicates trashed status (unless `include_trashed=true`)
- Implementation: Check WordPress REST API documentation for categories endpoint response structure

### Caching Strategy
- Reuse existing `categories()` cache from WordPress SDK
- Cache key includes `exclude` and `include_trashed` parameters
- Cache TTL: 30 minutes (same as regular categories endpoint)
- Invalidation: When category is created/updated/deleted, invalidate parent categories cache
- Consider separate cache key: `wp.categories.parents.v{version}.{exclude}.{include_trashed}.{queryHash}`

### Performance Considerations
- **Per-page parameter:** Already implemented in existing categories endpoint
- For 1000+ categories: Use per-page parameter to limit results if needed
- Monitor API response time during testing
- If performance issues arise: Consider limiting to top-level categories only, or implementing search/filter in dropdown

## Critical Issue: Authentication Enforcement

### Current Problem
- **Issue:** Page renders with full features even when WordPress token is not available
- **Root Cause:** 
  - `fetchCategories()` is called regardless of token status (line 750)
  - Features are not disabled when `tokenStatus.remembered === false`
  - Cached data may be displayed from previous sessions
- **Impact:** Users can see cached/stale data without authentication, violating security requirements

### Required Fix
1. **Check token status before any API calls:**
   - Do NOT call `fetchCategories()` if `tokenStatus.remembered === false`
   - Do NOT call `/api/v1/wordpress/categories/parents` without token
   - Verify token status in `onMounted` before fetching data

2. **Block all features when no token:**
   - Disable form inputs (name, slug, description, parent dropdown)
   - Disable all buttons (create, update, delete, refresh)
   - Hide or disable category table
   - Show clear message: "WordPress authentication required. Please login on home page first."

3. **Clear cached data:**
   - Clear `categories` array when token is missing
   - Clear `parentRegistry` when token is missing
   - Ensure no stale data is displayed

4. **UI/UX:**
   - Show prominent alert/error message at top of page
   - Link to home page for token setup
   - Disable all interactive elements with visual indication (grayed out)

## Open Questions / To Verify During Implementation

1. **WordPress API Trashed Field:**
   - **Action Required:** Check WordPress REST API documentation for categories endpoint
   - Verify exact field name for trashed status (`status`, `deleted`, or other?)
   - Test with actual WordPress API response

2. **Depth Field:**
   - **Action Required:** Check WordPress REST API documentation for categories endpoint
   - Verify if WordPress REST API returns `depth` field in category response
   - If not available, calculate depth from parent chain (traverse to root)

3. **Performance with Large Datasets:**
   - Test with 1000+ categories to verify <500ms response time
   - Use existing per-page parameter if needed to limit results
   - If slow, consider optimization strategies (limit to top-level only, search/filter)

4. **Empty State Handling:** ✅ RESOLVED
   - When all categories are excluded: Show "None" option (value: 0) + message "No valid parent categories available"
   - Message displayed as helper text below dropdown or as placeholder text in dropdown

## Notes
- Reference reported issue screenshot when available: `docs/assets/issues/parent-category-dropdown.png`
- Coordinate with caching strategy team for proper cache invalidation (reuse existing category cache)
- Consider future enhancement: drag-and-drop category reordering
- Must maintain backward compatibility with existing category API consumers
- Backend filter approach ensures data integrity and reduces frontend complexity

