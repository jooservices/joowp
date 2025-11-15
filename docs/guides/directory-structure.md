# Documentation Directory Structure Guide

## Overview

This guide explains the purpose and organization of each directory within the `docs/` folder, following the **Principle → Guideline → Standard** hierarchy.

---

## 📐 `/docs/architecture/` - Long-Lived Design Decisions

**Purpose:** Documents that define **What & Why** - engineering principles and architectural patterns.

**Contents:**
- `principles.md` - 24 engineering principles with embedded guidelines and standards
- `flow.md` - Request/response flow patterns, service layer architecture

**When to Use:**
- Understanding engineering standards and rationale
- Learning why decisions were made
- Reference for architectural patterns

**Characteristics:**
- Long-lived (rarely change)
- High-level (What & Why)
- Foundation for all other docs

---

## 🛠️ `/docs/development/` - Workflow & Tooling

**Purpose:** Documents that explain **How to Implement** - step-by-step procedures and tooling.

**Contents:**
- `guidelines.md` - Step-by-step implementation workflows and examples
- `code-quality.md` - Quality pipeline configuration and tooling setup

**When to Use:**
- Need step-by-step implementation instructions
- Understanding quality pipeline tools
- Learning implementation patterns

**Characteristics:**
- Practical (How-to)
- Examples and workflows
- Tool-specific configurations

---

## 📚 `/docs/reference/` - Quick Lookup

**Purpose:** Documents for **Quick Reference** - exact requirements, numbers, and configurations.

**Contents:**
- `standards.md` - All concrete rules, coverage targets, tool configs, exact requirements

**When to Use:**
- Need exact numbers (coverage targets, tool versions)
- Quick lookup for specific rules
- Finding exact format requirements

**Characteristics:**
- Quick lookup format
- Exact specifications
- Numbers and configs
- Single source of truth for rules

---

## 📖 `/docs/guides/` - Step-by-Step Tutorials

**Purpose:** Feature-specific tutorials with complete code examples.

**Contents:**
- `core-wordpress-sdk.md` - WordPress REST API integration patterns
- `lm-studio-sdk.md` - Local AI model integration and usage
- `writing-plans.md` - How to write technical implementation plans
- `directory-structure.md` - This guide

**When to Use:**
- Learning how to use specific SDKs
- Following complete working examples
- Understanding best practices for specific features

**Characteristics:**
- Tutorial format
- Complete code examples
- Feature-specific
- Best practices and patterns

**Note:** Guides show **HOW** to build, while Plans define **WHAT** to build.

---

## 📋 `/docs/plans/` - Implementation Plans

**Purpose:** Project planning documents organized by type and date.

**Structure:**
```
plans/
├── technical/      # Technical implementation plans
└── features/       # Feature and product plans
```

### `/docs/plans/technical/` - Technical Plans

**Purpose:** Implementation-focused plans for:
- Code refactoring and improvements
- Infrastructure changes
- Developer tooling
- Quality improvements
- Technical debt resolution

**File Naming:** `YYYY-MM-DD-{plan-name}.md`

**Examples:**
- `2025-11-12-strict-types-enforcement.md`
- `2025-11-13-lm-studio-sdk.md`
- `2025-11-14-core-http-client.md`

### `/docs/plans/features/` - Feature Plans

**Purpose:** Product-focused plans for:
- New user features
- UI/UX improvements
- Business logic changes
- Integration features
- User-facing functionality

**File Naming:** `YYYY-MM-DD-{plan-name}.md`

**Examples:**
- `2025-11-14-ai-content-suite.md`
- `2025-11-14-home-welcome-text-readability.md`

**Plan Structure:**
- Status, Owner, Created/Updated dates
- Summary and Objectives
- Tasks with Definition of Done
- Dependencies and Risks

---

## 📝 `/docs/decisions/` - Architecture Decision Records (ADRs)

**Purpose:** Documents key technical decisions and their rationale.

**Contents:**
- `2025-11-13-lm-studio-api.md` - Why we chose LM Studio over alternatives
- `README.md` - ADR format and guidelines

**When to Use:**
- Understanding why architectural choices were made
- Documenting major technical decisions
- Recording alternatives considered

**ADR Format:**
- Status (Proposed | Accepted | Deprecated | Superseded)
- Context (problem statement)
- Decision (what was decided)
- Consequences (tradeoffs)
- Alternatives Considered

**File Naming:** `YYYY-MM-DD-decision-title.md`

---

## 🔍 `/docs/retrospectives/` - Post-Mortems & Lessons Learned

**Purpose:** Documents what went wrong, how it was fixed, and lessons learned.

**Contents:**
- `inertia-progress-regression.md` - SPA bootstrap failure analysis
- `2025-11-15-lm-studio-workflow-violations.md` - Workflow violation lessons
- `README.md` - Retrospective template and guidelines

**When to Use:**
- Learning from past issues
- Understanding failure modes
- Preventing similar problems

**Retrospective Format:**
- Date, Severity, Impact
- What Happened
- Root Cause
- Fix
- Lessons Learned
- Action Items

---

## 📚 `/docs/stories/` - User Stories & Demos

**Purpose:** User stories, demos, and narrative documentation.

**Contents:**
- `lmstudio-streaming-demo.md` - Demo scenarios and user stories

**When to Use:**
- Understanding user-facing scenarios
- Demo documentation
- Narrative documentation

---

## 📄 Root Level Documentation Files

### `/docs/README.md` - Documentation Index

**Purpose:** Main entry point and navigation for all documentation.

**Contents:**
- Documentation hierarchy explanation
- Quick start workflows
- Common scenarios and navigation
- Links to all major docs

**When to Use:**
- First time reading docs
- Finding the right document
- Understanding documentation structure

---

### `/docs/ai-workflow.md` - AI Development Workflow

**Purpose:** ⭐ **START HERE** for all AI agents - multi-agent pipeline workflow.

**Contents:**
- Mandatory reading order
- Multi-agent pipeline (5 stages)
- Quality gates
- Commit authorization rules
- Plan file update protocol

**When to Use:**
- Starting work as AI agent
- Understanding AI workflow
- Learning commit authorization

**Critical:** All AI agents MUST read this first.

---

### `/docs/ai-infrastructure-setup.md` - AI Infrastructure

**Purpose:** Personal hardware/tools configuration for AI development.

**When to Use:**
- Setting up development environment
- Configuring AI tools

---

### `/docs/ai-models.md` - AI Model Selection

**Purpose:** AI model selection and role assignments.

**When to Use:**
- Understanding which AI model to use
- Role assignments

---

## Documentation Hierarchy Flow

```
📐 Architecture (What & Why)
    ↓
🛠️ Development (How - Approach)
    ↓
📚 Reference (How - Exact)
    ↓
📖 Guides (Tutorials)
```

**Example Flow:**
1. **Principles** → "We need type safety" (What & Why)
2. **Guidelines** → "How to add strict types" (How - Approach)
3. **Standards** → "`declare(strict_types=1);` in ALL files" (How - Exact)
4. **Guides** → "Complete example with all parameters" (Tutorial)

---

## Decision Rules: Where to Put New Documentation?

### 1. Is it a long-lived design decision?
- **Yes** → `/docs/architecture/`
- **No** → Continue

### 2. Is it a step-by-step implementation guide?
- **Yes** → `/docs/development/`
- **No** → Continue

### 3. Is it exact requirements/numbers/configs?
- **Yes** → `/docs/reference/`
- **No** → Continue

### 4. Is it a feature-specific tutorial with examples?
- **Yes** → `/docs/guides/`
- **No** → Continue

### 5. Is it an implementation plan?
- **Yes** → `/docs/plans/technical/` or `/docs/plans/features/`
- **No** → Continue

### 6. Is it an architectural decision record?
- **Yes** → `/docs/decisions/`
- **No** → Continue

### 7. Is it a post-mortem or lesson learned?
- **Yes** → `/docs/retrospectives/`
- **No** → Continue

### 8. Is it a user story or demo?
- **Yes** → `/docs/stories/`
- **No** → Root level or create new category

---

## Quick Reference Table

| Directory | Purpose | Question Answered | When to Use |
|-----------|---------|-------------------|-------------|
| `architecture/` | What & Why | "What must we do?" / "Why?" | Understanding principles |
| `development/` | How to Implement | "How do I implement?" | Step-by-step workflows |
| `reference/` | Quick Lookup | "What's the exact requirement?" | Exact numbers/rules |
| `guides/` | Tutorials | "How do I use X?" | Feature-specific examples |
| `plans/technical/` | Technical Plans | "What technical work is planned?" | Implementation planning |
| `plans/features/` | Feature Plans | "What features are planned?" | Product planning |
| `decisions/` | ADRs | "Why did we choose X?" | Understanding decisions |
| `retrospectives/` | Lessons Learned | "What went wrong?" | Learning from issues |
| `stories/` | User Stories | "How does user interact?" | Demo scenarios |

---

## Related Documentation

- [Documentation README](../README.md) - Main documentation index
- [AI Workflow](../ai-workflow.md) - Multi-agent development workflow
- [Writing Plans Guide](writing-plans.md) - How to write plans
- [Architecture Principles](../architecture/principles.md) - Engineering principles

