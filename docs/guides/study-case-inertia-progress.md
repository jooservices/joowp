## Study Case: Inertia Progress Helper Regression

### What happened

- Change: swapped `InertiaProgress.init()` for a custom helper (`start/setupProgress`) based only on local typings.
- Result: the compiled JS tried to call a non-existent helper on the dev bundle, throwing `TypeError: … is not a function`, so Inertia never mounted the welcome view.
- Tests (`npm run typecheck`, `php artisan test`) still passed because they don’t exercise the browser bundle.

### Fix

- Reverted to the library’s documented API (`InertiaProgress.init()`).
- Synced local type definitions with the real package exports.
- Restarted Vite and manually verified the page renders before committing.

### Lessons / safeguards

1. **Never trust local stubs blindly**—confirm against the actual package.
2. **UI changes require browser verification** in addition to type + PHP tests.
3. **Document every regression** so future work checks these failure modes.
4. **Dark UI ≠ unreadable UI**—always verify contrast ratios in the browser before shipping any styling.
5. **Bootstrap JS needs wiring**—dynamic components (dropdowns, modals) must be instantiated explicitly. Missing init silently breaks behaviour even when markup and styling look correct.
6. **Add placeholder routes early**—ensure navigation links land on real Inertia pages (even stubbed) to avoid 404/blank-state regressions during iterative builds.

Always revisit this note before touching SPA bootstrap code or third-party helpers.

