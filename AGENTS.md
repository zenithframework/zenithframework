# Zenith Framework - AI Agent Instructions

Instructions for AI agents working with Zenith Framework codebase.

## Project Overview

**Zenith Framework** is a clean, modern, backend-first PHP framework with:
- PHP 8.5+ requirement
- Strict routing architecture (separate files for Web, Api, Auth, Ai)
- Built-in UI system (Components, Pages, Layouts)
- ORM system with QueryBuilder
- CLI tools with 118+ commands
- Enterprise-grade security and performance
- **Official Website:** [zenithframework.com](https://zenithframework.com)

## Directory Structure

```
zen/
├── app/
│   ├── Http/Controllers/
│   ├── Http/Middleware/
│   ├── Http/Requests/
│   ├── Models/
│   ├── Pages/
│   ├── Services/
│   ├── Providers/
│   └── UI/Components/
├── boot/                  # Bootstrap (Autoloader, ConfigLoader, Engine, Ignition, RouteLoader, ServiceProvider)
├── config/               # Configuration (app.php, database.php)
├── core/                 # Framework core
│   ├── AI/              # AI integration (OpenAI, Anthropic, Ollama)
│   ├── Action/          # HTMX-style actions
│   ├── Auth/            # Authentication
│   ├── Cache/           # Cache system
│   ├── Console/Commands/# CLI commands
│   ├── Database/         # ORM, QueryBuilder, Migrations
│   ├── Diagnostics/     # Error handling
│   ├── Http/             # Request, Response, Redirect
│   ├── Log/              # Logging
│   ├── Routing/         # Router, Route, RouteGroup
│   ├── Session/          # Session management
│   ├── Support/          # Helpers, Str, Arr
│   ├── UI/               # Page, Component, Layout
│   └── Validation/       # Validator
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── public/               # Web entry point
├── routes/               # Route definitions
│   ├── Web.php           # Public routes (prefix: /)
│   ├── Api.php           # API routes (prefix: /api)
│   ├── Auth.php          # Auth routes (prefix: /auth)
│   └── Ai.php            # AI routes (prefix: /ai)
├── tests/
├── views/
├── zen                   # CLI entry point
└── .env                 # Environment config
```

## Important Rules

### 1. Import System (STRICT)

**Inline namespace usage is FORBIDDEN**

```php
// ❌ WRONG
\Zen\Diagnostics\Observability::metrics();

// ✅ CORRECT - Use top imports
use Zen\Diagnostics\Observability;
```

### 2. Running Commands

All commands use `php zen <command>`:

```bash
php zen make:model User
php zen make:controller UserController
php zen migrate
php zen route:list
php zen test
php zen lint
```

### 3. Creating Files

When adding new features:
1. Follow existing file patterns
2. Use proper namespaces
3. Add to autoloader in `boot/Autoloader.php`
4. Add command to `zen` CLI file if new command

### 4. Testing

```bash
# Run tests
php zen test

# Run lint
php zen lint
php zen lint --no-imports
```

### 5. Database

- Default driver: SQLite
- Migrations in `database/migrations/`
- Seeders in `database/seeders/`
- Factories in `database/factories/`

## Common Tasks

### Adding New CLI Command

1. Create file in `core/Console/Commands/<Name>.php`
2. Extend `Command` base class
3. Implement `handle(Container $container, array $arguments)` method
4. Add to `$commands` array in `zen` file

```php
<?php

namespace Zen\Console\Commands;

use Zen\Container;

class MyCommand extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $this->info("Command executed!");
    }
}
```

### Adding New Model

```bash
php zen make:model User --migration
```

### Adding New Route

Edit appropriate file in `routes/`:
- `routes/Web.php` - Public pages
- `routes/Api.php` - JSON APIs
- `routes/Auth.php` - Authentication
- `routes/Ai.php` - AI endpoints

### Adding Middleware

1. Create in `app/Http/Middleware/<Name>.php`
2. Apply in routes or globally in `boot/Engine.php`

### Adding View

Create in `views/`:
- Layouts: `views/layouts/<name>.php`
- Components: `views/components/<name>.php`
- Pages: `views/pages/<name>.php`

## Code Style

- PHP 8.5 strict typing
- PSR-12 inspired formatting
- Use `declare(strict_types=1);` in all files
- Use property hooks where appropriate
- Use asymmetric visibility for encapsulation
- Use helper functions: `app()`, `config()`, `view()`, `response()`, `redirect()`, `json()`, `session()`, `auth()`, `dd()`, `abort()`

## Key Files Reference

| File | Purpose |
|------|---------|
| `zen` | CLI entry point |
| `boot/Ignition.php` | Application bootstrap |
| `boot/Autoloader.php` | Custom class autoloader |
| `boot/RouteLoader.php` | Loads route files |
| `core/Routing/Router.php` | Main router |
| `core/Container.php` | Dependency injection |
| `core/Http/Request.php` | HTTP request |
| `core/Http/Response.php` | HTTP response |
| `core/Database/QueryBuilder.php` | Database queries |
| `core/Database/Model.php` | Base model |
| `core/Validation/Validator.php` | Validation |
| `core/Support/helpers.php` | Global helpers |
| `routes/Web.php` | Web routes |
| `routes/Api.php` | API routes |

## Testing New Code

```bash
# Check syntax
php zen lint

# Run tests
php zen test

# Test specific migration
php zen migrate
php zen migrate --rollback

# Check routes
php zen route:list
php zen route:list --json
```

## Environment

Configuration in `.env`:
```
APP_NAME=Zenith Framework
APP_ENV=development
APP_DEBUG=true

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

CACHE_DRIVER=file

AI_API_KEY=
```

## Notes for AI Agent

1. **Always use imports** - Never use inline `\Namespace\` prefixes
2. **Follow patterns** - Study existing code before writing new code
3. **Test changes** - Run `php zen lint` and `php zen test` after changes
4. **Update autoloader** - Add new namespace prefixes to `boot/Autoloader.php`
5. **Update CLI** - Add new commands to `zen` file
6. **Keep it clean** - No garbage code, minimal and predictable

## Quick Commands Reference

```bash
# Development
php zen serve                    # Start dev server
php zen serve --port=8080        # Custom port

# Make
php zen make:model <Name>
php zen make:controller <Name>
php zen make:middleware <Name>
php zen make:migration <Name>
php zen make:seeder <Name>
php zen make:factory <Name>
php zen make:service <Name>
php zen make:provider <Name>
php zen make:page <Name>
php zen make:component <Name>
php zen make:layout <Name> --type=app

# Remove
php zen remove:model <Name>
php zen remove:controller <Name>

# Rename
php zen rename:model <Name> --to=<NewName>

# Database
php zen migrate
php zen migrate --rollback
php zen migrate --reset
php zen db:seed
php zen db:seed <Name>

# Cache
php zen cache:clear

# Routes
php zen route:list
php zen route:list --json

# Code Quality
php zen lint
php zen test
php zen class:resolve
php zen class:resolve --fix
```
