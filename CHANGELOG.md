# Changelog

All notable changes to JOOservices Platform will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- **Commit Message Metadata Enforcement** - All AI-generated commits now require standardized metadata block (Generated-By, Generated-By-Tool, Model, Task-ID, Plan, Coverage). `commit-msg` hook validates metadata format before commit completes. See `ai-workflow/reference/standards.md#commit-message-metadata` for format requirements and `hooks/commit-msg.sample` for validation logic.

## [0.0.1-beta] - 2025-11-12

### Added

#### Documentation & Principles
- **CONTRIBUTING.md** - Comprehensive contribution workflow guide (setup, branching, commit guidelines, PR process)
- **README.md** - Complete quick start guide, architecture overview, code quality standards, and development commands
- **Engineering Principles** - Core non-negotiable requirements:
  - Module organization by business domain (WordPress, AI, Product) not technical features
  - Mandatory Navbar with active state indication on all pages
  - Delete action requirements (red buttons, confirmation modals, appropriate icons)
  - Layout requirements with parent/child navigation hierarchy
- **AI Coding Instructions** - Detailed implementation guides with code examples:
  - Type Safety examples (PHP strict types + TypeScript)
  - Unit test patterns and structure
  - Complete delete action implementation (Bootstrap modal, error handling, loading states)
  - Creating Vue pages with Navbar integration

#### Implementation Plans
- **Strict Types Enforcement Plan** - Step-by-step guide to add `declare(strict_types=1)` to 21 files (45 min estimate)
- **Caching Strategy Plan** - 3-layer caching architecture (WordPress API, Database, Computed results) with 2-3 hour implementation estimate
- **Code Compliance Plan** - Comprehensive roadmap for quality gate compliance (coverage, strict types, final classes)
- **WordPress Module Extraction Plan** - Migration strategy to move WordPress code from Core to WordPress module
- **Posts Management Plan** - CRUD operations for WordPress posts
- **Categories Management Plan** - Taxonomy management with hierarchical display
- **Tags Management Plan** - Tag management and assignment
- **Media Management Plan** - WordPress media library integration
- **AI Content Suite Plan** - AI-powered content generation features

#### Quality Infrastructure
- **Pre-commit Hook** - Automated quality gates enforcing:
  - `declare(strict_types=1)` validation in PHP files
  - Laravel Pint auto-fix and re-staging
  - PHPCS (PSR-12), PHPMD, PHPStan validation
  - Test suite execution
  - TypeScript type checking
- **Git Workflow Documentation** - Explicit file staging, conventional commits, commit scope guidelines
- **.env.example** - WordPress integration variables with inline documentation:
  - `WP_URL`, `WORDPRESS_API_TIMEOUT`, `WORDPRESS_API_USER_AGENT`, `WORDPRESS_API_NAMESPACE`

#### Features
- **Taxonomy Categories Management UI** - WordPress categories page with hierarchical display aligned to WordPress structure
- **Taxonomy Navigation** - Dropdown navigation in Navbar (Categories, Tags, Posts, Media)
- **Home Screen** - Navigation cards layout with Footer
- **WordPress Token Management** - JWT token storage with "Remember token" functionality using input groups

### Changed

- **Module Architecture** - Clarified business domain organization:
  - Core = Technical infrastructure only (logging, base classes, HTTP responses)
  - Domain modules = Business-specific logic (WordPress SDK in WordPress module, not Core)
  - Decision rule: "Does this contain business-specific logic?" → YES = Domain module, NO = Core
- **CONTRIBUTING.md** - Refactored to remove code examples, reference AI Coding Instructions instead
- **Frontend Patterns** - Enhanced with:
  - Delete action implementation guide (modal, confirmation, error handling)
  - Layout requirements (mandatory Navbar, active states)
  - Icon variations for different contexts (trash, times, eraser, archive)
- **Categories Page** - Updated to use fluid layout with proper grid system

### Fixed

- **Storage Framework Cache** - Added .gitignore patterns to exclude:
  - `storage/framework/cache/*`
  - `storage/framework/sessions/*`
  - `storage/framework/testing/*`
  - `storage/framework/views/*`
  - `storage/framework/phpstan/*`
- **UI Layout** - Enforced `container-fluid` and Bootstrap grid usage across all pages

### Documentation Structure

```
docs/
├── principles.md           # Core engineering requirements (WHAT + WHY)
├── code-quality.md         # Quality pipeline details
├── guides/
│   ├── core-wordpress-sdk.md
│   ├── home-screen.md
│   └── study-case-inertia-progress.md
└── plans/
    ├── 2025-11-12-strict-types-enforcement.md
    ├── 2025-11-12-caching-strategy.md
    ├── 2025-11-12-code-compliance.md
    ├── 2025-11-12-wordpress-module.md
    ├── 2025-11-11-posts-management.md
    ├── 2025-11-11-categories-management.md
    ├── 2025-11-11-tags-management.md
    ├── 2025-11-11-media-management.md
    └── 2025-11-11-ai-content-suite.md

.github/
└── copilot-instructions.md # AI coding implementation guide (HOW)

CONTRIBUTING.md             # Contribution workflow
README.md                   # Quick start & overview
```

### Code Quality Standards

**Mandatory Requirements:**
- ✅ `declare(strict_types=1)` in ALL PHP files (pre-commit enforced)
- ✅ Final classes by default
- ✅ Test coverage ≥ 80% (enforced by CI/CD)
- ✅ Quality pipeline: Pint → PHPCS → PHPMD → PHPStan (max level)
- ✅ TypeScript strict mode for all frontend code

**Coverage Targets (Enforced):**
- Overall project: 80% minimum
- Core module services: 95%
- API controllers: 90%
- FormRequests: 100%
- Models: 85%

### Pending Implementation

**P0 - Critical:**
- [ ] Add coverage scripts to composer.json (`test:coverage`, `test:coverage-check`)
- [ ] Update phpunit.xml with `<coverage>` section
- [ ] Update phpcs.xml to exclude `bootstrap/cache`
- [ ] Add `declare(strict_types=1)` to 21 files (app, config, database, Modules/Core)
- [ ] Change 8 classes to `final` class
- [ ] Write tests to reach 80% coverage

**P1 - High:**
- [ ] Create GitHub Actions CI/CD workflow (`.github/workflows/quality.yml`)

**P2 - Medium:**
- [ ] Implement caching strategy (WordPress API, DB queries, computed results)
- [ ] Extract WordPress code from Core module to WordPress module

### Technical Debt

- 20+ files missing `declare(strict_types=1)` (plan exists)
- 8 non-final classes (violations identified)
- Test coverage ~30-40% (target: 80%)
- PHPCS violations due to bootstrap/cache scanning
- WordPress domain code in Core module (should be in WordPress module)

### Notes

- All documentation written in English
- Pre-commit hook can be bypassed with `--no-verify` (for meta-commits only)
- Git workflow: Explicit file staging, no `git add .` allowed
- Commit scope guideline: "Could this be reverted independently?" → Yes = good boundary

---

## Future Releases

### [0.0.2] - Planned
- Complete P0 tasks (coverage scripts, strict types, final classes, 80% coverage)
- GitHub Actions CI/CD pipeline
- WordPress module extraction

### [0.1.0] - Planned
- WordPress Posts management
- WordPress Media management
- Caching layer implementation
- AI content generation suite

---

**Legend:**
- `[0.0.1-beta]` - Initial beta release with core documentation and quality infrastructure
