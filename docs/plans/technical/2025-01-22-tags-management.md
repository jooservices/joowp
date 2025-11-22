# Tags Management Implementation

**Status:** Completed  
**Created:** 2025-01-22  
**Last Updated:** 2025-01-22

## Summary

Implement a Tags Management page that mirrors the Categories page design and functionality, but adapted for WordPress tags (which are flat, non-hierarchical). The page will feature a left-side list of tags and a right-side create/update form, with full CRUD operations, search, pagination, and authentication enforcement.

## Problem Statement

Currently, the Tags page (`/taxonomy/tags`) only displays a placeholder "Work in progress" message. Users need a fully functional interface to:
- View all WordPress tags in a searchable, sortable table
- Create new tags with name, slug, and description
- Update existing tags
- Delete tags (with force delete option)
- Manage pagination and per-page display options

## Business Rules

1. **Flat structure** - Tags have no parent/child relationships (unlike categories)
2. **No hierarchy** - Tags are displayed in a flat list, sorted by name, ID, slug, or post count
3. **Authentication required** - All operations require WordPress JWT token (same as categories)
4. **Auto slug generation** - Slug auto-generates from name if left empty, but preserves manual edits
5. **Rich text description** - Description field uses Quill editor (HTML allowed)
6. **Pagination** - Supports per-page options (10, 20, 50, 100, Show all) and page navigation

## UI/UX Mockup

```
┌─────────────────────────────────────────────────────────────────────────┐
│ Tags Management                                                        │
│ Manage WordPress tags, organize content with flat taxonomy             │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│ [Alert: WordPress authentication required] (if not logged in)          │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│ Tags                                    [Per page: 10 ▼]               │
├─────────────────────────────────────────────────────────────────────────┤
│ [Search] [Refresh]                                                      │
│                                                                          │
│ ┌─────────────────────────────────────────────────────────────────────┐ │
│ │ ID │ Name          │ Slug        │ Posts │ Actions                  │ │
│ ├────┼───────────────┼─────────────┼───────┼──────────────────────────┤ │
│ │ 1  │ Technology    │ technology  │ 42    │ [Delete]                 │ │
│ │ 2  │ Programming   │ programming │ 15    │ [Delete]                 │ │
│ │ 3  │ Web Dev       │ web-dev     │ 8     │ [Delete]                 │ │
│ │ (Click row to edit)                                                      │
│ └─────────────────────────────────────────────────────────────────────┘ │
│                                                                          │
│ [Previous] Page 1 · showing 10 results [Next]                          │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│ Create tag                                                              │
├─────────────────────────────────────────────────────────────────────────┤
│ [Name input]                                                             │
│ [Slug input] (auto-generated from name if empty)                        │
│ [Description editor] (Quill - HTML allowed)                            │
│ [Create tag button]                                                      │
└─────────────────────────────────────────────────────────────────────────┘
```

## Implementation Tasks

### Phase 1: Backend - SDK Methods

- [x] Add `createTag()` method to `SdkContract` and `Sdk`
  - DoD: Method accepts tag payload (name, slug, description)
  - DoD: Makes POST request to WordPress REST API `/wp/v2/tags`
  - DoD: Invalidates tag list cache after creation
  - DoD: Returns created tag data
  - Estimated: 1 hour

- [x] Add `updateTag()` method to `SdkContract` and `Sdk`
  - DoD: Method accepts tag ID and payload
  - DoD: Makes POST request to WordPress REST API `/wp/v2/tags/{id}`
  - DoD: Invalidates tag cache and tag list cache after update
  - DoD: Returns updated tag data
  - Estimated: 1 hour

- [x] Add `deleteTag()` method to `SdkContract` and `Sdk`
  - DoD: Method accepts tag ID and query parameters (force)
  - DoD: Makes DELETE request to WordPress REST API `/wp/v2/tags/{id}`
  - DoD: Invalidates tag cache and tag list cache after deletion
  - DoD: Returns deletion response
  - Estimated: 1 hour

- [x] Add `tag()` method to `SdkContract` and `Sdk` (single tag retrieval)
  - DoD: Method accepts tag ID and optional query parameters
  - DoD: Makes GET request to WordPress REST API `/wp/v2/tags/{id}`
  - DoD: Uses caching (30 minutes TTL)
  - DoD: Returns single tag data
  - Estimated: 30 minutes

### Phase 2: Backend - Service Layer

- [x] Create `TagService` in `Modules/WordPress/app/Services/TagService.php`
  - DoD: Implements `list()` method with filters (search, per_page, page)
  - DoD: Implements `create()` method with action logging
  - DoD: Implements `update()` method with before/after logging
  - DoD: Implements `delete()` method with before logging and force option
  - DoD: Uses `ActionLogger` for all mutations (same pattern as CategoryService)
  - DoD: Handles pagination for tag list (WordPress API limit: 100 per page)
  - Estimated: 3 hours

### Phase 3: Backend - HTTP Layer

- [x] Create `IndexTagsRequest` in `Modules/WordPress/app/Http/Requests/IndexTagsRequest.php`
  - DoD: Validates `search` (string, max:120, nullable)
  - DoD: Validates `per_page` (integer, min:1, max:100)
  - DoD: Validates `page` (integer, min:1)
  - DoD: Includes PHPStan type definition
  - Estimated: 30 minutes

- [x] Create `StoreTagRequest` in `Modules/WordPress/app/Http/Requests/StoreTagRequest.php`
  - DoD: Validates `name` (required, string, max:100)
  - DoD: Validates `slug` (sometimes, nullable, string, max:120)
  - DoD: Validates `description` (sometimes, nullable, string, max:500)
  - DoD: Includes PHPStan type definition
  - Estimated: 30 minutes

- [x] Create `UpdateTagRequest` in `Modules/WordPress/app/Http/Requests/UpdateTagRequest.php`
  - DoD: Same validation rules as `StoreTagRequest`
  - DoD: Includes PHPStan type definition
  - Estimated: 30 minutes

- [x] Create `DeleteTagRequest` in `Modules/WordPress/app/Http/Requests/DeleteTagRequest.php`
  - DoD: Validates `force` (sometimes, boolean)
  - DoD: Includes PHPStan type definition
  - Estimated: 30 minutes

- [x] Create `TagController` in `Modules/WordPress/app/Http/Controllers/TagController.php`
  - DoD: Implements `index()` method using `IndexTagsRequest` and `TagService::list()`
  - DoD: Implements `store()` method using `StoreTagRequest` and `TagService::create()`
  - DoD: Implements `update()` method using `UpdateTagRequest` and `TagService::update()`
  - DoD: Implements `destroy()` method using `DeleteTagRequest` and `TagService::delete()`
  - DoD: Returns `ApiResponse` with proper codes and messages
  - DoD: Extracts authenticated user for action logging
  - Estimated: 2 hours

- [x] Add routes to `Modules/WordPress/routes/api.php`
  - DoD: `GET /api/v1/wordpress/tags` → `TagController::index`
  - DoD: `POST /api/v1/wordpress/tags` → `TagController::store`
  - DoD: `POST /api/v1/wordpress/tags/{tag}` → `TagController::update`
  - DoD: `DELETE /api/v1/wordpress/tags/{tag}` → `TagController::destroy`
  - DoD: Routes follow same naming pattern as categories (`core.wordpress.tags.*`)
  - Estimated: 15 minutes

### Phase 4: Frontend - Vue Component

- [x] Update `resources/js/Pages/Taxonomy/Tags/Index.vue` with full implementation
  - DoD: **Layout Structure:**
    - Left side (col-xl-8): Tags list table
    - Right side (col-xl-4): Create/Update form (sticky)
  - DoD: **Page Header:**
    - Title: "Tags Management"
    - Description: "Manage WordPress tags, organize content with flat taxonomy"
    - Remove "View Plan" button
  - DoD: **Authentication Enforcement:**
    - Show alert at top when `!tokenStatus.remembered`
    - Hide tags list when `!tokenStatus.remembered`
    - Hide create/update form when `!tokenStatus.remembered`
    - Only show single authentication message when not logged in
  - DoD: **Tags List Card:**
    - Header: "Tags" title with "Per page" selector on the right (same line, no wrapping)
    - Search input with debounce
    - Refresh button
    - Table columns: ID, Name, Slug, Posts, Actions
    - Sortable columns (ID, Name, Slug, Posts)
    - **Clickable rows:** Clicking on a tag row opens edit form (same as Categories)
    - Delete button in Actions column (with `@click.stop` to prevent row click)
    - Pagination controls (Previous/Next, page info)
    - ID displayed as plain number (no badge, no "#" prefix)
  - DoD: **Create/Update Form:**
    - Title: "Create tag" or "Update tag"
    - Name input (required)
    - Slug input (auto-generated from name if empty, preserves manual edits)
    - Description field using Quill editor (minimal toolbar: bold, italic, link, lists)
    - Dark theme styling for Quill
    - Submit button (Create/Update)
    - Cancel button (when editing)
  - DoD: **State Management:**
    - Reactive filters (search, perPage, page)
    - Reactive form (name, slug, description)
    - `slugManuallyEdited` ref to track manual slug edits
    - `editingTag` ref to track current tag being edited
    - `isLoading` and `isSubmitting` refs
  - DoD: **Row Click Behavior:**
    - Table rows have `@click="selectForEdit(tag)"` to open edit form
    - Delete button uses `@click.stop` to prevent row click from triggering
    - Row styling indicates clickability (cursor pointer, hover effect)
  - DoD: **Functions:**
    - `fetchTags()` - Fetches tags from API with filters
    - `submitTag()` - Creates or updates tag
    - `confirmDelete()` - Deletes tag with confirmation (uses `@click.stop` to prevent row click)
    - `selectForEdit()` - Populates form for editing (triggered by row click)
    - `resetForm()` - Clears form and resets editing state
    - `generateSlug()` - Creates URL-friendly slug from name
    - `handleSlugInput()` - Marks slug as manually edited
    - `debouncedSearch()` - Debounces search input
    - `cycleSort()` - Handles column sorting
    - `sortIcon()` - Returns sort icon class
    - `extractErrorMessage()` - Extracts error message from API response
    - `pushAlert()` - Shows alert notification
  - DoD: **Auto Slug Generation:**
    - Watches `form.name` and auto-generates `form.slug` if `slugManuallyEdited` is false and slug is empty
    - Preserves manual slug edits
    - Re-generates if slug is cleared
  - DoD: **Quill Integration:**
    - Install `quill` and `@vueup/vue-quill` packages
    - Configure minimal toolbar
    - Apply dark theme styling
  - DoD: **Pagination Logic:**
    - Handles `perPage === 'all'` correctly
    - Disables Next button when showing all or at last page
    - Resets to page 1 when filters change
  - Estimated: 8 hours

### Phase 5: Testing

- [x] Create unit tests for `TagService` in `tests/Unit/WordPressTagServiceTest.php`
  - DoD: Test `list()` with various filters
  - DoD: Test `list()` with pagination (WordPress API limit: 100)
  - DoD: Test `create()` with valid payload
  - DoD: Test `update()` with valid payload
  - DoD: Test `delete()` with force option
  - DoD: Test action logging for all mutations
  - DoD: Mock SDK calls properly
  - Estimated: 3 hours

- [x] Create feature tests for `TagController` in `tests/Feature/WordPressTagApiTest.php`
  - DoD: Test `GET /api/v1/wordpress/tags` endpoint
  - DoD: Test `POST /api/v1/wordpress/tags` endpoint
  - DoD: Test `POST /api/v1/wordpress/tags/{tag}` endpoint
  - DoD: Test `DELETE /api/v1/wordpress/tags/{tag}` endpoint
  - DoD: Test validation errors
  - DoD: Test authentication requirements
  - DoD: Mock SDK calls properly
  - Estimated: 3 hours

### Phase 6: Documentation & Cleanup

- [x] Update `Modules/WordPress/README.md` with tags documentation
  - DoD: Document tag routes and endpoints
  - DoD: Document TagService usage
  - DoD: Link to tests
  - Estimated: 30 minutes

- [x] Verify all quality gates pass
  - DoD: Laravel Pint (code style)
  - DoD: PHP_CodeSniffer (PSR-12)
  - DoD: PHPMD (design quality)
  - DoD: PHPStan (static analysis)
  - DoD: Vue-tsc (TypeScript checking)
  - DoD: All tests pass
  - Estimated: 30 minutes

## Technical Notes

### Differences from Categories

1. **No Parent Hierarchy:**
   - Tags have no `parent` field
   - No `eligibleParents()` method needed
   - No parent dropdown in form
   - No parent column in table
   - No parent badge display

2. **Simpler Structure:**
   - TagService is simpler (no descendant calculation, no depth calculation)
   - FormRequest validation doesn't include `parent` field
   - Frontend doesn't need parent options fetching

3. **Same Core Features:**
   - Authentication enforcement (same pattern)
   - Search, pagination, sorting (same pattern)
   - Quill editor for description (same implementation)
   - Auto slug generation (same logic)
   - Action logging (same pattern)

### WordPress REST API Notes

- Tags endpoint: `/wp/v2/tags`
- Tag fields: `id`, `name`, `slug`, `description`, `count` (post count)
- Pagination: `per_page` max is 100 (same as categories)
- Search: Uses `search` query parameter

### Caching Strategy

- Tag list cache: `wp.tags.{query_hash}` (30 minutes TTL)
- Single tag cache: `wp.tag.{id}` (30 minutes TTL)
- Cache invalidation: After create/update/delete operations

## Risks & Mitigations

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| WordPress API rate limiting | High | Medium | Use caching, implement pagination properly |
| Large tag list performance | Medium | Medium | Implement pagination, use "Show all" option carefully |
| Slug conflicts | Low | Low | WordPress handles slug uniqueness automatically |

## Dependencies

- WordPress REST API with JWT authentication
- Existing CategoryService pattern (for reference)
- Quill.js for rich text editing
- Vue 3, TypeScript, Inertia.js
- Laravel FormRequest validation
- ActionLogger for audit trails

## Acceptance Criteria

1. ✅ Tags page displays full list of tags in table format
2. ✅ Search functionality works with debounce
3. ✅ Pagination works correctly (including "Show all" option)
4. ✅ Sorting works for all columns (ID, Name, Slug, Posts)
5. ✅ Clicking on tag row opens edit form (no separate Edit button)
6. ✅ Create tag form works with auto slug generation
7. ✅ Update tag form pre-populates and updates correctly
8. ✅ Delete tag works with confirmation (doesn't trigger row click)
9. ✅ Authentication is enforced (hide UI when not logged in)
10. ✅ Quill editor works for description field
11. ✅ All backend endpoints return proper API responses
12. ✅ Action logging works for all mutations
13. ✅ All tests pass
14. ✅ All quality gates pass

## Estimated Total Time

- Backend (SDK + Service + HTTP): ~8 hours
- Frontend (Vue component): ~8 hours
- Testing: ~6 hours
- Documentation: ~1 hour
- **Total: ~23 hours**

## Results

### Implementation Summary

All phases have been completed successfully:

**Phase 1 - SDK Methods:** ✅
- Added `tag()`, `createTag()`, `updateTag()`, `deleteTag()` methods to `SdkContract` and `Sdk`
- Implemented cache invalidation methods (`invalidateTagCache()`, `invalidateTagListCache()`)
- All methods follow WordPress REST API documentation standards

**Phase 2 - Service Layer:** ✅
- Created `TagService` with full CRUD operations
- Integrated `ActionLogger` for audit trails
- Query parameters follow WordPress REST API documentation (context, orderby, order)

**Phase 3 - HTTP Layer:** ✅
- Created all FormRequest classes with proper validation
- Created `TagController` with error handling for server downtime
- Added routes following same pattern as categories
- All endpoints return structured `ApiResponse` format

**Phase 4 - Frontend:** ✅
- Full Vue component implementation with Tags list and Create/Update form
- Quill editor integration for description field
- Auto slug generation from name
- Row click to edit functionality
- Search, pagination, and sorting
- Error handling for API failures

**Phase 5 - Testing:** ✅
- Unit tests for `TagService` covering all methods
- Feature tests for `TagController` covering all endpoints
- Validation error tests included

**Phase 6 - Documentation:** ✅
- Updated `Modules/WordPress/README.md` with tags documentation
- All quality gates pass

### Additional Features Implemented

- **Error Handling:** Added comprehensive error handling in `TagController` to gracefully handle WordPress server downtime (502/503 errors)
- **Frontend Error Handling:** Updated Vue component to handle structured error responses from API
- **WordPress API Compliance:** All query parameters verified against official WordPress REST API documentation

### Test Results

- All unit tests pass
- All feature tests pass
- All quality gates pass (Pint, PHPCS, PHPMD, PHPStan, Vue-tsc)

