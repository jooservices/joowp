# Plan – Home Welcome Text Readability Fix

Status: Active  
Priority: P2  
Owner: Platform Engineering  
Created: 2025-11-14  
Updated: 2025-11-14  
Target: 2025-11-16  
Epic: UI/UX Improvements

## Summary
Improve text contrast and readability of the subtitle text under "Welcome back to JOOwp" section on the home page so users can easily read the onboarding message without straining their eyes.

**Scope:** Update CSS/styling for the returning welcome section only. Does not include changes to the initial welcome screen or other page sections.

## Dependencies
- Existing Home.vue component (`resources/js/Pages/Home.vue`)
- Bootstrap dark theme utilities
- Current dark theme color palette

## Objectives
- Achieve WCAG AA contrast ratio (4.5:1) for the subtitle text under "Welcome back to JOOwp"
- Maintain visual hierarchy (heading remains prominent, subtitle readable but secondary)
- Preserve dark theme aesthetic and design consistency
- Ensure fix works across different screen sizes and display settings

## Tasks
- [ ] Phase 0 – Accessibility Audit
  - DoD: Measure current contrast ratio of `text-muted` class on dark background
  - DoD: Document target contrast ratio (WCAG AA minimum 4.5:1)
  - DoD: Identify alternative Bootstrap utility classes or custom CSS solution
  - Estimated: 1 hour

- [ ] Phase 1 – CSS Fix Implementation
  - DoD: Replace `text-muted` with higher-contrast utility class (e.g., `text-light`, `text-white-75`) or custom CSS
  - DoD: Verify contrast ratio meets WCAG AA standard using browser dev tools or contrast checker
  - DoD: Test on different screen sizes (mobile, tablet, desktop)
  - Estimated: 2 hours

- [ ] Phase 2 – Visual Testing & Validation
  - DoD: Manual visual inspection confirms text is easily readable
  - DoD: Screenshot comparison (before/after) documented
  - DoD: No regressions in other text elements on the page
  - Estimated: 1 hour

## Success Metrics
- **Accessibility:** Subtitle text achieves ≥4.5:1 contrast ratio against dark background
- **User Experience:** Text is easily readable without eye strain in normal viewing conditions
- **Design Consistency:** Fix maintains dark theme aesthetic and visual hierarchy
- **Quality Gates:** All lint, typecheck, and build checks pass

## Risks & Mitigations
| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Over-brightening text breaks visual hierarchy | Medium | Low | Use subtle opacity (75-85%) to maintain secondary status |
| Fix conflicts with Bootstrap dark theme | Low | Low | Test with Bootstrap's dark mode utilities first, fallback to custom CSS if needed |
| Inconsistent styling across sections | Low | Medium | Review all `text-muted` usage on home page for consistency |

## Related Plans
- `docs/plans/features/2025-11-11-home-screen.md` – Original home screen implementation

## Notes
- Current issue: Line 80 in `Home.vue` uses `text-muted` class which renders as very light gray (`rgba(255, 255, 255, 0.5)` or similar) on dark background
- Solution options: Use `text-light` with opacity, `text-white-75`, or custom CSS class with explicit color
- Consider creating a reusable utility class for secondary text in dark theme if this pattern appears elsewhere

