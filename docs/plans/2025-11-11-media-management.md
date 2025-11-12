# Plan – Media Management

Status: Active  
Owner: TBD  
Created: 2025-11-11  
Updated: 2025-11-11

## Summary
- Provide a comprehensive media library inside JOOwp for browsing, uploading, editing, and organising WordPress media assets. Layer in AI-assisted capabilities for tagging, alt-text generation, and content verification while respecting the local AI workflow.

## Objectives
- Mirror core WordPress media functionality (list, upload, edit metadata, delete) through the JOOwp UI and API layer.
- Integrate categories/tags plans so media can be filtered by taxonomy and attached to posts.
- Introduce AI helpers for generating alt text, suggesting tags, and detecting potential issues (duplicate content, low contrast, sensitive material).
- Ensure all mutations are audited, remember-token safeguards apply, and functionality is covered by unit + feature tests.

## Tasks
- [ ] Confirm media requirements: supported mime types, maximum file size, metadata fields (title, description, alt text, captions).
- [ ] Extend the WordPress SDK with helper methods for media listing, upload (multipart), update, and delete.
- [ ] Design REST endpoints (likely under a Media module) using the standard API response envelope.
- [ ] Implement FormRequests for uploads and metadata updates, including validation for file size and type.
- [ ] Build Vue pages:
  - [ ] `Media/Index.vue` – grid/list view with filters, remember-token guardrail, upload button, bulk actions.
  - [ ] `Media/Edit.vue` – metadata panel, taxonomy assignment, AI assistance controls.
- [ ] Create shared components for file drop zone, responsive masonry/grid layout, and preview modals.
- [ ] Implement AI helpers:
  - [ ] Alt-text generator (local model prompt, manual approval required).
  - [ ] Smart tag suggestions using the taxonomy plans.
  - [ ] Fact-check / compliance hints (e.g., detect brand mark usage, check aspect ratio).
- [ ] Log all media mutations via ActionLogger, including AI suggestion acceptance/rejection.
- [ ] Add unit tests (FormRequests, services, SDK wrapper) and feature tests (upload, edit, delete, AI suggestion submission).
- [ ] Update documentation (home guide, SDK guide, AI content suite, media-specific readme) once feature is stable.

## Notes
- Evaluate background job vs. synchronous upload flow; large files may require queued processing and progress indicators.
- Ensure AI helpers operate on locally stored files (download to temp, generate metadata, upload) in line with the local-AI policy.
- Coordinate with Posts plan so media selection is available inside post editor modals.

