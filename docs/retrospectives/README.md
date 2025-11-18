# Retrospectives

Post-mortems and lessons learned from production issues and incidents.

## What is a Retrospective?

A retrospective documents what went wrong, how it was fixed, and what we learned to prevent similar issues in the future.

## Existing Retrospectives

- [**Inertia Progress Regression**](inertia-progress-regression.md) - SPA bootstrap failure caused by incorrect helper usage
- [**Commit Message Metadata Enforcement**](2025-01-22-commit-message-metadata-enforcement.md) - Post-implementation review of mandatory commit metadata requirements

## Retrospective Template

```markdown
# Retrospective: [Issue Name]

**Date:** YYYY-MM-DD
**Severity:** Low | Medium | High | Critical
**Impact:** Description of user/system impact

## What Happened

Detailed description of the issue.

## Root Cause

Technical explanation of what caused the issue.

## Fix

How the issue was resolved.

## Lessons Learned

1. Key takeaway
2. Process improvement
3. Safeguard added

## Action Items

- [ ] Preventive measure 1
- [ ] Documentation update
- [ ] Test coverage added
```

## Key Lessons

1. **Never trust local stubs** - Always verify against actual package exports
2. **UI changes need browser verification** - Tests don't catch runtime bundle issues
3. **Document every regression** - Future work should check these failure modes
