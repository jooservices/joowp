# Plan – Commit Message Metadata Enforcement

Status: Active  
Priority: P1  
Owner: Platform Engineering  
Created: 2025-11-14  
Updated: 2025-11-14  
Target: 2025-11-18  
Epic: Developer Workflow

## Summary
Define and enforce a standardized commit message metadata block (agent attribution, tool, model, task/plan reference) so every commit clearly identifies its origin within the multi-agent workflow.

**Scope:** Documentation, tooling updates (pre-commit or lint), and checklists. No application runtime code changes.

## Dependencies
- `docs/ai-workflow.md`
- `docs/architecture/principles.md` (commit discipline section)
- Existing pre-commit hook infrastructure

## Objectives
- Document the required metadata schema appended to commit messages.
- Update tooling to validate presence/format of metadata during commits.
- Provide templates/examples for AI agents and humans.

## Tasks
- [ ] Phase 0 – Specification
  - DoD: Metadata schema drafted (fields, accepted values) and approved.
  - DoD: Decision record documenting rationale and rollout plan.
  - Estimated: 3 hours

- [ ] Phase 1 – Documentation Updates
  - DoD: Update `docs/ai-workflow.md`, `development/guidelines.md`, `reference/standards.md`, and `architecture/principles.md` with metadata requirements.
  - DoD: Provide commit templates/examples mirroring required format.
  - Estimated: 3 hours

- [ ] Phase 2 – Tooling Enforcement
  - DoD: Enhance pre-commit hook (or new Git hook) to validate metadata block.
  - DoD: Add optional helper script/template to compose commit messages.
  - DoD: Tests or manual checklist verifying hook behaviour.
  - Estimated: 4 hours

- [ ] Phase 3 – Rollout & Retrospective
  - DoD: Communicate change via README/CHANGELOG.
  - DoD: Schedule follow-up retrospective after first week of enforcement.
  - Estimated: 2 hours

## Success Metrics
- 100% of new commits include required metadata block.
- Hooks reject any commit missing metadata.
- Documentation and templates clearly describe the format.

## Risks & Mitigations
| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Hook false positives blocking urgent fixes | Medium | Low | Provide `--no-verify` escape with manual approval; add thorough tests |
| Agents forget new format initially | Low | Medium | Supply templates, update guidelines, and run awareness reminders |
| Metadata drift (field names change) | Medium | Low | Centralize schema in decision record and version it |

## Related Plans
- `docs/plans/technical/2025-11-13-lm-studio-sdk.md` – commits from LM Studio work must follow the new metadata standard.


