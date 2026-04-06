# Changelog

All notable changes to Zen Framework will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-04-07

### Added

#### Core Framework
- Container with dependency injection
- Configuration loader with dot-notation support
- Custom autoloader with namespace support
- Routing system with strict file separation (Web, Api, Auth, Ai)
- Route parameters and named routes
- Route groups

#### HTTP Layer
- Request class with input parsing
- Response class with JSON support
- Redirect class

#### Database & ORM
- QueryBuilder with query methods (select, where, joins, aggregates)
- Model base class with ORM features
- Builder for query chaining
- Paginator for pagination
- Migration runner with rollback support
- Seeder class
- Factory class with fake data generation

#### Validation
- Validator with 28+ rules
- FormRequest base class

#### Session & Auth
- Session manager
- Authentication system (login, logout, attempt)
- CSRF middleware

#### Cache
- Cache class with file and array drivers
- Remember, increment, decrement methods

#### UI System
- Page base class
- Component base class
- Layout class

#### AI Integration
- AI class supporting OpenAI, Anthropic, and Ollama
- Chat, JSON, and stream methods

#### Logging
- Logger with multiple log levels

#### CLI Commands (40+)
- make:model, make:controller, make:middleware, make:migration
- make:seeder, make:factory, make:service, make:provider
- make:page, make:component, make:layout, make:request
- make:job, make:event, make:listener
- remove:model, remove:controller, remove:middleware, remove:migration
- remove:seeder, remove:factory, remove:service, remove:provider
- remove:page, remove:component, remove:layout, remove:request
- rename:model, rename:controller, rename:service
- migrate, migrate:rollback, migrate:fresh
- db:seed, cache:clear, route:list, serve
- lint, test, class:resolve

#### Layout Types
- app (authenticated with sidebar)
- guest (public landing)
- blank (minimal)
- auth (login/register)
- dashboard (admin)
- custom (user-defined)

### Documentation
- README.md - Quick start and features
- GUIDE.md - Comprehensive tutorial
- COMPARE.md - Comparison with Laravel/Livewire
- SKILLS.md - CLI and API reference
- AGENTS.md - AI agent instructions
- CHANGELOG.md - This file
- CONTRIBUTING.md - Contribution guidelines

### Infrastructure
- Public entry point (public/index.php)
- Environment configuration (.env)
- Default layouts
- Database SQLite setup
- Test framework

## [0.1.0] - 2026-04-06

### Added
- Initial project structure
- Basic routing
- Simple model and controller stubs

---

## Upgrade Notes

### v1.0.0
- Requires PHP 8.4+
- New CLI commands available
- Strict routing now enforced (separate route files)
- All imports must use top-level `use` statements

### Future Versions
- Stay tuned for more features and improvements!