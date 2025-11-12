# JOOwp Documentation

Complete documentation for the JOOservices WordPress platform.

## 📐 Architecture

Core system design and patterns.

- [**Principles**](architecture/principles.md) - Engineering principles, type safety, testing requirements
- [**Flow**](architecture/flow.md) - Request/response flow, service layer pattern, repository usage

## 🛠️ Development

Day-to-day developer workflow and tooling.

- [**Code Quality**](development/code-quality.md) - Quality pipeline (Pint → PHPCS → PHPMD → PHPStan)
- [**AI Workflow**](development/ai-workflow.md) - AI-augmented development with local models

## 📖 Guides

Step-by-step tutorials and how-to documentation.

- [**WordPress SDK**](guides/wordpress-sdk.md) - Using the WordPress REST API SDK

## 📚 Reference

API and component specifications.

_(Coming soon: API responses, SDK methods, logging, resources)_

## 🎯 Features

Feature implementation plans organized by business domain.

### WordPress
- [**Categories**](features/wordpress/categories.md) - Category management implementation
- [**Posts**](features/wordpress/posts.md) - Post management implementation
- [**Tags**](features/wordpress/tags.md) - Tag management implementation
- [**Media**](features/wordpress/media.md) - Media upload and management
- [**Home Screen**](features/wordpress/home-screen.md) - Home page UI/UX

### AI
- [**AI Content Suite**](features/ai-content-suite.md) - AI-powered content generation

## 🔧 Technical

Technical improvement and infrastructure plans.

- [**Caching Strategy**](technical/caching-strategy.md) - Caching implementation
- [**Code Compliance**](technical/code-compliance.md) - Code standards compliance
- [**Code Quality Remediation**](technical/code-quality-remediation.md) - Quality improvements
- [**Parent Category Dropdown**](technical/parent-category-dropdown.md) - UI component enhancement
- [**Realtime Socket Integration**](technical/realtime-socket-integration.md) - WebSocket implementation
- [**Strict Types Enforcement**](technical/strict-types-enforcement.md) - Type safety enforcement
- [**WordPress Module**](technical/wordpress-module.md) - WordPress module architecture

## 📝 Decisions

Architecture Decision Records (ADRs) documenting major architectural choices.

_(Coming soon: Service layer pattern, Repository pattern, Resource vs JSON)_

## 🔍 Retrospectives

Post-mortems and lessons learned from production issues.

- [**Inertia Progress Regression**](retrospectives/inertia-progress-regression.md) - SPA bootstrap failure analysis

---

## Quick Start

New to the project? Start here:

1. Read [**Principles**](architecture/principles.md) for engineering standards
2. Review [**Flow**](architecture/flow.md) to understand the architecture
3. Check [**Code Quality**](development/code-quality.md) for the development pipeline
4. Browse [**WordPress SDK**](guides/wordpress-sdk.md) for API integration

## Conventions

- **Architecture** - Long-lived system design documents
- **Development** - Workflow and tooling documentation
- **Guides** - Step-by-step tutorials
- **Reference** - API specifications and component docs
- **Features** - Implementation plans for user-facing features
- **Technical** - Infrastructure and improvement plans
- **Decisions** - ADRs documenting architectural choices
- **Retrospectives** - Post-mortems and lessons learned
