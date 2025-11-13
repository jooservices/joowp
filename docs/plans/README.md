# Plans Directory

This directory contains all project planning documents organized by type and chronologically by date.

## Directory Structure

```
docs/plans/
├── technical/          # Technical implementation plans
│   ├── 2025-11-12-parent-category-dropdown.md
│   ├── 2025-11-12-realtime-socket-integration.md  
│   ├── 2025-11-12-strict-types-enforcement.md
│   └── 2025-11-12-wordpress-module.md
└── features/           # Feature and product plans
    └── (future feature plans)
```

## File Naming Convention

**Format:** `YYYY-MM-DD-{plan-name}.md`

**Examples:**
- `2025-11-12-parent-category-dropdown.md`
- `2025-11-13-user-authentication-upgrade.md`

## Plan File Template

```markdown
# Plan – {Feature Name}

Status: {Active|Completed|On Hold|Cancelled}  
Owner: {Team/Person}  
Created: YYYY-MM-DD  
Updated: YYYY-MM-DD  

## Summary
- Brief description of what this plan achieves

## Objectives
- Clear, measurable goals
- Success criteria

## Tasks
- [ ] Task 1
- [ ] Task 2
- [x] Completed task

## Notes
- Important considerations
- Dependencies
- Risk factors
```

## Plan Types

### Technical Plans (`technical/`)
Implementation-focused plans for:
- Code refactoring and improvements
- Infrastructure changes
- Developer tooling
- Quality improvements
- Technical debt resolution

### Feature Plans (`features/`)
Product-focused plans for:
- New user features
- UI/UX improvements  
- Business logic changes
- Integration features
- User-facing functionality

## AI Workflow Integration

These plans integrate with our AI-driven development workflow:

1. **Cursor AI (Team Lead)** creates strategic plans
2. **ChatGPT Plus (Developer)** implements tasks atomically
3. **GitHub Pro (Reviewer)** validates completion
4. **LM Studio (Documentation)** tracks progress
5. **Human (Approver)** provides final sign-off

Each plan should have:
- ✅ Clear task boundaries for AI handoffs
- ✅ Measurable completion criteria  
- ✅ Status tracking for accountability
- ✅ Cross-references to related documentation

## References

- [AI Workflow Guide](../ai-workflow.md) - Complete AI development process
- [Task Completion Accountability](../architecture/principles.md#task-completion-accountability) - Principle #17
- [Writing Plans Guide](../guides/writing-plans.md) - Detailed planning guidelines