## Commit Message Standards

To ensure consistency and traceability, all commit messages must adhere to the following standards:

> **Exact Format:** See [Standards Reference](../reference/standards.md#commit-message-format) for complete format specification.

### Format
1. **Title**:
   - Format: `<type>(<scope>): <description>` (scope is required)
   - Example: `feat(lmstudio): add inference API`
   - Types: `feat`, `fix`, `docs`, `test`, `refactor`, `style`, `chore`
   - Scope: Module or component name (e.g., `core`, `wordpress`, `lmstudio`, `app`, `config`, `plans`)

2. **Body**:
   - Provide a concise explanation of the change.
   - Include the reason for the change (why) and reference the related plan/task.
   - Example:
     ```
     Implement Phase 2 backend flow for LM Studio so the platform can request inferences, stream responses, 
     and expose console/HTTP entry points guarded by feature flags.

     - Added inference events, service, and controller backed by new FormRequest
     - Wired feature flag + config, routes, and SDK logging enhancements
     - Delivered artisan tooling, frontend demo page, and feature/unit tests
     ```

3. **Metadata** (if applicable):
   - Include additional metadata for traceability:
     - `Co-authored-by`: Name and email of co-author(s).
     - `Generated-By`: Tool or agent responsible for the commit.
     - `Task-ID`: Reference to the task or plan.
     - Example:
       ```
       Co-authored-by: Viet Vu <jooservices@gmail.com>
       Generated-By: ChatGPT Plus
       Task-ID: LMSTUDIO-P2
       ```

### Rules
- Every commit must reference a plan or task.
- Metadata fields are optional unless specified by the team.
- Ensure the commit message is clear and concise.

### Examples
#### Example 1: Simple Feature Commit
```
feat(lmstudio): add inference API (Task LMSTUDIO-P2)

Implement Phase 2 backend flow for LM Studio so the platform can request inferences, stream responses, 
and expose console/HTTP entry points guarded by feature flags.

Co-authored-by: Viet Vu <jooservices@gmail.com>
Generated-By: ChatGPT Plus
Task-ID: LMSTUDIO-P2
```

#### Example 2: Bug Fix Commit
```
fix(core): resolve namespace parsing issue in SDK

Fixes a bug where the LM Studio SDK failed to parse namespaces correctly during inference requests.

Task-ID: SDK-12
```

For more details, refer to `docs/ai-workflow.md` and `docs/plans/technical/2025-11-14-commit-message-metadata.md`.