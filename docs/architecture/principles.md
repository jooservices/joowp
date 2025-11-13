# Engineering Principles

## Philosophy

JOOservices Platform is built on strict type safety, comprehensive testing, and automated quality gates. Every line of code must pass a rigorous pipeline before merge.

## 🎯 Quick Principles Summary

### Type Safety
- `declare(strict_types=1);` in ALL PHP files (no exceptions)
- Explicit types on all methods, `final` classes, `readonly` dependencies
- PHPStan level max

### Quality Pipeline  
- **Order:** Pint → PHPCS → PHPMD → PHPStan
- **Command:** `composer lint` (must pass before commit)
- Zero violations, no global suppressions

### Test Coverage
- **80%** overall, **95%** Core services, **90%** controllers, **100%** FormRequests
- New classes = new tests (no exceptions)
- **Command:** `composer test:coverage-check`

### Modular Architecture
- **One domain = one module** (WordPress, AI, Product)
- **Core = technical infrastructure only** (no business logic)
- **Decision rule:** Business logic? → Domain module. Generic tool? → Core module

### API Responses
- **Envelope pattern:** `ApiResponse::success(code, message, data)`
- **Resource for Models,** Raw JSON for mixed/aggregated data
- Consistent error structure

### Audit Logging
- **Log all mutations** with actor, before/after, metadata
- Action log (30d), External log (14d)
- **Pattern:** `$logger->log(operation, actor, before, after, metadata)`

### Third-Party Integration
- **Never call third-party APIs from frontend** - always proxy through Laravel
- **Use SDK contracts,** never HTTP clients directly
- Secure credential management, auto-injection

### Frontend Standards
- **TypeScript-only** with strict mode
- **Dark theme + Bootstrap + FontAwesome** exclusively
- All pages include Navbar, delete confirmations mandatory

### Service Layer
- **Flow:** Controller → Service → Repository/SDK
- **One service = one business domain**
- Repository only for database, SDK direct for external APIs

### SOLID Design
- **S** - Single Responsibility (one class, one reason to change)
- **O** - Open/Closed (extend behavior without modifying existing code)
- **L** - Liskov Substitution (subclasses must be substitutable for base)
- **I** - Interface Segregation (clients shouldn't depend on unused methods)
- **D** - Dependency Inversion (depend on abstractions, not concretions)

### AI Workflow
- **Atomic commits:** One complete task = one commit (no partial work)
- **Multi-stage quality gates:** Cursor → ChatGPT → GitHub Pro → LM Studio → Human
- **Zero tolerance:** No warnings, suppressions, or deprecations in production
- **Documentation sync:** Plans and completed tasks must always match

### Commit Discipline
- **Task atomicity:** Break features into small, independent tasks
- **File ownership:** Only commit files you directly created or modified
- **Meaningful messages:** Descriptive commit messages with type and scope
- **Working state:** Every commit must be deployable and testable
- **Rollback safety:** Any commit can be reverted independently

### Task Completion Accountability
- **Plan synchronization:** Completed features must be checked off in plan files
- **Status accuracy:** Plan status must reflect actual implementation state
- **Completion validation:** Feature marked complete only when fully tested and working
- **Handoff clarity:** Next AI agent knows exactly what's been finished

### API Documentation
- **Auto-generated:** API docs generated from code (FormRequests, Controllers, Resources)
- **Always current:** Documentation syncs automatically with code changes
- **Complete coverage:** All endpoints documented with request/response examples
- **Interactive testing:** Live API testing interface for developers

### API Versioning Discipline
- **Semantic versioning:** All API changes follow semantic versioning rules
- **Backward compatibility:** Maintain compatibility or use proper versioning
- **Deprecation warnings:** Clear warnings before removal of features
- **Multi-version support:** Maintain at least 2 versions simultaneously

### Defensive Programming
- **Validate everything:** All inputs, assumptions, and preconditions
- **Fail fast:** Explicit checks prevent corrupted state
- **No silent failures:** Every error condition explicitly handled

### Documentation as Code
- **PHPDoc for all public APIs:** Parameter descriptions and return types
- **Explain complex logic:** Inline comments for business rules
- **Exception documentation:** Document all throws declarations
- **No TODO comments:** Production branches must be clean

### Performance by Design
- **N+1 query prevention:** Database efficiency in all queries
- **Proper indexing:** Database indexes for all query patterns
- **Caching strategy:** Cache expensive operations
- **Async external calls:** No synchronous third-party calls in requests

### Security First
- **Input sanitization:** All user data validated and cleaned
- **SQL injection prevention:** No raw queries, use query builder
- **CSRF protection:** All state-changing endpoints protected
- **Rate limiting:** All public APIs have rate limits
- **No hardcoded secrets:** Secure credential management

### Validation & Quality
- **All validation in FormRequests** (100% coverage required)
- **Pre-commit:** lint + coverage + typecheck + build (all must pass)
- **Atomic commits** with explicit file staging

---

## 1. Type Safety

### 🎯 Principle: Type Safety
**What you must do:** All code must be type-safe with no implicit type coercion or ambiguous types.

**Why:** Type safety prevents runtime errors, improves IDE support, and makes code self-documenting. Explicit types eliminate entire classes of bugs before code runs and enable better refactoring tools.

### 📋 Guidelines: How to Achieve Type Safety

#### 1. File-Level Strict Mode
Enable strict type checking for every PHP file to prevent automatic type coercion and catch type mismatches at runtime.

#### 2. Explicit Type Declarations
Declare parameter and return types for all functions/methods. Never rely on type inference for public APIs or interfaces.

#### 3. Immutable Dependencies
Use readonly properties for injected dependencies to prevent accidental mutation and improve thread safety.

#### 4. Generic Type Annotations
Document array shapes and collection types using PHPDoc to help static analysis tools understand complex structures.

### ⚙️ Rules/Standards: Exact Implementation

#### MANDATORY Requirements:
- ✅ **MUST:** Add `declare(strict_types=1);` immediately after `<?php` in ALL PHP files
  - Applies to: Classes, interfaces, traits, routes, config, migrations, seeders, tests
  - Pre-commit hook enforces this (build fails without it)
  - No exceptions permitted

- ✅ **MUST:** Declare explicit types on all method parameters and return values
- ✅ **MUST:** Use `final` keyword for all classes by default  
- ✅ **MUST:** Use `readonly` modifier for constructor-injected dependencies

#### Forbidden Practices:
- ❌ **FORBIDDEN:** `mixed` type except when interfacing with untyped third-party code (requires inline comment with justification)
- ❌ **FORBIDDEN:** Missing parameter or return types on public methods
- ❌ **FORBIDDEN:** Relying on type inference for interface methods

#### PHPDoc Requirements:
- ✅ **MUST:** Document array shapes: `@param array<string, mixed> $data`
- ✅ **MUST:** Document return collections: `@return array<int, User>`

#### Tool Configuration:
- PHPStan level: `max` (no suppressions without justification)
- PHPStan rules: `checkMissingIterableValueType: true`, `checkGenericClassInNonGenericObjectType: true`

> **Implementation Details:** See [Development Guidelines](../development/guidelines.md#type-safety-implementation) for step-by-step implementation and code examples.

---

## 2. Quality Pipeline

### 🎯 Principle: Automated Quality Gates
**What you must do:** All code must pass automated quality checks before merge to maintain consistent standards.

**Why:** Automated gates catch issues early, enforce consistency across teams, and reduce review burden. Quality tools are faster and more thorough than manual review, ensuring maintainable code.

### 📋 Guidelines: How to Maintain Code Quality

#### 1. Fix in Priority Order
Run tools sequentially and fix issues before proceeding: Pint → PHPCS → PHPMD → PHPStan.

#### 2. Auto-Fix When Possible
Let Pint auto-fix style issues before manually addressing structural concerns.

#### 3. Understand Violations
Don't blindly suppress warnings - understand the issue and fix root cause.

#### 4. Configure Once, Enforce Everywhere
Maintain tool configurations in version control; never override locally.

### ⚙️ Rules/Standards: Quality Requirements

#### MANDATORY Requirements:
- ✅ **MUST:** All automated quality checks pass before commit (zero violations)
- ✅ **MUST:** Tools run in correct order to avoid conflicts
- ❌ **FORBIDDEN:** Global suppressions in configuration files
- ⚠️ **DISCOURAGED:** Line-level suppressions without inline justification

#### Quality Gate Coverage:
- **Code Style:** Canonical formatting and PSR-12 compliance
- **Design Quality:** SOLID principles and complexity metrics
- **Static Analysis:** Type safety and potential bugs

> **Implementation Details:** See [Development Guidelines](../development/guidelines.md#quality-pipeline-workflow) for step-by-step fixing procedures and [Code Quality](../development/code-quality.md) for complete tool configurations.

---

## 3. Test Coverage

### 🎯 Principle: Comprehensive Testing
**What you must do:** All code must be covered by automated tests that verify correctness and prevent regressions.

**Why:** Tests serve as living documentation, enable safe refactoring, and catch bugs before production. High coverage correlates with lower defect rates and improves system reliability.

### 📋 Guidelines: How to Achieve Comprehensive Testing

#### 1. Test Pyramid Strategy
Follow the testing pyramid: many unit tests, fewer integration tests, minimal E2E tests.

#### 2. Arrange-Act-Assert Pattern
Structure all tests with clear setup, execution, and verification phases for readability.

#### 3. Test Behavior, Not Implementation
Focus tests on public APIs and observable behavior, not internal implementation details.

#### 4. Mock External Dependencies
Isolate units under test by mocking external services, databases, and third-party APIs.

#### 5. Test Edge Cases
Include tests for error conditions, boundary values, and exceptional scenarios.

### ⚙️ Rules/Standards: Exact Implementation

#### MANDATORY Coverage Targets (CI-Enforced):

| Layer | Minimum Coverage | Rationale |
|-------|-----------------|-----------|
| **Overall Project** | **80%** | Baseline quality gate - CI fails below this |
| **Core Module Services** | **95%** | Critical shared infrastructure |
| **API Controllers** | **90%** | All endpoints must be integration tested |
| **FormRequests** | **100%** | Simple validation rules - no excuse for gaps |
| **Models (business logic)** | **85%** | Domain rules must be verified |
| **Repositories** | **80%** | Data access layer baseline |
| **Middleware** | **90%** | Request/response pipeline critical |
| **SDK Classes** | **95%** | Third-party integrations require thorough testing |

#### Coverage Exclusions:
- Service Providers (framework boilerplate)
- Migrations (schema definitions)
- Configuration files (data, not logic)
- Blade templates (frontend presentation)

#### New Code Requirements:
- ✅ **MUST:** Every new class requires accompanying unit tests before merge
- ✅ **MUST:** Pull requests that decrease coverage are automatically rejected
- ✅ **MUST:** Coverage gaps in modified code must be addressed in same PR

#### Test Commands:
```bash
composer test:coverage        # Generate HTML coverage report
composer test:coverage-check  # Enforce coverage thresholds (CI gate)
```

#### Test File Naming:
- Unit tests: `tests/Unit/{Namespace}/{ClassName}Test.php`
- Feature tests: `tests/Feature/{Module}/{Feature}Test.php`
- Test methods: `test_{method_name}_{scenario}_{expected_outcome}()`

> **Implementation Details:** See [Development Guidelines](../development/guidelines.md#test-coverage-implementation) for writing unit tests and achieving coverage targets.

---

## 4. Modular Architecture

### 🎯 Principle: Domain-Driven Modularity
**What you must do:** Organize code by business domain, not technical layer, with clear module boundaries.

**Why:** Domain-based organization scales better, improves maintainability, and makes business logic easier to find. Modules can be developed/tested/deployed independently, reducing coupling.

### 📋 Guidelines: How to Structure Modules

#### 1. One Domain = One Module
Each business domain gets its own self-contained module with all related code.

#### 2. Module Independence
Modules communicate through contracts/interfaces, never direct dependencies on concrete implementations.

#### 3. Core vs Domain Modules
Core module contains only technical infrastructure (no business logic). Domain modules contain business-specific logic.

#### 4. Decision Rule for Module Placement
Ask: "Does this contain business-specific logic?"
- **YES** → Business domain module (WordPress SDK → WordPress module)
- **NO** → Core module (ActionLogger → Core module)

### ⚙️ Rules/Standards: Module Organization

#### Module Naming and Structure:
- ✅ **MUST:** Singular PascalCase names matching business domain
- ✅ **MUST:** Self-contained modules with standardized structure
- ✅ **MUST:** Module activation controls which domains are enabled

#### Domain Classification Rules:
- ✅ **Core module:** Technical infrastructure only (ActionLogger, ApiResponse, BaseService)
- ✅ **Domain modules:** Business-specific logic (WordPress SDK, AI services, Product management)
- ❌ **FORBIDDEN:** Business logic in Core module
- ❌ **FORBIDDEN:** Technical infrastructure scattered across domain modules

#### Inter-Module Communication:
- ✅ **MUST:** Use contracts/interfaces for cross-module dependencies
- ❌ **FORBIDDEN:** Direct instantiation of classes from other modules

#### Decision Matrix:
| Question | Answer | Location |
|----------|--------|----------|
| Contains business rules? | Yes | Domain module |
| Generic technical tool? | Yes | Core module |
| WordPress-specific logic? | Yes | WordPress module |
| AI-specific logic? | Yes | AI module |

> **Implementation Details:** See [Development Guidelines](../development/guidelines.md#module-creation-workflow) for complete module setup procedures and directory structure requirements.

---

## 5. API Response Standardization

### 🎯 Principle: Consistent API Responses
**What you must do:** All API endpoints must use a standardized response format for predictable client integration.

**Why:** Standardized responses simplify frontend development, improve API usability, and enable consistent error handling across all endpoints.

### 📋 Guidelines: How to Standardize API Responses

#### 1. Use Envelope Pattern
Wrap all responses in a consistent structure with metadata, status, and data fields.

#### 2. Resource vs Raw JSON Strategy
Use Resources for Model data, raw JSON for non-Model aggregated data.

#### 3. Consistent Error Format
Use the same error structure across all endpoints for predictable error handling.

### ⚙️ Rules/Standards: Response Requirements

#### Response Structure Requirements:
- ✅ **MUST:** Use standardized envelope pattern for all API responses
- ✅ **MUST:** Include success/error indicators, status codes, and human-readable messages
- ✅ **MUST:** Consistent error structure across all endpoints

#### Data Strategy:
- ✅ **Use Resource classes:** When data maps directly to Eloquent Models
- ✅ **Use raw JSON:** For authentication responses, aggregated statistics, confirmations
- ❌ **FORBIDDEN:** Mixed response formats within same API

> **Implementation Details:** See [Development Guidelines](../development/guidelines.md#api-development-workflow) for complete endpoint examples and [Architecture Flow](flow.md) for Resource vs Raw JSON decision patterns.

---

## 6. Audit Logging

### 🎯 Principle: Comprehensive Audit Trail
**What you must do:** All domain mutations must be logged with actor, before/after state, and metadata.

**Why:** Audit trails provide accountability, enable debugging production issues, support compliance requirements, and help understand system usage patterns.

### 📋 Guidelines: How to Implement Audit Logging

#### 1. Log All Mutations
Record create, update, delete operations on domain entities with context.

#### 2. Capture State Changes
Store both before and after state to understand what changed.

#### 3. Include Actor Information
Always record who performed the action for accountability.

#### 4. Add Contextual Metadata
Include request IP, user agent, and other relevant context.

### ⚙️ Rules/Standards: Logging Requirements

#### Audit Trail Requirements:
- ✅ **MUST:** Log all domain mutations (create, update, delete operations)
- ✅ **MUST:** Include actor identification for accountability
- ✅ **MUST:** Capture before/after state for all changes
- ✅ **MUST:** Add contextual metadata for debugging and compliance

#### Log Channel Strategy:
- **Action logs:** Domain business mutations with actor and state tracking
- **External logs:** Third-party API calls with sanitized request/response data

#### Data Protection:
- ✅ **MUST:** Mask sensitive data (passwords, tokens, secrets) in all logs
- ✅ **MUST:** Follow data retention policies for different log types

> **Implementation Details:** See [Development Guidelines](../development/guidelines.md#audit-logging-implementation) for complete logging patterns and [Standards Reference](../reference/standards.md#audit-logging-standards) for configuration and retention policies.

---

## 7. Third-Party Integration Security

### 🎯 Principle: Secure Third-Party Proxy
**What you must do:** All third-party API interactions must be proxied through Laravel backend, never called directly from frontend.

**Why:** Direct frontend calls expose third-party API credentials, bypass Laravel's authentication/authorization, create CORS issues, and introduce security vulnerabilities. Proxying enables consistent logging, error handling, rate limiting, and access control across all external services.

### 📋 Guidelines: How to Integrate Third-Party APIs

#### 1. Backend Proxy Pattern
Route all third-party requests through Laravel API endpoints with proper authentication.

#### 2. Use SDK Contracts
Create and inject SDK contract interfaces, never use HTTP clients directly in business logic.

#### 3. Secure Credential Management
Store API keys, tokens, and secrets securely with proper rotation capabilities.

#### 4. Comprehensive Logging
Automatically log all external API calls for monitoring and debugging.

#### 5. Error Handling & Rate Limiting
Implement consistent error handling and respect third-party rate limits.

### ⚙️ Rules/Standards: Integration Security

#### Security Requirements:
- ✅ **MUST:** Proxy all third-party calls through Laravel backend
- ✅ **MUST:** Use SDK contract interfaces, never HTTP clients directly
- ✅ **MUST:** Store credentials securely with proper encryption and rotation
- ✅ **MUST:** Log all external API calls for monitoring and debugging

#### Architecture Requirements:
- ✅ **MUST:** Frontend → Laravel API → SDK Contract → Third-Party API flow
- ✅ **MUST:** SDK contracts for each third-party service with dependency injection
- ✅ **MUST:** Auto-inject authentication headers and handle rate limiting
- ❌ **FORBIDDEN:** Direct frontend calls to external APIs
- ❌ **FORBIDDEN:** API credentials exposed to frontend

#### Data Protection:
- ✅ **MUST:** Validate and sanitize all data before external API calls
- ✅ **MUST:** Implement proper error handling without leaking sensitive information
- ✅ **MUST:** Use HTTPS for all third-party communications

> **Implementation Details:** See [WordPress SDK Guide](../guides/core-wordpress-sdk.md) for complete SDK implementation example and [Standards Reference](../reference/standards.md#third-party-integration-standards) for security configuration details.

---

## 8. Frontend Standards

### 🎯 Principle: Type-Safe Dark Theme UI
**What you must do:** All frontend code must be TypeScript-only with consistent dark theme UI patterns.

**Why:** TypeScript prevents runtime errors and improves development experience. Consistent dark theme and UI patterns create professional user experience and reduce cognitive load.

### 📋 Guidelines: How to Build Frontend

#### 1. TypeScript-Only Development
No JavaScript files permitted - use TypeScript with strict mode enabled.

#### 2. Consistent UI Framework
Use Bootstrap + FontAwesome exclusively for all components and styling.

#### 3. Standard Interaction Patterns
Implement consistent loading states, error handling, and user feedback.

#### 4. Accessible Design Patterns
Use appropriate controls for different interaction types (switches vs checkboxes).

### ⚙️ Rules/Standards: Frontend Requirements

#### Type Safety Requirements:
- ✅ **MUST:** TypeScript-only with strict mode enabled (no JavaScript files)
- ✅ **MUST:** Explicit types for all variables, functions, and component props
- ❌ **FORBIDDEN:** Any usage of `any` type without explicit justification

#### UI Consistency Requirements:
- ✅ **MUST:** Dark theme aesthetic across all application surfaces
- ✅ **MUST:** Bootstrap + FontAwesome exclusively for components and icons
- ✅ **MUST:** Consistent interaction patterns (loading states, error handling, user feedback)
- ✅ **MUST:** Standard layout structure with navigation and responsive design

#### User Experience Standards:
- ✅ **MUST:** Appropriate control types for different interactions (switches vs checkboxes)
- ✅ **MUST:** Confirmation dialogs for destructive actions
- ✅ **MUST:** Accessible design patterns and keyboard navigation
- ❌ **FORBIDDEN:** Inconsistent UI patterns within same application

> **Implementation Details:** See [Development Guidelines](../development/guidelines.md#frontend-development-patterns) for complete component templates and [Standards Reference](../reference/standards.md#frontend-standards) for exact UI requirements and patterns.

---

## 9. Layered Request Architecture

### 🎯 Principle: Strict Layer Separation
**What you must do:** Follow Middleware → FormRequest → Controller → Service → Repository flow with single responsibility per layer.

**Why:** Strict layering prevents business logic from leaking into infrastructure concerns. Each layer handles one type of responsibility, making code predictable, testable, and maintainable. Business logic is centralized and isolated from HTTP, database, and cross-cutting concerns.

### 📋 Guidelines: Layer Responsibilities

#### 1. Middleware Layer
Handle cross-cutting concerns that apply to multiple endpoints: authentication, CORS, rate limiting, request logging.

#### 2. FormRequest Layer  
Validate input data and handle endpoint-specific authorization before business logic execution.

#### 3. Controller Layer
Orchestrate HTTP request/response cycle. Delegate all business decisions to services.

#### 4. Service Layer
Contain all business logic and domain rules. Coordinate between repositories and external services.

#### 5. Repository Layer
Handle data persistence operations only. Return domain models without business logic.

### ⚙️ Rules/Standards: Layer Boundaries

#### Request Flow (MANDATORY):
```
HTTP Request → Middleware → FormRequest → Controller → Service → Repository → Database
                   ↓            ↓           ↓         ↓          ↓
            Cross-cutting   Validation   HTTP      Business   Data
             Concerns                  Response    Logic    Access
```

#### Layer Isolation Rules:
- ✅ **MUST:** Controllers never call repositories directly
- ✅ **MUST:** Services never handle HTTP status codes or response formatting  
- ✅ **MUST:** Repositories never contain business logic or call other repositories
- ✅ **MUST:** Business logic exists only in Service layer
- ❌ **FORBIDDEN:** Database calls outside Repository layer
- ❌ **FORBIDDEN:** Business logic in Controller layer

#### Service Communication:
- ✅ **MUST:** Services call other Services for different business domains
- ✅ **MUST:** Services call SDKs directly for external APIs (no repository layer)
- ✅ **MUST:** One Service = One business domain responsibility

> **Implementation Details:** See [Architecture Flow](flow.md) for complete request examples and [Development Guidelines](../development/guidelines.md#layered-architecture-implementation) for step-by-step implementation patterns.

---

## 10. SOLID Design Principles

### 🎯 Principle: SOLID Object-Oriented Design
**What you must do:** Apply SOLID principles to create maintainable, extensible, and testable object-oriented code.

**Why:** SOLID principles reduce coupling, increase cohesion, and make code easier to understand, test, and modify. They prevent common design problems that lead to rigid, fragile, and hard-to-maintain codebases.

### 📋 Guidelines: How to Apply SOLID Principles

#### 1. Single Responsibility Principle (SRP)
Each class should have only one reason to change. One responsibility per class.

#### 2. Open/Closed Principle (OCP)  
Classes should be open for extension but closed for modification. Use interfaces and inheritance.

#### 3. Liskov Substitution Principle (LSP)
Subclasses must be substitutable for their base classes without breaking functionality.

#### 4. Interface Segregation Principle (ISP)
Clients should not be forced to depend on interfaces they don't use. Keep interfaces focused.

#### 5. Dependency Inversion Principle (DIP)
High-level modules should not depend on low-level modules. Both should depend on abstractions.

### ⚙️ Rules/Standards: SOLID Implementation

#### Single Responsibility Principle (SRP):
- ✅ **MUST:** One class = one business responsibility
- ✅ **MUST:** Each service handles only its domain logic (UserService → user logic only)
- ❌ **FORBIDDEN:** Classes with multiple reasons to change

#### Open/Closed Principle (OCP):
- ✅ **MUST:** Extend behavior through interfaces and inheritance
- ✅ **MUST:** Use Strategy pattern for varying algorithms
- ❌ **FORBIDDEN:** Modifying existing classes to add new features

#### Liskov Substitution Principle (LSP):
- ✅ **MUST:** Subclasses honor base class contracts and behavior expectations
- ✅ **MUST:** Same input/output behavior for substitutable classes
- ❌ **FORBIDDEN:** Subclasses that break parent class assumptions

#### Interface Segregation Principle (ISP):
- ✅ **MUST:** Create focused, role-specific interfaces
- ✅ **MUST:** Split large interfaces into smaller, cohesive ones
- ❌ **FORBIDDEN:** Fat interfaces that force unused dependencies

#### Dependency Inversion Principle (DIP):
- ✅ **MUST:** Depend on abstractions (interfaces), not concrete classes
- ✅ **MUST:** Inject dependencies through constructor with interface types
- ✅ **MUST:** Use Service Provider for binding abstractions to implementations

#### SOLID in Platform Architecture:
- **Module Organization:** One module = one business domain (SRP)
- **Extension Strategy:** Add modules without modifying existing ones (OCP)
- **Contract Communication:** Modules communicate through interfaces (DIP)

#### Validation Tools:
- **PHPMD:** Detects SRP violations and design complexity issues
- **PHPStan:** Enforces DIP through interface type checking
- **Architecture tests:** Validate dependency rules and module boundaries

> **Implementation Details:** See [Development Guidelines](../development/guidelines.md#solid-implementation-patterns) for detailed examples and code patterns, and [Standards Reference](../reference/standards.md#solid-design-standards) for tool configurations.

---

## 11. API Documentation Standards

### 🎯 Principle: Living API Documentation
**What you must do:** All API endpoints must be automatically documented and kept current with code changes.

**Why:** Manual documentation becomes outdated immediately. Auto-generated documentation ensures accuracy, reduces maintenance burden, and provides interactive testing capabilities. API consumers need reliable, current documentation to integrate successfully.

### 📋 Guidelines: Documentation Strategy

#### 1. Code-Driven Documentation
Generate API documentation automatically from existing code structure (FormRequests, Controllers, Resources).

#### 2. Zero-Maintenance Documentation
Documentation updates automatically when code changes, eliminating manual sync overhead.

#### 3. Complete Endpoint Coverage
Every API endpoint documented with request parameters, validation rules, response formats, and example data.

#### 4. Interactive Testing Interface
Provide live API testing capabilities for development and debugging.

### ⚙️ Rules/Standards: Documentation Requirements

#### Auto-Generation Requirements:
- ✅ **MUST:** API documentation generated from actual code (not separate files)
- ✅ **MUST:** FormRequest validation rules automatically documented
- ✅ **MUST:** Resource response formats automatically extracted
- ✅ **MUST:** Route parameters and descriptions included
- ❌ **FORBIDDEN:** Manual API documentation that can become outdated

#### Coverage Requirements:
- ✅ **MUST:** All public API endpoints documented
- ✅ **MUST:** Request/response examples for each endpoint
- ✅ **MUST:** Error response formats and codes documented
- ✅ **MUST:** Authentication requirements clearly specified

#### Accuracy Standards:
- ✅ **MUST:** Documentation reflects current code state (not aspirational)
- ✅ **MUST:** Validation rules match FormRequest implementations
- ✅ **MUST:** Response formats match actual Resource outputs
- ❌ **FORBIDDEN:** Outdated or inaccurate endpoint documentation

#### Recommended Tools (Laravel-Optimized):
- **Primary:** Scramble (`dedoc/scramble`) - Laravel-native auto-generation
- **Alternative:** Laravel API Documentation Generator
- **Fallback:** OpenAPI Generator with PHPDoc annotations

#### Documentation Access:
- ✅ **MUST:** API documentation accessible via `/api/documentation` route
- ✅ **MUST:** Interactive testing interface available
- ✅ **MUST:** Documentation updated on deployment
- ✅ **MUST:** Version-specific documentation for API versioning

> **Implementation Details:** See [Development Guidelines](../development/guidelines.md#api-documentation-setup) for tool installation and configuration, and [Standards Reference](../reference/standards.md#api-documentation-standards) for exact requirements.

---

## 12. API Versioning Discipline

### 🎯 Principle: Backward Compatibility and Version Control
**What you must do:** All API changes must maintain backward compatibility or use proper versioning to prevent breaking existing integrations.

**Why:** Breaking changes destroy client integrations and damage trust. Semantic versioning provides clear expectations for API consumers. Proper deprecation allows graceful migration. Multiple version support ensures smooth transitions for all clients.

### 📋 Guidelines: Versioning Strategy

#### 1. Semantic Versioning
Use semantic versioning (MAJOR.MINOR.PATCH) to communicate the impact of changes clearly.

#### 2. Backward Compatibility First
Prioritize backward-compatible changes whenever possible to minimize client disruption.

#### 3. Graceful Deprecation
Provide advance notice and migration paths before removing functionality.

#### 4. Multi-Version Support
Maintain multiple API versions simultaneously to support gradual client migration.

### ⚙️ Rules/Standards: Versioning Requirements

#### Semantic Versioning (MANDATORY):
- ✅ **MUST:** Follow semantic versioning for all API changes
- ✅ **MUST:** MAJOR version for breaking changes
- ✅ **MUST:** MINOR version for backward-compatible feature additions
- ✅ **MUST:** PATCH version for backward-compatible bug fixes
- ❌ **FORBIDDEN:** Breaking changes in MINOR or PATCH releases

#### Backward Compatibility:
- ✅ **MUST:** Maintain backward compatibility whenever technically possible
- ✅ **MUST:** Add new optional fields instead of modifying existing ones
- ✅ **MUST:** Preserve existing endpoint behavior in same version
- ❌ **FORBIDDEN:** Changing response structure without version increment

#### Deprecation Management:
- ✅ **MUST:** Provide deprecation warnings at least one MAJOR version before removal
- ✅ **MUST:** Include clear migration instructions in deprecation notices
- ✅ **MUST:** Document deprecation timeline and end-of-life dates
- ✅ **MUST:** Add `Deprecated` header to deprecated endpoints

#### Version Support:
- ✅ **MUST:** Maintain at least 2 MAJOR versions simultaneously
- ✅ **MUST:** Support current version + previous version minimum
- ✅ **MUST:** Provide clear version upgrade documentation
- ✅ **MUST:** Version-specific API documentation for each supported version

#### Breaking Change Guidelines:
- ✅ **Backward Compatible:** Adding optional fields, new endpoints, expanding enums
- ❌ **Breaking Changes:** Removing fields, changing field types, modifying validation rules
- ✅ **Required for Breaking:** MAJOR version increment + deprecation period

#### URL Versioning Strategy:
- ✅ **MUST:** Include version in URL path: `/api/v1/`, `/api/v2/`
- ✅ **MUST:** Route different versions to appropriate controllers
- ❌ **FORBIDDEN:** Header-based versioning (harder for clients to test)

> **Implementation Details:** See [Development Guidelines](../development/guidelines.md#api-versioning-implementation) for version management workflows and [Standards Reference](../reference/standards.md#api-versioning-standards) for exact versioning rules.

---

## 13. FormRequest Validation

### 🎯 Principle: Centralized Input Validation
**What you must do:** All input validation must be handled by FormRequest classes with 100% test coverage.

**Why:** Centralized validation ensures consistent rules, improves security by validating at entry points, and provides clear contract for API endpoints. FormRequests are simple enough to require 100% test coverage.

### 📋 Guidelines: How to Implement Validation

#### 1. Comprehensive Rule Definition
Define all validation rules in typed `rules()` method with clear documentation.

#### 2. Custom Error Messages
Provide user-friendly error messages for better API usability.

#### 3. Authorization Integration
Include authorization logic in `authorize()` method when needed.

#### 4. Complete Test Coverage
FormRequests must have 100% test coverage due to their simplicity.

### ⚙️ Rules/Standards: Validation Requirements

#### FormRequest Requirements:
- ✅ **MUST:** All input validation handled by FormRequest classes
- ✅ **MUST:** Typed rules method with complete PHPDoc annotations
- ✅ **MUST:** 100% test coverage for all FormRequests (simple validation rules)
- ✅ **MUST:** Custom error messages for better API usability

#### Validation Strategy:
- ✅ **MUST:** Define comprehensive validation rules with clear documentation
- ✅ **MUST:** Include authorization logic when endpoint requires access control
- ❌ **FORBIDDEN:** Validation logic scattered across controllers or services
- ❌ **FORBIDDEN:** FormRequests with incomplete test coverage

> **Implementation Details:** See [Development Guidelines](../development/guidelines.md#api-development-workflow) for complete FormRequest creation workflow and testing patterns.

---

## 14. Pre-Commit Workflow

### 🎯 Principle: Quality Gates Before Merge
**What you must do:** All code must pass quality pipeline and coverage checks before any commit.

**Why:** Preventing defects at commit time is exponentially cheaper than fixing them in production. Consistent quality gates ensure maintainable codebase and protect team productivity.

### ⚙️ Rules/Standards: MANDATORY Pre-Commit Checklist

```bash
# 1. Run quality pipeline
composer lint

# 2. Run tests with coverage enforcement  
composer test:coverage-check

# 3. TypeScript validation
npm run typecheck

# 4. Production build check
npm run build
```

**All 4 steps must pass** - no exceptions, no bypass permissions.

**CI/CD enforces:**
- All quality tools must pass (Pint → PHPCS → PHPMD → PHPStan)
- Test coverage ≥ 80% (fails build if below)
- No TypeScript errors
- Successful production build
- All files have `declare(strict_types=1)`

**Coverage violations = build failure:**
- Overall coverage drops below 80%
- Core services drop below 95%
- Controllers drop below 90%
- FormRequests below 100%
- Any new class without tests

---

## 15. Git Standards

### 🎯 Principle: Atomic Commits
**What you must do:** Only commit files you directly created or modified, with clear commit boundaries.

**Why:** Clean commit history enables easier debugging, code reviews, and rollbacks. Atomic commits prevent accidental inclusion of unrelated changes.

### ⚙️ Rules/Standards: Commit Discipline

#### File Staging:
- ✅ **CORRECT:** `git add specific-file.php specific-file2.md` (explicit file list)
- ❌ **WRONG:** `git add .` or `git add -A` (commits everything including others' work)

#### Commit Size Guidelines:
- **1-5 files:** Usually appropriate
- **5-15 files:** Acceptable if same feature (Controller + Service + Tests)
- **15-30 files:** Justify in commit message (e.g., new module setup)
- **>30 files:** Consider splitting into multiple commits

#### Commit Message Format:
```
<type>: <description>
```
**Types:** `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`

#### Decision Rule:
"Could this commit be reverted independently without breaking anything?"
- ✅ Yes → Good commit boundary
- ❌ No → Consider combining or splitting

---

## 16. Task Completion Accountability

### 🎯 Principle: Plan-Code Synchronization
**What you must do:** Update plan files immediately when features are completed to maintain accurate project state.

**Why:** AI agents rely on plan files to understand project status and make decisions about next steps. Outdated plans cause confusion, duplicate work, and broken handoffs. LM Studio needs accurate completion status to generate proper changelogs.

### 📋 Guidelines: Completion Tracking

#### 1. Immediate Plan Updates
Mark tasks complete in plan files as soon as implementation and testing are finished.

#### 2. Accurate Status Reflection
Plan status must precisely match actual implementation state - no optimistic marking.

#### 3. Completion Criteria
Features marked complete only when fully implemented, tested, and working in target environment.

#### 4. Handoff Preparation
Ensure next AI agent has clear understanding of what's been accomplished.

### ⚙️ Rules/Standards: Accountability Requirements

#### Plan Synchronization:
- ✅ **MUST:** Update plan files immediately after feature completion
- ✅ **MUST:** Mark specific tasks as ✅ COMPLETED with implementation details
- ✅ **MUST:** Include completion timestamp and responsible AI agent
- ❌ **FORBIDDEN:** Marking tasks complete before full implementation and testing

#### Completion Validation:
- ✅ **MUST:** Feature works in target environment (not just local)
- ✅ **MUST:** All tests pass for completed functionality
- ✅ **MUST:** Documentation updated for completed features
- ✅ **MUST:** Quality pipeline passes for all related code

#### Status Accuracy:
- ✅ **MUST:** Plan status reflects actual code state, never aspirational
- ✅ **MUST:** Include specific implementation notes (files changed, approach taken)
- ✅ **MUST:** Note any deviations from original plan requirements
- ❌ **FORBIDDEN:** Optimistic completion marking for partially working features

#### AI Handoff Information:
- ✅ **MUST:** Clear status for next AI agent: what's done vs what's remaining
- ✅ **MUST:** Document any blockers or dependencies for remaining tasks
- ✅ **MUST:** Include context about implementation decisions made
- ✅ **MUST:** Reference specific commits that implemented the completed tasks

#### LM Studio Integration:
- ✅ **MUST:** Plan updates trigger LM Studio to update changelogs
- ✅ **MUST:** Completion status enables proper git hook processing
- ✅ **MUST:** Task completion feeds into automated documentation generation

> **Implementation Details:** See [AI Workflow Guide](../guides/ai-development-workflow.md) for plan file formats and completion tracking procedures.

---

## 17. Commit Discipline

### 🎯 Principle: Atomic and Traceable Commits
**What you must do:** Break work into small, independent tasks with meaningful commits that can be safely rolled back.

**Why:** AI agents need clear task boundaries for handoffs. Atomic commits enable precise rollbacks, easier debugging, and better code review. Each commit should represent a complete, working unit that advances the project.

### 📋 Guidelines: Commit Strategy

#### 1. Task Decomposition
Break large features into small, independent tasks that can be completed and tested in isolation.

#### 2. File Ownership  
Only commit files you directly created or modified to avoid accidentally including others' work.

#### 3. Commit Completeness
Every commit must represent a working, testable state - never partial implementations.

#### 4. Message Clarity
Use descriptive commit messages that explain what was done and why.

#### 5. Traceability
Link commits back to specific plan tasks and requirements.

### ⚙️ Rules/Standards: Commit Requirements

#### Task Atomicity:
- ✅ **MUST:** One logical task = one commit (feature, bugfix, refactor)
- ✅ **MUST:** Task can be completed and tested independently  
- ✅ **MUST:** Commit represents working, deployable state
- ❌ **FORBIDDEN:** Partial implementations or work-in-progress commits

#### File Management:
- ✅ **MUST:** Stage specific files explicitly: `git add file1.php file2.md`
- ✅ **MUST:** Review staged changes before commit: `git diff --cached`
- ❌ **FORBIDDEN:** `git add .` or `git add -A` (commits everything)
- ❌ **FORBIDDEN:** Including files modified by other developers

#### Commit Message Standards:
- ✅ **MUST:** Format: `<type>(<scope>): <description>`
- ✅ **MUST:** Types: `feat`, `fix`, `docs`, `test`, `refactor`, `style`, `chore`
- ✅ **MUST:** Include task reference when applicable
- ✅ **MUST:** Explain what and why, not how

#### Rollback Safety:
- ✅ **MUST:** Any commit can be reverted without breaking functionality
- ✅ **MUST:** Related changes grouped in single commit (controller + test + docs)
- ✅ **MUST:** Dependencies and migrations included with feature code
- ❌ **FORBIDDEN:** Commits that require other commits to function

#### Quality Gates:
- ✅ **MUST:** All quality tools pass before commit
- ✅ **MUST:** Tests pass for all modified code
- ✅ **MUST:** Documentation updated for public API changes
- ❌ **FORBIDDEN:** Committing code that fails quality pipeline

#### Commit Size Guidelines:
- **1-5 files:** Ideal size for focused changes
- **5-15 files:** Acceptable for feature with tests and docs
- **15+ files:** Requires justification (new module, large refactor)
- **Decision rule:** "Could this be reverted independently?"

> **Implementation Details:** See [Standards Reference](../reference/standards.md#commit-standards) for exact commit message formats and [Development Guidelines](../development/guidelines.md#commit-workflow) for step-by-step procedures.

---

## 18. AI-Driven Development Workflow

### 🎯 Principle: Multi-Agent Quality Pipeline
**What you must do:** Follow strict AI handoff protocol with atomic commits and multi-stage review gates.

**Why:** AI-driven development requires crystal-clear boundaries and quality gates at each handoff. Atomic commits enable precise rollbacks and clear accountability between AI agents. Multi-stage review prevents defects from propagating through the pipeline.

### 📋 Guidelines: AI Agent Responsibilities

#### 1. Cursor AI (Team Lead)
Strategic planning, documentation management, architectural decisions, skeleton code structure.

#### 2. ChatGPT Plus (Full Stack Developer)  
Task implementation with atomic commits, soft review cycles, plan completion tracking.

#### 3. GitHub Pro (Code Reviewer)
Quality enforcement, approval/rejection decisions, production readiness validation.

#### 4. LM Studio (Documentation Manager)
Git hook automation, plan synchronization, changelog generation.

#### 5. Human (Final Approver)
Ultimate quality gate before public release.

### ⚙️ Rules/Standards: Workflow Requirements

#### Atomic Commit Requirements:
- ✅ **MUST:** One complete task = one commit (no partial implementations)
- ✅ **MUST:** Each commit passes all quality gates independently
- ✅ **MUST:** Commit messages reference specific plan tasks
- ❌ **FORBIDDEN:** Work-in-progress commits or incomplete features

#### Quality Gate Enforcement:
- ✅ **MUST:** All quality tools pass at each AI handoff
- ✅ **MUST:** Plans and completed tasks remain synchronized
- ✅ **MUST:** Documentation updates accompany code changes
- ❌ **FORBIDDEN:** Bypassing any stage in the AI review pipeline

#### AI Handoff Standards:
- ✅ **MUST:** Clear task boundaries for AI agent transitions
- ✅ **MUST:** Explicit acceptance criteria for each deliverable
- ❌ **FORBIDDEN:** Ambiguous requirements that cause AI confusion

> **Implementation Details:** See [AI Workflow Guide](../guides/ai-development-workflow.md) for detailed agent responsibilities and handoff procedures.

---

## 19. Zero Tolerance Quality

### 🎯 Principle: Perfect Code Quality
**What you must do:** Maintain absolutely zero warnings, deprecations, or suppressions in production code.

**Why:** Any tolerance for minor issues creates slippery slope to declining quality. AI agents need unambiguous pass/fail criteria. Zero tolerance ensures consistent quality standards across all AI-generated code.

### 📋 Guidelines: Quality Standards

#### 1. Absolute Static Analysis
No warnings of any kind from analysis tools - only clean passes.

#### 2. No Deprecated Usage
Proactively update deprecated functions before they become warnings.

#### 3. Suppression Control
Any suppression requires explicit justification and human approval.

### ⚙️ Rules/Standards: Zero Tolerance Enforcement

#### Static Analysis Requirements:
- ✅ **MUST:** PHPStan level max with zero violations (warnings + errors)
- ✅ **MUST:** PHPCS compliance with zero warnings
- ✅ **MUST:** PHPMD clean analysis with no design violations
- ❌ **FORBIDDEN:** Any `@SuppressWarnings` without mandatory code review

#### Deprecation Management:
- ✅ **MUST:** Monitor and fix deprecated function usage immediately
- ✅ **MUST:** Update dependencies before deprecation warnings appear
- ❌ **FORBIDDEN:** Deploying code with deprecation warnings

> **Implementation Details:** See [Standards Reference](../reference/standards.md#zero-tolerance-quality) for complete quality tool configurations.

---

## 20. Documentation as Code

### 🎯 Principle: Self-Documenting Code
**What you must do:** All code must be self-documenting with comprehensive PHPDoc annotations and clear inline comments.

**Why:** Self-documenting code improves maintainability, reduces onboarding time, and serves as living documentation that stays current with implementation. AI agents need comprehensive documentation to understand and maintain code effectively.

### 📋 Guidelines: Documentation Strategy

#### 1. Comprehensive API Documentation
Document all public methods with complete parameter descriptions, return types, and behavior explanations.

#### 2. Business Logic Comments
Explain complex business rules and domain logic with clear inline comments.

#### 3. Exception Documentation
Document all possible exceptions that methods can throw with conditions and handling guidance.

#### 4. Clean Production Code
Maintain production branches free of TODO comments and temporary documentation.

### ⚙️ Rules/Standards: Documentation Requirements

#### PHPDoc Requirements:
- ✅ **MUST:** PHPDoc annotations for all public methods with parameter descriptions and return types
- ✅ **MUST:** Document array shapes: `@param array<string, mixed> $data`
- ✅ **MUST:** Document return collections: `@return array<int, User>`
- ✅ **MUST:** Include `@throws` annotations for all exceptions that can be thrown

#### Inline Comment Standards:
- ✅ **MUST:** Explain complex business logic with clear comments above the code block
- ✅ **MUST:** Document algorithm choices and performance considerations
- ✅ **MUST:** Explain non-obvious code patterns and workarounds
- ❌ **FORBIDDEN:** Comments that simply restate what the code does

#### Exception Documentation:
- ✅ **MUST:** Document all exceptions in method PHPDoc: `@throws InvalidArgumentException When $id is negative`
- ✅ **MUST:** Include conditions that trigger each exception
- ✅ **MUST:** Provide guidance on how calling code should handle exceptions

#### Production Code Cleanliness:
- ✅ **MUST:** Production branches completely free of TODO comments
- ✅ **MUST:** All temporary documentation removed before merge
- ✅ **MUST:** Placeholder comments replaced with actual implementation details
- ❌ **FORBIDDEN:** Any TODO, FIXME, or HACK comments in production

> **Implementation Details:** See [Development Guidelines](../development/guidelines.md#documentation-standards) for PHPDoc templates and inline comment best practices.

---

## 21. Performance by Design

### 🎯 Principle: Performance-First Architecture
**What you must do:** Consider performance implications in all architectural decisions and implement efficient data access patterns.

**Why:** Performance debt is harder to fix than architectural debt. Proactive performance design prevents scalability bottlenecks and ensures consistent user experience as the system grows.

### 📋 Guidelines: Performance Strategy

#### 1. Database Efficiency
Design efficient database queries and prevent common performance anti-patterns like N+1 queries.

#### 2. Strategic Caching
Implement caching for expensive operations while maintaining data consistency.

#### 3. Asynchronous Processing
Use async patterns for external API calls and long-running operations.

#### 4. Proper Indexing
Design database indexes that support your query patterns.

### ⚙️ Rules/Standards: Performance Requirements

#### Database Performance:
- ✅ **MUST:** Prevent N+1 queries through eager loading and query optimization
- ✅ **MUST:** Create database indexes for all query patterns (WHERE, ORDER BY, JOIN clauses)
- ✅ **MUST:** Use Laravel's query builder or Eloquent ORM for SQL injection protection
- ✅ **MUST:** Monitor and optimize slow queries (>100ms threshold)

#### Caching Strategy:
- ✅ **MUST:** Cache expensive operations (external API calls, complex calculations, heavy queries)
- ✅ **MUST:** Implement cache invalidation strategy for data consistency
- ✅ **MUST:** Use appropriate cache TTL based on data volatility
- ✅ **MUST:** Cache at multiple levels (application, database, HTTP)

#### Asynchronous Processing:
- ✅ **MUST:** Use Laravel queues for external API calls in request cycle
- ✅ **MUST:** Implement async patterns for long-running operations
- ✅ **MUST:** Use background jobs for email sending, file processing, and third-party integrations
- ❌ **FORBIDDEN:** Synchronous external API calls that block HTTP responses

#### Resource Optimization:
- ✅ **MUST:** Optimize asset loading (CSS, JS minification and compression)
- ✅ **MUST:** Use appropriate HTTP status codes and caching headers
- ✅ **MUST:** Implement pagination for large data sets
- ❌ **FORBIDDEN:** Loading all records without pagination or limits

#### Performance Monitoring:
- ✅ **MUST:** Log slow queries and performance metrics
- ✅ **MUST:** Monitor external API response times and failure rates
- ✅ **MUST:** Set up alerts for performance degradation

> **Implementation Details:** See [Development Guidelines](../development/guidelines.md#performance-optimization) for caching patterns and async processing examples.

---

## 22. Security First

### 🎯 Principle: Security-by-Design
**What you must do:** Security must be a primary consideration in all design decisions with comprehensive protection against common vulnerabilities.

**Why:** Security vulnerabilities can compromise entire systems and user data. Building security into the foundation is more effective and cheaper than retrofitting security measures later.

### 📋 Guidelines: Security Strategy

#### 1. Input Validation and Sanitization
Validate and sanitize all user inputs to prevent injection attacks and data corruption.

#### 2. Authentication and Authorization
Implement comprehensive access control with proper session management.

#### 3. Data Protection
Protect sensitive data at rest and in transit with proper encryption and access controls.

#### 4. API Security
Secure all API endpoints with proper rate limiting and access controls.

### ⚙️ Rules/Standards: Security Requirements

#### Input Security:
- ✅ **MUST:** Validate and sanitize all user inputs using Laravel's validation system
- ✅ **MUST:** Use parameterized queries or ORM to prevent SQL injection
- ✅ **MUST:** Sanitize data before output to prevent XSS attacks
- ✅ **MUST:** Implement strict input type checking and range validation

#### SQL Injection Prevention:
- ✅ **MUST:** Use Laravel's query builder or Eloquent ORM exclusively
- ✅ **MUST:** Use parameter binding for any raw SQL queries
- ❌ **FORBIDDEN:** String concatenation for building SQL queries
- ❌ **FORBIDDEN:** Direct user input in database queries without validation

#### CSRF and State Protection:
- ✅ **MUST:** CSRF protection enabled on all state-changing endpoints
- ✅ **MUST:** Proper session management with secure session configuration
- ✅ **MUST:** Use Laravel's built-in CSRF token validation
- ✅ **MUST:** Implement proper logout and session invalidation

#### API Security:
- ✅ **MUST:** Rate limiting on all public API endpoints
- ✅ **MUST:** Proper authentication on all protected endpoints
- ✅ **MUST:** Input validation through FormRequest classes
- ✅ **MUST:** Implement proper API versioning and deprecation

#### Credential Management:
- ✅ **MUST:** Store all secrets and API keys in secure environment variables
- ✅ **MUST:** Use Laravel's encryption for sensitive data storage
- ✅ **MUST:** Implement credential rotation policies
- ❌ **FORBIDDEN:** Hardcoded passwords, API keys, or secrets in code
- ❌ **FORBIDDEN:** Sensitive data in configuration files committed to version control

#### Data Protection:
- ✅ **MUST:** HTTPS for all communications (no HTTP in production)
- ✅ **MUST:** Encrypt sensitive data at rest using Laravel's encryption
- ✅ **MUST:** Implement proper access logging for sensitive operations
- ✅ **MUST:** Follow data retention and deletion policies

#### Security Headers:
- ✅ **MUST:** Implement security headers (HSTS, CSP, X-Frame-Options)
- ✅ **MUST:** Proper CORS configuration for API endpoints
- ✅ **MUST:** Content-Type validation for file uploads

> **Implementation Details:** See [Development Guidelines](../development/guidelines.md#security-implementation) for complete security patterns and [Standards Reference](../reference/standards.md#security-standards) for configuration details.

---

## 23. Defensive Programming

### 🎯 Principle: Fail Fast and Explicit
**What you must do:** Validate all inputs and assumptions with explicit checks that fail immediately when violated.

**Why:** Defensive programming prevents corrupted state and makes debugging easier. AI-generated code needs explicit validation to catch edge cases. Failing fast prevents cascading failures and data corruption.

### 📋 Guidelines: Defensive Strategies

#### 1. Input Validation
Validate all method parameters at entry points with clear error messages.

#### 2. Precondition Checking
Assert all assumptions about system state before proceeding with operations.

#### 3. Null Safety
Explicit null checks before object usage to prevent null pointer exceptions.

#### 4. Exception Clarity
Throw specific exceptions with detailed context about what failed and why.

### ⚙️ Rules/Standards: Defensive Implementation

#### Validation Requirements:
- ✅ **MUST:** Validate all method parameters with type and range checks
- ✅ **MUST:** Assert preconditions using clear assertion messages
- ✅ **MUST:** Explicit null checks before object method calls
- ❌ **FORBIDDEN:** Silent failures or assumption-based logic

#### Error Handling Standards:
- ✅ **MUST:** Throw specific exceptions (not generic Exception)
- ✅ **MUST:** Include context in exception messages (what failed, why, how to fix)
- ✅ **MUST:** Log all errors with sufficient debugging context
- ❌ **FORBIDDEN:** Empty catch blocks or ignored exceptions

#### Resource Management:
- ✅ **MUST:** Explicit cleanup of database connections, file handles, external resources
- ✅ **MUST:** Try-finally blocks for guaranteed resource cleanup
- ❌ **FORBIDDEN:** Resource leaks or unclosed connections

> **Implementation Details:** See [Development Guidelines](../development/guidelines.md#defensive-programming-patterns) for validation patterns and error handling examples.

---

## Key Documentation

- **[Development Guidelines](../development/guidelines.md)** - Step-by-step implementation workflows
- **[Standards Reference](../reference/standards.md)** - Quick lookup for all concrete rules  
- **[Architecture Flow](flow.md)** - Request/response patterns and service layer
- **[Code Quality](../development/code-quality.md)** - Detailed tooling configuration
- **[WordPress SDK Guide](../guides/core-wordpress-sdk.md)** - WordPress integration examples

**Read all documentation before starting any task.**
