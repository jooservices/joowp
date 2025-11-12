# JOOservices Platform

Modular Laravel 12 platform targeting PHP 8.4 with a Vue 3 + Inertia + Pinia front-end.

## Quick Start

### Prerequisites

- PHP 8.4+
- Node.js 20+
- Composer
- npm
- SQLite (for local development)
- Xdebug (for test coverage)

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/JOOservices/joowp.git
cd joowp

# 2. Install dependencies
composer install
npm install

# 3. Setup environment
cp .env.example .env
php artisan key:generate

# 4. Configure WordPress integration (optional)
# Edit .env and set:
# WP_URL=https://your-wordpress-site.com
# WORDPRESS_API_TIMEOUT=10
# WORDPRESS_API_USER_AGENT=CoreWordPressSdk/1.0
# WORDPRESS_API_NAMESPACE=wp/v2

# 5. Run migrations
php artisan migrate

# 6. Start development server
composer dev  # Runs server + queue + logs + vite in one command
```

### Development Commands

```bash
# Full development stack (server + queue + logs + vite)
composer dev

# Quality pipeline (MUST be green before commits)
composer lint  # Runs: pint → phpcs → phpmd → phpstan

# Individual quality tools
composer lint:pint        # Laravel Pint (auto-fix style)
composer lint:phpcs       # PHP_CodeSniffer (PSR-12)
composer analyze:phpmd    # PHPMD (design quality)
composer analyze:phpstan  # PHPStan (static analysis)

# Testing (MANDATORY before commits)
composer test                # Run all tests
composer test:coverage       # Run with coverage report
composer test:coverage-check # Enforce 80% minimum coverage
npm run typecheck           # TypeScript validation

# Frontend
npm run dev    # Vite dev server
npm run build  # Production build
```

## Code Quality Standards

**Every commit must:**
1. Pass quality pipeline: `composer lint`
2. Pass all tests with 80%+ coverage: `composer test:coverage-check`
3. Include unit tests for new code (no exceptions)
4. Use `final` classes with `declare(strict_types=1)` at top of **EVERY** PHP file
5. Pass TypeScript validation: `npm run typecheck`

**Coverage Requirements (Enforced by CI/CD):**
- Overall project: 80% minimum (build fails if below)
- Core module services: 95%
- API controllers: 90%
- FormRequests: 100%
- Models: 85%

**See [CONTRIBUTING.md](CONTRIBUTING.md) for detailed development workflow.**

## Architecture

### Modular Design
- **Module system:** `nwidart/laravel-modules` - each domain in its own module
- **Core module:** Provides shared services (WordPress SDK, utilities)
- **Module structure:** Routes, Controllers, Services, FormRequests, Tests
- **Module status:** Enabled/disabled via `modules_statuses.json`

### API Pattern
- **Response envelope:** `App\Http\Responses\ApiResponse` for all API responses
- **Format:** `{ "ok": true|false, "code": "domain.action", "status": 200, "message": "...", "data": {}, "meta": {} }`
- **Routes:** Auto-prefixed with `/api/v1` per module

### Frontend
- **Stack:** TypeScript-only Vue 3 + Inertia + Pinia
- **Theme:** Dark theme aesthetic
- **UI:** Bootstrap 5 + FontAwesome
- **Build:** Vite with module auto-discovery

### WordPress Integration
- **SDK:** Guzzle-based contract implementation (`Modules\Core\Services\WordPress\Sdk`)
- **Authentication:** JWT tokens via `wp_tokens` table
- **Always proxy through Laravel** - never call WordPress from frontend
- **Configuration:** Set via `config('core.wordpress')` or `.env` variables

## Documentation

### Core Guides
- [Engineering Principles](docs/principles.md) - Non-negotiable requirements
- [Code Quality Workflow](docs/code-quality.md) - Quality pipeline details
- [Contributing Guide](CONTRIBUTING.md) - Development workflow
- [AI Coding Instructions](.github/copilot-instructions.md) - AI assistant guidelines

### Feature Guides
- [Home Screen Guide](docs/guides/home-screen.md)
- [Core WordPress SDK](docs/guides/core-wordpress-sdk.md)
- [Study Case: Inertia Progress Regression](docs/guides/study-case-inertia-progress.md)

### Implementation Plans
- [Plans Directory](docs/plans/README.md)
- [Code Compliance Plan](docs/plans/2025-11-12-code-compliance.md)

## Key Features

- **Modular architecture** via `nwidart/laravel-modules`
- **Core WordPress SDK** powered by Guzzle for interacting with WordPress REST APIs
- **TypeScript-first Vue application** scaffold with Inertia and Pinia
- **Strict type safety** - PHP 8.4 strict types + TypeScript strict mode
- **Comprehensive quality gates** - Pint, PHPCS, PHPMD, PHPStan (max level)
- **Test coverage enforcement** - 80% minimum via CI/CD
- **Dual logging channels** - Action log (mutations) + External log (API calls)
- **Pre-commit quality hook** - Blocks commits with quality violations

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on:
- Development setup and workflow
- Code standards and quality gates
- Testing requirements and coverage targets
- Git commit guidelines and scope rules
- Pull request process

## License

This project is licensed under the MIT License.
