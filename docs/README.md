# JOOwp Project Documentation

This directory contains **project-specific documentation** for the JOOservices WordPress platform.

## 📖 Master Documentation

For architecture principles, development guidelines, code quality standards, and implementation guides, see the **master documentation** in:

**→ [`../ai-workflow/`](../ai-workflow/README.md)**

The `ai-workflow/` directory contains reusable documentation that applies to all Laravel projects. It includes:
- Architecture principles and flow patterns
- Development guidelines and workflows
- Code quality standards and tooling
- Implementation guides (REST APIs, testing, security, etc.)

## 📋 Project-Specific Content

This `docs/` directory contains documentation specific to **this project only**:

### 📝 Plans
Implementation plans for features and technical work:
- [`plans/features/`](plans/features/) - Feature/product plans
- [`plans/technical/`](plans/technical/) - Technical/refactor/infrastructure plans

### 📚 Decisions
Architecture Decision Records (ADRs) documenting major architectural choices:
- [`decisions/`](decisions/) - Project-specific ADRs

### 🔍 Retrospectives
Post-mortems and lessons learned from production issues:
- [`retrospectives/`](retrospectives/) - Project-specific retrospectives

### 📖 Stories
Implementation stories and demos:
- [`stories/`](stories/) - Project-specific stories

## 🎯 Quick Navigation

| What you need | Where to find it |
|---------------|------------------|
| **Architecture principles** | [`../ai-workflow/architecture/principles.md`](../ai-workflow/architecture/principles.md) |
| **Development guidelines** | [`../ai-workflow/development/guidelines.md`](../ai-workflow/development/guidelines.md) |
| **Code quality standards** | [`../ai-workflow/reference/standards.md`](../ai-workflow/reference/standards.md) |
| **Implementation guides** | [`../ai-workflow/guides/`](../ai-workflow/guides/) |
| **AI workflow** | [`../ai-workflow/ai-workflow.md`](../ai-workflow/ai-workflow.md) |
| **Project plans** | [`plans/`](plans/) |
| **Project decisions** | [`decisions/`](decisions/) |
| **Project retrospectives** | [`retrospectives/`](retrospectives/) |

## 📁 Directory Structure

```
docs/
├── README.md              # This file
├── plans/                 # Implementation plans (project-specific)
│   ├── features/          # Feature/product plans
│   └── technical/         # Technical/refactor plans
├── decisions/            # Architecture Decision Records (project-specific)
├── retrospectives/       # Post-mortems (project-specific)
└── stories/              # Implementation stories (project-specific)

ai-workflow/               # Master documentation (reusable)
├── architecture/          # Principles and flow
├── development/           # Guidelines and code quality
├── guides/                # Implementation guides
└── reference/             # Standards and quick lookup
```

## 💡 Usage

1. **New to the project?** → Start with [`../ai-workflow/architecture/principles.md`](../ai-workflow/architecture/principles.md)
2. **Need to implement something?** → Check [`plans/`](plans/) for existing plans or create a new one
3. **Understanding past decisions?** → Review [`decisions/`](decisions/)
4. **Learning from issues?** → Read [`retrospectives/`](retrospectives/)

---

**Note:** The `ai-workflow/` directory is a Git submodule containing reusable documentation. Do not modify files in `ai-workflow/` directly. For project-specific content, add it here in `docs/`.
