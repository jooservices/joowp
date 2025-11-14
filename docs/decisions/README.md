# Architecture Decision Records (ADRs)

Documents key technical decisions and their rationale.

## What is an ADR?

An Architecture Decision Record captures important architectural decisions along with their context and consequences.

## Active ADRs

- [**2025-11-13: LM Studio API Selection**](2025-11-13-lm-studio-api.md) - Why we chose LM Studio over alternatives

## ADR Format

Each ADR should include:

- **Status:** Proposed | Accepted | Deprecated | Superseded
- **Context:** What problem are we solving?
- **Decision:** What did we decide?
- **Consequences:** What are the tradeoffs?
- **Alternatives Considered:** What else did we evaluate?

## Creating New ADRs

1. Name: `YYYY-MM-DD-decision-title.md`
2. Follow format above
3. Link from this README
4. Reference in related plans/guides

## Proposed Future ADRs

Based on current architecture:

- **ADR-002**: Service layer pattern (1 service = 1 business logic)
- **ADR-003**: Repository pattern for database access only
- **ADR-004**: Resource vs raw JSON response strategy
- **ADR-005**: No repository layer for external APIs
