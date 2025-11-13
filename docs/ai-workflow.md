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
**Role:** Automated documentation and changelog generation
**Responsibilities:**
- Monitor git hooks for completed tasks
- Generate changelogs from plan completions
- Keep documentation in sync with code changes
- Update project status based on git activity

**Trigger:** Git hooks after approved commits
**Focus:** Plan file analysis and changelog generation

### Stage 5: Human (Final Approver)
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
- ❌ **Never commit partial work** - only complete, working features
- ❌ **Never modify files you didn't create** - explicit file staging only

## 🔗 Quick Navigation

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