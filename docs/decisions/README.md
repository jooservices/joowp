# Decisions

Architecture Decision Records (ADRs) documenting major architectural choices.

## What is an ADR?

An Architecture Decision Record captures important architectural decisions along with their context and consequences.

## Format

Each ADR should include:

```markdown
# ADR-NNN: Title

**Status:** Proposed | Accepted | Deprecated | Superseded

**Date:** YYYY-MM-DD

**Context:**
What is the issue we're facing?

**Decision:**
What did we decide to do?

**Consequences:**
What are the implications of this decision?

**Alternatives Considered:**
What other options did we evaluate?
```

## Existing Decisions

_(Coming soon)_

## Proposed ADRs

Based on current architecture:

- **ADR-001**: Service layer pattern (1 service = 1 business logic)
- **ADR-002**: Repository pattern for database access only
- **ADR-003**: Resource vs raw JSON response strategy
- **ADR-004**: No repository layer for external APIs
