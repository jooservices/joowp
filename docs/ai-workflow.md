# AI Development Workflow - START HERE

> **🤖 ALL AI AGENTS MUST READ THIS FIRST**  
> This is your entry point to the JOOservices development workflow. Do not begin any work until you've completed the mandatory reading and understand your role.

## 📚 Mandatory Reading Order

### Phase 1: Workflow Understanding
1. **This document (`ai-workflow.md`)** - Your role, responsibilities, and handoff procedures
2. **`architecture/principles.md`** - Core engineering principles and "what/why" decisions
3. **`development/guidelines.md`** - Step-by-step implementation procedures and "how-to" patterns

### Phase 2: Reference Materials  
4. **`reference/standards.md`** - Quick lookup for exact rules, commands, and requirements
5. **`architecture/flow.md`** - Request/response patterns and service layer communication
6. **`development/code-quality.md`** - Quality pipeline tools and configurations

### Phase 3: Specialized Guides
7. **`guides/core-wordpress-sdk.md`** - WordPress integration examples (when relevant)
8. **Domain-specific plans** in `plans/` folder - Current project requirements

## 🔄 Multi-Agent Pipeline

### Stage 1: Cursor AI (Team Lead)
**Model Used:** [To Be Decided]
**Role:** Strategic planning, documentation management, architectural decisions
**Responsibilities:**
- Create project plans with atomic, testable tasks
- Update architectural documentation  
- Generate skeleton code structure
- Break down features into AI-implementable units

**Required Reading Focus:**
- Complete mandatory reading (all 8 documents)
- Deep focus on `architecture/` folder for decision-making context
- Review existing `plans/` to understand project direction

### Stage 2: ChatGPT Plus (Full Stack Developer)
**Model Used:** [To Be Decided]
**Role:** Feature implementation with atomic commits
**Responsibilities:**
- Implement tasks from Cursor AI's plans
- Create atomic commits for each completed task
- Update plan files with completion status
- Write tests and ensure quality pipeline passes
- Hand off completed work to GitHub Pro

**Required Reading Focus:**
- Complete mandatory reading (all 8 documents)
- Deep focus on `development/guidelines.md` for implementation patterns
- Master `reference/standards.md` for exact requirements
- Review `architecture/flow.md` for service layer patterns

**Critical Workflow:**
1. Pick specific task from plan
2. Implement feature following documented patterns
3. Ensure all quality tools pass (`composer lint && composer test:coverage-check`)
4. Create atomic commit with meaningful message
5. Update plan file marking task complete
6. Move to next task

### Stage 3: GitHub Pro (Code Reviewer)
**Model Used:** [To Be Decided]
**Role:** Quality enforcement and approval gateway
**Responsibilities:**
- Review all code against documented standards
- Validate adherence to architectural principles
- Approve or reject based on quality criteria
- Ensure consistency with project patterns

**Required Reading Focus:**
- Complete mandatory reading (all 8 documents)
- Deep focus on `architecture/principles.md` for validation criteria
- Master `reference/standards.md` for enforcement rules
- Understand `development/code-quality.md` for tool expectations

### Stage 4: LM Studio (Documentation Manager)
**Model Used:** [To Be Decided]
**Role:** Automated documentation and changelog generation
**Responsibilities:**
- Monitor git hooks for completed tasks
- Generate changelogs from plan completions
- Keep documentation in sync with code changes
- Update project status based on git activity

**Trigger:** Git hooks after approved commits
**Focus:** Plan file analysis and changelog generation

### Stage 5: Human (Final Approver)
**Model Used:** Not Applicable
**Role:** Ultimate quality gate and business decision maker
**Responsibilities:**
- Final review of completed features
- Business logic validation
- Approval for public GitHub release

## ✅ Quality Gates (Enforced at Every Stage)

### Pre-Commit Requirements (ChatGPT Plus):
```bash
composer lint              # All quality tools pass
composer test:coverage-check # 80%+ coverage maintained  
npm run typecheck          # TypeScript validation
npm run build              # Production build succeeds
```

### Review Criteria (GitHub Pro):
- Follows documented architectural patterns
- Meets coverage requirements per layer
- Atomic commits with meaningful messages
- Plan files updated accurately
- No quality tool violations

### Documentation Sync (LM Studio):
- Plan completion status matches git commits
- Changelog reflects actual implemented features
- Documentation consistency maintained

## 🎯 Decision Making Framework

### When Implementing Features:
1. **Check principles first:** Does this align with documented engineering decisions?
2. **Follow established patterns:** Use examples from guidelines, don't invent new approaches
3. **Reference standards:** Look up exact requirements rather than guessing
4. **Validate against quality gates:** Ensure all tools pass before commit

### When Unclear or Conflicting Information:
1. **Reference documentation hierarchy:** Principles > Guidelines > Standards
2. **Look for similar examples:** Check existing code and patterns
3. **Escalate to human:** If documentation conflicts or gaps exist

### Forbidden Practices:
- ❌ **Never assume requirements** - always reference documentation
- ❌ **Never bypass quality gates** - all tools must pass
- ❌ **Never commit partial work** - only complete, working sub-tasks
- ❌ **Never modify files you didn't create** - explicit file staging only
- ❌ **NEVER commit without explicit human approval** - AI agents MUST ask and wait
- ❌ **NEVER assume approval from previous messages** - Always ask after implementation, regardless of previous message patterns
- ❌ **NEVER commit based on pattern matching** - Only commit when human explicitly approves

## 🚫 CRITICAL: Commit Authorization

**ABSOLUTE RULE:** AI agents are **FORBIDDEN** from executing `git commit` without explicit human approval.

## AI Commit Execution Workflow

To ensure clarity and control over commit operations involving AI tools, the following workflow applies to all AI agents (e.g., ChatGPT, Claude, etc.):

1. **No Automatic Commits**:
   - AI agents must never execute a `git commit` without explicit permission from the user.

2. **Commit Execution Upon User Approval**:
   - When the user explicitly says "commit" or provides a similar approval, the AI agent will automatically execute the commit.
   - The commit message will be prepared in advance and used directly unless the user specifies otherwise.

3. **Quality Checks Before Commit**:
   - AI agents must ensure that all quality gates (e.g., linting, testing, type checking, building) are passed before executing the commit.

4. **Responsibility**:
   - The user retains control over when commits are executed, but AI agents handle the actual commit process once approval is given.

This workflow ensures that commits are deliberate, meet all quality standards, and minimize manual effort for the user.

### Atomic Commits Per Sub-task

Break features into tasks, commit each task separately:

```
Feature: User Authentication
├─ Task A1: User model + tests → Commit 1
├─ Task A2: UserRepository + tests → Commit 2
├─ Task A3: AuthService + tests → Commit 3
└─ Task A4: Controllers + tests → Commit 4
```

### Workflow for Each Sub-task:

1. **Complete ONE task completely**
   - Code implementation finished
   - Tests written and passing
   - Quality gates passed (`composer lint && composer test:coverage-check`)
   - **Do NOT proceed until 100% complete**

2. **Stage files for THAT task only**
   ```bash
   git add app/Models/User.php tests/Unit/Models/UserTest.php
   ```
   **Rule:** Stage ONLY when task is complete. Never stage work-in-progress.

3. **ALWAYS ask human for approval (MANDATORY)**
   - **Rule:** After ANY implementation, you MUST ask before committing
   - **Rule:** Do NOT rely on previous message patterns or assume approval
   - **Rule:** Ask in yes/no format: "Implementation complete. Ready to commit?"
   ```
   "Ready to commit? Task A1 complete: User model with validation
   
   Staged files:
   - app/Models/User.php  
   - tests/Unit/Models/UserTest.php
   
   Quality gates: ✅ All passed"
   ```

4. **WAIT for human response (MANDATORY)**
   - **MANDATORY:** Do NOT proceed until human responds
   - **Approve = any indication of agreement** (flexible, not pattern-based)
   - Valid approval examples: "commit", "yes", "ok", "go ahead", etc.
   - Do NOT rely on pattern matching from previous messages
   - Any other response = do NOT commit

5. **Execute commit (only after approval)**
   ```bash
   git commit -m "feat(app): add User model with validation

Generated-By: Cursor Pro
Generated-By-Tool: Cursor Pro
Model: Auto
Task-ID: AUTH-1
Plan: docs/plans/features/2025-11-14-user-authentication.md
Coverage: 85%"
   ```

6. **Repeat for next task**

### Examples:

✅ **CORRECT:**
```
[AI completes Task A1: User model]
[AI runs composer lint && composer test - all pass]
AI: git add app/Models/User.php tests/Unit/Models/UserTest.php
AI: "Implementation complete. Ready to commit? Task A1 complete: User model with validation
     
     Staged files:
     - app/Models/User.php
     - tests/Unit/Models/UserTest.php
     
     Quality gates: ✅ All passed"
[AI WAITS for human response]
Human: "commit" (or "yes", "ok", etc. - any approval indication)
AI: git commit -m "feat(app): add User model with validation

Generated-By: Cursor Pro
Generated-By-Tool: Cursor Pro
Model: Auto
Task-ID: AUTH-1
Plan: docs/plans/features/2025-11-14-user-authentication.md
Coverage: 85%"
```

❌ **WRONG:**
```
AI: "I've completed the work."
AI: [executes git commit without asking]
```

❌ **WRONG:**
```
AI: "Ready to commit?"
AI: [commits immediately without waiting for response]
```

❌ **WRONG:**
```
[AI completes implementation]
[User previously said "commit" for different task]
AI: [assumes approval based on previous pattern and commits]
# Should: Always ask again, regardless of previous messages
```

❌ **WRONG:**
```
AI: [stages 3 tasks at once and commits together]
# Should be: 1 task = 1 commit
```

### Enforcement:

- **Pre-commit hook:** Verifies human approval
- **CI/CD:** Rejects unauthorized commits
- **Code review:** Auto-commits = PR rejection

**Violation consequences:**
- Session terminated
- Changes rolled back  
- Incident logged for review

### Commit Scope Guidelines:

- **1-7 files:** Perfect (one sub-task)
- **7-15 files:** OK if same logical unit
- **15-30 files:** Must justify in message
- **>30 files:** SPLIT into multiple commits

**Remember:** 1 sub-task = 1 commit. Each commit must be atomic, complete, and independently reversible.

## � Plan File Update Protocol

### After Each Task Completion

**AI agents MUST update plan files to track progress:**

#### 1. Mark Task Complete

Update the task in plan file:

```markdown
# Before
- [ ] Task A1: User model with validation
  - DoD: Model with strict types, validation rules, tests
  - Estimate: 2 hours

# After  
- [x] Task A1: User model with validation
  - DoD: Model with strict types, validation rules, tests ✅
  - Status: Completed (2025-11-14, ChatGPT)
  - Commit: abc123f
```

#### 2. Add Completion Metadata

Include who completed and when:

```markdown
- [x] Task A2: UserRepository with CRUD
  - DoD: Repository with all CRUD methods, unit tests ✅
  - Status: Completed (2025-11-14, ChatGPT)
  - Commit: def456a
  - Duration: 1.5 hours
```

#### 3. Update Plan Status (If Applicable)

When all tasks in a phase complete:

```markdown
# At top of plan file

Status: In Progress → Active
Updated: 2025-11-14
Progress: Phase 1 complete (4/4 tasks)
```

#### 4. Stage Plan File WITH Code

Always include plan file in the same commit:

```bash
# Stage code + tests + plan file together
git add app/Models/User.php \
        tests/Unit/Models/UserTest.php \
        docs/plans/technical/2025-11-12-user-auth.md
```

#### 5. Reference in Commit Message

```bash
git commit -m "feat(app): add User model with validation

- Implement User model with strict types
- Add validation rules (email, password)
- Write comprehensive unit tests
- Update plan: mark Task A1 complete

Generated-By: Cursor Pro
Generated-By-Tool: Cursor Pro
Model: Auto
Task-ID: AUTH-1
Plan: docs/plans/features/2025-11-14-user-authentication.md
Coverage: 85%

Refs: docs/plans/technical/2025-11-12-user-auth.md"
```

### Complete Example Workflow:

```
[AI completes Task A1: User model]
[AI runs quality gates - pass]

# 1. Update plan file
AI: [Opens docs/plans/technical/2025-11-12-user-auth.md]
AI: [Changes - [ ] to - [x], adds completion metadata]

# 2. Stage code + plan together
AI: git add app/Models/User.php \
           tests/Unit/Models/UserTest.php \
           docs/plans/technical/2025-11-12-user-auth.md

# 3. Ask for approval
AI: "Ready to commit? Task A1 complete: User model with validation

     Staged files:
     - app/Models/User.php
     - tests/Unit/Models/UserTest.php
     - docs/plans/technical/2025-11-12-user-auth.md (updated)
     
     Quality gates: ✅ All passed"

# 4. Wait for human
[Human: "commit"]

# 5. Commit with plan reference
AI: git commit -m "feat(app): add User model with validation

- Implement User model with strict types
- Add validation rules
- Write unit tests
- Update plan: mark Task A1 complete

Generated-By: Cursor Pro
Generated-By-Tool: Cursor Pro
Model: Auto
Task-ID: AUTH-1
Plan: docs/plans/features/2025-11-14-user-authentication.md
Coverage: 85%"
```

### Plan Update Checklist:

Before committing each task:

- [ ] Task marked complete with `[x]`
- [ ] Completion metadata added (date, agent, commit)
- [ ] Plan status updated if needed
- [ ] Plan file staged WITH code files
- [ ] Commit message references plan update

**Why This Matters:**
- ✅ Progress is visible and trackable
- ✅ Git history links code to plan tasks
- ✅ Easy to see what's done vs pending
- ✅ Human can review progress at any time
- ✅ Plan stays in sync with codebase

## �🔗 Quick Navigation

- **Engineering Principles:** [architecture/principles.md](architecture/principles.md)
- **Implementation Guide:** [development/guidelines.md](development/guidelines.md)  
- **Quick Reference:** [reference/standards.md](reference/standards.md)
- **Service Patterns:** [architecture/flow.md](architecture/flow.md)
- **Quality Tools:** [development/code-quality.md](development/code-quality.md)
- **WordPress Integration:** [guides/core-wordpress-sdk.md](guides/core-wordpress-sdk.md)

## 🚀 Getting Started Checklist

### For All AI Agents:
- [ ] Read this workflow document completely
- [ ] Complete mandatory reading (8 documents)
- [ ] Understand your specific role and responsibilities
- [ ] Know which documentation to reference for decisions
- [ ] Understand quality gate requirements

### Before Starting Work:
- [ ] Identify your stage in the pipeline
- [ ] Review current project plans
- [ ] Understand handoff requirements to next stage
- [ ] Verify all tools and dependencies are configured

**Remember:** This is a multi-agent workflow. Your work quality affects every subsequent AI in the pipeline. Follow documented patterns precisely.