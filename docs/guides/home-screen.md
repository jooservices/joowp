## Home Screen Overview

The home screen (`/`) serves as the public landing page for JOOwp. Its intent is to orient new contributors, surface the most important developer resources, and provide a fast path into authenticated workflows when a WordPress token is remembered.

### First visit

- Displays the hero section with the “Welcome to JOOwp” call-to-action and quick buttons to enter the platform or jump straight into the engineering principles.
- The welcome card appears only on the first load per browser; closing it stores a flag (`joowp.welcome.seen`) so subsequent visits show the returning layout.

### Returning visit

- Shows the returning card with shortcuts to the WordPress SDK guide and a button to replay the onboarding hero.
- The navbar replicates the expected dark aesthetic and collects WordPress credentials when no token is remembered.
- The “Remember” switch persists the JWT in the `wp_tokens` table through `POST /api/v1/wordpress/token`. The platform never sends credentials directly to WordPress; it calls the Core API which, in turn, uses the SDK.

### Remembered token state

- When a token exists, the credential inputs collapse into an input-group summary that displays a masked token and the associated WordPress username.
- The token summary supports:
  - Viewing the masked token at a glance.
  - A `Forget` action (red button) that clears the stored token via `DELETE /api/v1/wordpress/token` and re-enables the credential inputs.
- A loading message covers the navbar controls while token status initializes (`GET /api/v1/wordpress/token`).

### Navigation updates

- The global navbar now persists across every Inertia page and exposes the **Taxonomy** dropdown.
  - **Categories** (`/taxonomy/categories`) opens the management table that mirrors WordPress’ tree ordering by default and supports column-based sorting when needed.
  - **Tags** (`/taxonomy/tags`) currently hosts the matching taxonomy shell and links to the relevant implementation plans.
- When a remembered WordPress token exists, all taxonomy API calls automatically include the stored JWT via the SDK’s token resolver.

### Quick links

- **Principles** — `docs/principles.md` (engineering rules, coding standards, testing gates).
- **Code quality** — `docs/code-quality.md` (end-to-end lint/analyse workflow).
- **WordPress SDK** — `docs/guides/core-wordpress-sdk.md` (API usage, remember-token endpoints).
- **Study case** — `docs/guides/study-case-inertia-progress.md` (SPA regression post-mortem).
- **Taxonomy plans** — `docs/plans/2025-11-11-categories-management.md` and `docs/plans/2025-11-11-tags-management.md` (roadmaps for taxonomy modules).

Use the home screen as the canonical starting point whenever you need to onboard teammates, validate that tokens are remembered, or navigate to feature documentation.

