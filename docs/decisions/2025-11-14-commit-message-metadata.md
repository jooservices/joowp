# ADR-2025-11-14: Commit Message Metadata Enforcement

**Status:** Accepted  
**Date:** 2025-11-14  
**Related Plan:** `docs/plans/technical/2025-11-14-commit-message-metadata.md`

## Context

Multi-agent workflow (Cursor Pro, ChatGPT Plus, GitHub Pro, LM Studio) requires clear attribution and traceability. Without standardized metadata, it's impossible to track which agent/tool/model generated each commit, making debugging, accountability, and workflow optimization difficult.

During development, we identified that commits from different AI agents lacked consistent attribution, making it difficult to:
- Debug issues by tracing commit origin
- Measure productivity and quality by agent
- Optimize workflow handoffs between agents
- Maintain accountability in multi-agent pipeline

## Decision

Require standardized metadata block for all AI-generated commits with the following specifications:

1. **Enforcement Method:** `commit-msg` Git hook validates metadata presence and format before commit completes
2. **Scope:** All commits (AI-generated and human) - metadata is REQUIRED for all commits
3. **Required Fields:** 6 fields must be present:
   - `Generated-By` - Tool or agent responsible (e.g., "Cursor Pro", "ChatGPT Plus")
   - `Generated-By-Tool` - Tool name (e.g., "Cursor Pro", "GitHub Copilot")
   - `Model` - Model version (e.g., "Auto", "claude-sonnet-3.5-20241022", "gpt-4-turbo-2024-04-09")
   - `Task-ID` - Task reference (e.g., "SDK-1", "AUTH-2") or `N/A` if no task exists
   - `Plan` - Plan file path (e.g., "docs/plans/technical/2025-11-13-lm-studio-sdk.md") or `N/A` if no plan exists
   - `Coverage` - Test coverage percentage (e.g., "95%") or `N/A` if no code changes, or `Documentation` for docs-only commits
4. **Validation Rules:**
   - All fields must be present (cannot omit fields)
   - Task-ID and Plan cannot be blank (must use `N/A` if not applicable)
   - Coverage must be percentage, `N/A`, or `Documentation`
5. **Rollout Strategy:** Strict enforcement from Phase 2:
   - Phase 0: Specification and decision record (completed)
   - Phase 1: Documentation updates (completed)
   - Phase 2: Tooling enforcement with STRICT validation (completed)
   - Phase 3: Rollout communication and retrospective (in progress)

## Consequences

### Positive
- ✅ Clear traceability of commit origin (which agent/tool/model)
- ✅ Better accountability in multi-agent workflow
- ✅ Easier debugging by tracing commits to specific agents
- ✅ Workflow optimization through commit pattern analysis
- ✅ Consistent format across all AI-generated commits

### Negative
- ⚠️ Additional step for AI agents (mitigated by templates and helper scripts)
- ⚠️ Hook validation blocks commits without metadata (NO BYPASS - strict enforcement)
- ⚠️ Initial learning curve for agents (mitigated by comprehensive documentation and examples)
- ⚠️ No emergency escape - all commits must include metadata (mitigated by helper script)

## Alternatives Considered

### 1. Optional Metadata
**Rejected** - Defeats purpose of traceability. Without enforcement, agents may skip metadata, making it unreliable.

### 2. Post-commit Validation (CI/CD only)
**Rejected** - Too late, commits already in local history. Bad commits propagate through workflow before detection.

### 3. Pre-commit Hook Validation
**Rejected** - Pre-commit hook runs before commit message is finalized. Cannot access commit message content reliably.

### 4. Manual Checklist Only
**Rejected** - Relies on human memory and discipline. Not scalable for multi-agent workflow with high commit frequency.

### 5. commit-msg Hook (Selected)
**Accepted** - Runs after commit message is written but before commit completes. Can read and validate message, reject if invalid. Perfect timing for validation.

## Implementation Details

- **Hook Location:** `.git/hooks/commit-msg` (created from `hooks/commit-msg.sample`)
- **Validation Logic:** Checks for all 6 required fields, validates format, rejects if missing/invalid
- **Error Messages:** Clear guidance on what's missing and how to fix
- **Bypass:** ❌ **NO BYPASS ALLOWED** - All commits must pass metadata validation
- **Helper Tool:** `scripts/git-commit-template.sh` available to generate correct format
- **Technical Note:** `--no-verify` only bypasses `pre-commit` hook, NOT `commit-msg` hook (Git behavior)

## Related Documentation

- [Commit Message Standards](../reference/standards.md#commit-message-metadata)
- [AI Workflow Guide](../ai-workflow.md)
- [Development Guidelines](../development/guidelines.md#commit-message-format)
- [Implementation Plan](../plans/technical/2025-11-14-commit-message-metadata.md)

