# Zenith Framework - AI Agent Guide

> **Comprehensive Guide for AI IDEs** - Cursor, Claude, Qwen, Codex, GitHub Copilot, Windsurf, Aider, Roo Code, and more

This guide provides unified instructions, rules, plans, and skills for AI agents working with the Zenith Framework codebase across all major AI-powered IDEs.

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [AI Agent Rules](#ai-agent-rules)
3. [AI Agent Skills](#ai-agent-skills)
4. [AI Agent Tools Reference (64+ Tools)](#ai-agent-tools-reference-64-tools)
5. [Development Plans](#development-plans)
6. [IDE-Specific Configurations](#ide-specific-configurations)
7. [Common Workflows](#common-workflows)
8. [Code Standards](#code-standards)
9. [Testing & Quality](#testing--quality)
10. [Advanced Tool Usage](#advanced-tool-usage)

---

## Project Overview

**Zenith Framework** is a clean, modern, backend-first PHP framework with:

- **PHP Version:** 8.5+
- **Architecture:** Clean, backend-first, API-driven
- **Routing:** Strict routing with separate files (Web, Api, Auth, Ai)
- **UI System:** Built-in Components, Pages, Layouts
- **Database:** ORM with QueryBuilder, 10 relationship types
- **CLI Tools:** 40+ commands for development
- **Security:** Enterprise-grade (WAF, DDoS protection, rate limiting)
- **Real-time:** Native SSE support, WebSocket, connection pooling
- **AI Integration:** OpenAI, Anthropic, Ollama support
- **Performance:** Multi-level caching, connection pooling

### Key Strengths

✅ Zero magic - predictable architecture  
✅ Strict typing with PHP 8.5 features  
✅ Property hooks and asymmetric visibility  
✅ HTMX-style server-side rendering  
✅ Native SSE for real-time streaming  
✅ Enterprise security suite  

---

## AI Agent Rules

### 🚨 CRITICAL RULES (MUST FOLLOW)

#### 1. Import System (STRICT)

**NEVER use inline namespace prefixes. Always use top-level imports.**

```php
// ❌ WRONG - Inline namespace usage
\Zen\Diagnostics\Observability::metrics();
$result = \Zen\Database\Query::table('users')->get();

// ✅ CORRECT - Use top imports
use Zen\Diagnostics\Observability;
use Zen\Database\Query;

Observability::metrics();
$result = Query::table('users')->get();
```

#### 2. File Creation Protocol

When adding new features:

1. ✅ Follow existing file patterns and conventions
2. ✅ Use proper namespaces consistently
3. ✅ Add to autoloader in `boot/Autoloader.php` if new namespace
4. ✅ Add CLI command to `zen` file if new command
5. ✅ Run `php zen lint` to verify syntax
6. ✅ Run `php zen test` to verify functionality

#### 3. Code Quality Gates

**NEVER commit without verification:**

```bash
# Always run these after changes
php zen lint          # Syntax and style check
php zen test          # Run test suite
php zen route:list    # Verify routes (if routes changed)
```

#### 4. Database Rules

- Default driver: **SQLite**
- Migrations location: `database/migrations/`
- Seeders location: `database/seeders/`
- Factories location: `database/factories/`
- **ALWAYS** test migrations with rollback

#### 5. Security First

**NEVER:**
- Hardcode secrets, API keys, or passwords
- Log sensitive information
- Disable security features without explicit request
- Use eval() or exec() without sandboxing
- Expose stack traces in production

---

## AI Agent Skills

### Skill 1: Code Generation

**Capabilities:**
- Generate controllers, models, middleware, services
- Create migrations with proper schema
- Write validation rules and form requests
- Build UI components and pages
- Implement API endpoints

**Process:**
1. Understand requirement
2. Check existing patterns (`glob`, `grep_search`)
3. Generate code following conventions
4. Add proper imports
5. Update autoloader if needed
6. Verify with lint and tests

### Skill 2: Code Refactoring

**Capabilities:**
- Extract methods and classes
- Rename variables and functions
- Inline unnecessary abstractions
- Convert to PHP 8.5 features (property hooks, attributes)
- Optimize database queries

**Process:**
1. Identify refactoring target
2. Understand dependencies (`grep_search`, `agent`)
3. Apply refactoring incrementally
4. Run `php zen lint` after each change
5. Run `php zen test` to verify

### Skill 3: Debugging

**Capabilities:**
- Analyze error messages and stack traces
- Trace request lifecycle
- Identify routing issues
- Fix validation errors
- Resolve dependency injection problems

**Process:**
1. Get error details
2. Search relevant code (`grep_search`, `read_file`)
3. Identify root cause
4. Apply fix
5. Verify with tests

### Skill 4: Architecture Design

**Capabilities:**
- Design clean module structure
- Plan database schema
- Design API endpoints
- Implement design patterns
- Create service layer architecture

**Process:**
1. Understand requirements
2. Research existing architecture (`agent` with Explore)
3. Propose design with diagram/structure
4. Get approval
5. Implement incrementally

### Skill 5: Testing

**Capabilities:**
- Write unit tests for services
- Write integration tests for APIs
- Create feature tests for controllers
- Mock dependencies and external services
- Generate test data with factories

**Process:**
1. Identify code to test
2. Check existing test patterns
3. Write tests following conventions
4. Run `php zen test` to verify
5. Fix failures

---

## AI Agent Tools Reference (64+ Tools)

This section provides a comprehensive reference for all tools available to AI agents working with Zenith Framework. Tools are organized by category for easy reference.

### Category 1: Code Analysis Tools (8 tools)

#### 1. `grep_search` - Content Search
Search file contents using regex patterns.

```json
{
  "pattern": "class.*Controller",
  "glob": "*.php",
  "path": "app/Http/Controllers"
}
```

**Use cases:**
- Find class definitions
- Search for function usage
- Locate specific patterns
- Find deprecated code

#### 2. `glob` - File Pattern Search
Find files matching glob patterns.

```json
{
  "pattern": "**/*Controller.php",
  "path": "app/Http"
}
```

**Use cases:**
- Find all controllers
- Locate test files
- Find migration files
- Search by extension

#### 3. `read_file` - File Reader
Read file contents with optional pagination.

```json
{
  "file_path": "/absolute/path/to/file.php",
  "offset": 0,
  "limit": 50
}
```

**Use cases:**
- Read entire files
- Read specific line ranges
- Check configuration
- Review code

#### 4. `list_directory` - Directory Listing
List files and directories.

```json
{
  "path": "/absolute/path/to/directory",
  "ignore": ["node_modules", "vendor"]
}
```

**Use cases:**
- Explore project structure
- Find files in directory
- Check directory contents

#### 5. `agent` (Explore) - Codebase Explorer
Fast agent for exploring codebases.

```json
{
  "description": "Explore routing architecture",
  "subagent_type": "Explore",
  "prompt": "Find all route definitions in routes/ directory"
}
```

**Use cases:**
- Understand codebase structure
- Find patterns across files
- Answer architectural questions
- Explore dependencies

#### 6. `agent` (General-Purpose) - Complex Task Handler
General-purpose agent for multi-step tasks.

```json
{
  "description": "Refactor authentication system",
  "subagent_type": "general-purpose",
  "prompt": "Update auth to use JWT tokens instead of sessions"
}
```

**Use cases:**
- Complex refactoring
- Multi-file changes
- Research and implement
- Documentation updates

#### 7. `web_search` - Web Search
Search the web for up-to-date information.

```json
{
  "query": "PHP 8.5 property hooks best practices"
}
```

**Use cases:**
- Find documentation
- Check latest practices
- Research solutions
- Verify compatibility

#### 8. `web_fetch` - URL Fetcher
Fetch and process web content.

```json
{
  "url": "https://www.php.net/manual/en/language.oop5.properties.php",
  "prompt": "Extract information about property hooks"
}
```

**Use cases:**
- Fetch API documentation
- Read tutorials
- Check specifications
- Gather requirements

---

### Category 2: File Manipulation Tools (6 tools)

#### 9. `write_file` - Create/Write Files
Write content to a file.

```json
{
  "file_path": "/absolute/path/to/file.php",
  "content": "<?php\n\ndeclare(strict_types=1);\n\n// ... code ..."
}
```

**Use cases:**
- Create new files
- Overwrite files
- Generate code
- Write documentation

#### 10. `edit` - Edit Files
Replace text in files with context.

```json
{
  "file_path": "/absolute/path/to/file.php",
  "old_string": "// old code with 3 lines context",
  "new_string": "// new code with 3 lines context"
}
```

**Use cases:**
- Update code
- Fix bugs
- Refactor methods
- Update imports

#### 11. `edit` (replace_all) - Bulk Edit
Replace all occurrences of text.

```json
{
  "file_path": "/absolute/path/to/file.php",
  "old_string": "old_function",
  "new_string": "new_function",
  "replace_all": true
}
```

**Use cases:**
- Rename across file
- Update all instances
- Batch replacements

#### 12. `save_memory` (global) - Save User Preference
Save user-level information.

```json
{
  "fact": "User prefers PHP 8.5 features",
  "scope": "global"
}
```

**Use cases:**
- Remember preferences
- Store coding style
- Save common patterns

#### 13. `save_memory` (project) - Save Project Info
Save project-specific information.

```json
{
  "fact": "Database uses SQLite",
  "scope": "project"
}
```

**Use cases:**
- Document decisions
- Store project facts
- Remember conventions

#### 14. `todo_write` - Task Management
Create and manage task lists.

```json
{
  "todos": [
    {
      "id": "1",
      "content": "Create model",
      "status": "completed"
    },
    {
      "id": "2",
      "content": "Add migration",
      "status": "in_progress"
    }
  ]
}
```

**Use cases:**
- Track progress
- Plan features
- Organize work
- Report status

---

### Category 3: Shell & Execution Tools (4 tools)

#### 15. `run_shell_command` - Execute Commands
Run shell commands with optional background mode.

```json
{
  "command": "php zen lint",
  "description": "Check syntax and style",
  "is_background": false
}
```

**Use cases:**
- Run CLI commands
- Start servers
- Execute scripts
- Check status

#### 16. `run_shell_command` (background) - Background Execution
Run long-running processes in background.

```json
{
  "command": "php zen serve",
  "description": "Start development server",
  "is_background": true
}
```

**Use cases:**
- Start dev server
- Run watchers
- Start queue workers
- Run schedulers

#### 17. `run_shell_command` (with timeout) - Timed Execution
Run commands with timeout.

```json
{
  "command": "php zen test",
  "description": "Run test suite",
  "timeout": 60000
}
```

**Use cases:**
- Long-running tests
- Migrations
- Data processing

#### 18. `run_shell_command` (with directory) - Directory-Specific
Run commands in specific directory.

```json
{
  "command": "composer install",
  "description": "Install dependencies",
  "directory": "/path/to/project"
}
```

**Use cases:**
- Install packages
- Run in subdirectories
- Workspace commands

---

### Category 4: Zen CLI Commands (20+ tools)

#### 19. `php zen serve` - Development Server
Start the built-in development server.

```bash
php zen serve
php zen serve --port=8080
php zen serve --host=0.0.0.0
```

#### 20. `php zen make:model` - Create Model
Generate a new model class.

```bash
php zen make:model User
php zen make:model User --migration
php zen make:model User --fillable=name,email
```

#### 21. `php zen make:controller` - Create Controller
Generate a controller class.

```bash
php zen make:controller UserController
php zen make:controller UserController --api
php zen make:controller UserController --resource
```

#### 22. `php zen make:migration` - Create Migration
Generate a database migration.

```bash
php zen make:migration create_users_table
php zen make:migration add_email_to_users_table
```

#### 23. `php zen make:seeder` - Create Seeder
Generate a database seeder.

```bash
php zen make:seeder UserSeeder
php zen make:seeder DatabaseSeeder
```

#### 24. `php zen make:factory` - Create Factory
Generate a model factory.

```bash
php zen make:factory UserFactory
php zen make:factory PostFactory
```

#### 25. `php zen make:service` - Create Service
Generate a service class.

```bash
php zen make:service UserService
php zen make:service PaymentService
```

#### 26. `php zen make:middleware` - Create Middleware
Generate HTTP middleware.

```bash
php zen make:middleware Auth
php zen make:middleware Cors
```

#### 27. `php zen make:request` - Create Form Request
Generate a form request class.

```bash
php zen make:request StoreUser
php zen make:request UpdatePost
```

#### 28. `php zen make:page` - Create Page
Generate a UI page.

```bash
php zen make:page Dashboard
php zen make:page UserProfile
```

#### 29. `php zen make:component` - Create Component
Generate a UI component.

```bash
php zen make:component UserCard
php zen make:component Navbar
```

#### 30. `php zen make:layout` - Create Layout
Generate a layout file.

```bash
php zen make:layout admin --type=app
php zen make:layout dashboard
```

#### 31. `php zen make:provider` - Create Provider
Generate a service provider.

```bash
php zen make:provider AppServiceProvider
```

#### 32. `php zen make:job` - Create Job
Generate a queue job class.

```bash
php zen make:job ProcessOrder
php zen make:job SendEmail
```

#### 33. `php zen make:event` - Create Event
Generate an event class.

```bash
php zen make:event UserRegistered
```

#### 34. `php zen make:listener` - Create Listener
Generate an event listener.

```bash
php zen make:listener SendWelcomeEmail
```

#### 35. `php zen migrate` - Run Migrations
Execute pending migrations.

```bash
php zen migrate
php zen migrate --seed
php zen migrate --force
```

#### 36. `php zen migrate --rollback` - Rollback Migration
Rollback the last migration batch.

```bash
php zen migrate --rollback
php zen migrate --rollback --step=3
```

#### 37. `php zen migrate --reset` - Reset All Migrations
Reset all migrations.

```bash
php zen migrate --reset
php zen migrate --reset --seed
```

#### 38. `php zen db:seed` - Run Seeders
Execute database seeders.

```bash
php zen db:seed
php zen db:seed UserSeeder
```

#### 39. `php zen lint` - Code Linting
Check code syntax and style.

```bash
php zen lint
php zen lint --no-imports
```

#### 40. `php zen test` - Run Tests
Execute the test suite.

```bash
php zen test
php zen test --filter=UserTest
```

#### 41. `php zen route:list` - List Routes
Display all registered routes.

```bash
php zen route:list
php zen route:list --json
php zen route:list --method=GET
```

#### 42. `php zen cache:clear` - Clear Cache
Clear application cache.

```bash
php zen cache:clear
php zen cache:clear --all
```

#### 43. `php zen class:resolve` - Check Autoloading
Verify class resolution.

```bash
php zen class:resolve
php zen class:resolve --fix
```

---

### Category 5: Built-in Qwen Skills (3 tools)

#### 44. `skill: review` - Code Review
Review code for quality and security.

```
/review
/review <pr-number>
/review <file-path>
/review <pr-number> --comment
```

**Use cases:**
- Review pull requests
- Check code quality
- Find security issues
- Performance review

#### 45. `skill: loop` - Recurring Tasks
Create scheduled recurring tasks.

```
/loop 5m check the build
/loop 30m check the PR
/loop list
/loop clear
```

**Use cases:**
- Monitor builds
- Check PR status
- Run periodic tests
- Watch logs

#### 46. `skill: qc-helper` - Configuration Help
Get help with Qwen Code configuration.

```
/qc-helper how do I configure MCP servers?
/qc-helper change approval mode to yolo
```

**Use cases:**
- View settings
- Modify configuration
- Troubleshoot
- Get help

---

### Category 6: Communication & Interaction Tools (3 tools)

#### 47. `ask_user_question` (single) - Single Choice
Ask user a single-choice question.

```json
{
  "questions": [
    {
      "question": "Which database driver should we use?",
      "header": "Database",
      "multiSelect": false,
      "options": [
        {"label": "SQLite", "description": "Local file database"},
        {"label": "MySQL", "description": "Traditional SQL database"},
        {"label": "PostgreSQL", "description": "Advanced SQL database"}
      ]
    }
  ]
}
```

#### 48. `ask_user_question` (multi) - Multi Choice
Ask user a multi-choice question.

```json
{
  "questions": [
    {
      "question": "Which features do you want to enable?",
      "header": "Features",
      "multiSelect": true,
      "options": [
        {"label": "Cache", "description": "Enable caching"},
        {"label": "Queue", "description": "Enable queue system"},
        {"label": "Mail", "description": "Enable mail system"}
      ]
    }
  ]
}
```

#### 49. `ask_user_question` (with metadata) - Tracked Questions
Ask questions with analytics tracking.

```json
{
  "questions": [
    {
      "question": "What's your preferred auth method?",
      "header": "Auth",
      "multiSelect": false,
      "metadata": {"source": "remember"},
      "options": [
        {"label": "Session", "description": "Cookie-based session auth"},
        {"label": "JWT", "description": "Token-based JWT auth"},
        {"label": "API Key", "description": "API key authentication"}
      ]
    }
  ]
}
```

---

### Category 7: Advanced AI Operations (8 tools)

#### 50. `agent` (Explore - quick) - Quick Exploration
Fast codebase exploration.

```json
{
  "description": "Quick structure scan",
  "subagent_type": "Explore",
  "prompt": "Show me the main directory structure"
}
```

**Thoroughness:** `quick`

#### 51. `agent` (Explore - medium) - Moderate Exploration
Moderate-depth codebase analysis.

```json
{
  "description": "Find auth flow",
  "subagent_type": "Explore",
  "prompt": "Trace the authentication flow from routes to models"
}
```

**Thoroughness:** `medium`

#### 52. `agent` (Explore - very thorough) - Deep Analysis
Comprehensive codebase analysis.

```json
{
  "description": "Full architecture review",
  "subagent_type": "Explore",
  "prompt": "Analyze the entire MVC architecture and document patterns"
}
```

**Thoroughness:** `very thorough`

#### 53. Parallel Agents - Multi-Agent Execution
Launch multiple agents simultaneously.

```json
{
  "agents": [
    {
      "description": "Find controllers",
      "subagent_type": "Explore",
      "prompt": "List all controllers"
    },
    {
      "description": "Find models",
      "subagent_type": "Explore",
      "prompt": "List all models"
    }
  ]
}
```

**Use cases:**
- Parallel searches
- Independent tasks
- Faster execution

#### 54. `agent` (Research) - Deep Research
Research complex questions autonomously.

```json
{
  "description": "Research PHP 8.5 features",
  "subagent_type": "general-purpose",
  "prompt": "Research and document all PHP 8.5 features and how to use them"
}
```

#### 55. `agent` (Implement) - Feature Implementation
Autonomously implement features.

```json
{
  "description": "Implement user registration",
  "subagent_type": "general-purpose",
  "prompt": "Implement complete user registration with validation, email verification, and password hashing"
}
```

#### 56. `agent` (Test) - Test Execution
Run and analyze tests.

```json
{
  "description": "Run test suite",
  "subagent_type": "general-purpose",
  "prompt": "Run all tests, analyze failures, and fix failing tests"
}
```

#### 57. `agent` (Document) - Documentation Generation
Generate documentation.

```json
{
  "description": "Document API endpoints",
  "subagent_type": "general-purpose",
  "prompt": "Generate comprehensive API documentation for all routes in Api.php"
}
```

---

### Category 8: Configuration & System Tools (6 tools)

#### 58. Environment Variables - `.env` Management
Read and modify environment variables.

```bash
# Check current env
cat .env

# Modify .env (use edit tool)
# Update APP_DEBUG=true to APP_DEBUG=false
```

**Use cases:**
- Check configuration
- Update secrets
- Toggle features
- Set API keys

#### 59. Configuration Files - `config/*.php`
Read and modify application configuration.

```php
// Read config/config/app.php
// Update app name, debug mode, etc.
```

**Use cases:**
- Update app settings
- Configure database
- Set cache driver
- Configure mail

#### 60. Autoloader - `boot/Autoloader.php`
Update class autoloader.

```php
// Add new namespace
'App\\Jobs\\' => 'app/Jobs/',
'App\\Events\\' => 'app/Events/',
```

**Use cases:**
- Register new namespaces
- Fix class loading
- Update paths

#### 61. Route Files - `routes/*.php`
Modify route definitions.

```php
// routes/Web.php
Route::get('/users', [UserController::class, 'index']);
```

**Use cases:**
- Add routes
- Add middleware
- Add prefixes
- Group routes

#### 62. Service Providers - `app/Providers/`
Register services and bindings.

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->container->singleton(UserService::class);
}
```

**Use cases:**
- Register singletons
- Bind interfaces
- Register commands
- Boot services

#### 63. Migration Files - `database/migrations/`
Create and modify database migrations.

```php
public function up(): void
{
    $this->schema->create('users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamps();
    });
}
```

**Use cases:**
- Create tables
- Add columns
- Add indexes
- Modify schema

---

### Category 9: Debugging & Diagnostics Tools (8 tools)

#### 64. `dd()` - Dump and Die
Debug variable dumping.

```php
dd($variable);
dd($user->toArray());
```

**Use cases:**
- Inspect variables
- Debug values
- Check data structure

#### 65. Error Logs - `storage/logs/`
Read application logs.

```bash
# Check latest log
cat storage/logs/app-2026-04-09.log
```

**Use cases:**
- Debug errors
- Trace issues
- Monitor warnings

#### 66. Route Debugging - `php zen route:list`
Debug routing issues.

```bash
php zen route:list --json | jq '.[] | select(.path | contains("user"))'
```

**Use cases:**
- Find route conflicts
- Check prefixes
- Verify middleware

#### 67. Database Debugging - Query Builder Debug
Debug database queries.

```php
// In controller or tinker
Query::table('users')->toSql();
```

**Use cases:**
- Check generated SQL
- Debug query parameters
- Optimize queries

#### 68. Session Debugging - Session Inspection
Debug session data.

```php
session()->all();
session()->get('key');
```

**Use cases:**
- Check session data
- Debug auth issues
- Verify flash data

#### 69. Cache Debugging - Cache Inspection
Debug cache entries.

```bash
php zen cache:clear
```

```php
cache()->get('key');
cache()->has('key');
```

**Use cases:**
- Check cached values
- Debug cache misses
- Verify expiration

#### 70. Container Debugging - Dependency Injection
Debug container bindings.

```php
app()->bound(UserService::class);
app()->make(UserService::class);
```

**Use cases:**
- Check registered bindings
- Debug DI issues
- Resolve instances

#### 71. View Debugging - Template Inspection
Debug rendered views.

```php
view('users.index', compact('users'))->render();
```

**Use cases:**
- Check view output
- Debug variables
- Verify templates

---

### Category 10: Security & Performance Tools (6 tools)

#### 72. WAF Rules - Web Application Firewall
Configure WAF rules.

```php
// config/security.php
'waf' => [
    'enabled' => true,
    'rules' => ['xss', 'sqli', 'rce']
]
```

**Use cases:**
- Enable/disable WAF
- Add custom rules
- Configure protections

#### 73. Rate Limiting - Request Throttling
Configure rate limiting.

```php
Route::middleware(['throttle:60,1'])->group(function() {
    Route::get('/api/users', [UserController::class, 'index']);
});
```

**Use cases:**
- Prevent abuse
- Protect endpoints
- Manage quotas

#### 74. CSRF Protection - Token Validation
Configure CSRF protection.

```php
// Middleware applied to POST routes
Route::middleware(['csrf'])->group(function() {
    // Protected routes
});
```

**Use cases:**
- Enable CSRF
- Exclude routes
- Debug token issues

#### 75. Encryption - Data Encryption
Encrypt/decrypt data.

```php
use Zen\Support\Crypt;

$encrypted = Crypt::encrypt('secret');
$decrypted = Crypt::decrypt($encrypted);
```

**Use cases:**
- Encrypt sensitive data
- Secure tokens
- Protect secrets

#### 76. Two-Factor Auth - TOTP Setup
Configure 2FA.

```php
use Zen\Auth\TwoFactor;

$secret = TwoFactor::generateSecret();
$qrCode = TwoFactor::getQRCodeUrl($user->email, $secret);
```

**Use cases:**
- Enable 2FA
- Verify codes
- Generate QR codes

#### 77. Circuit Breaker - Fault Tolerance
Configure circuit breaker pattern.

```php
use Zen\Resilience\CircuitBreaker;

$breaker = new CircuitBreaker([
    'failureThreshold' => 5,
    'resetTimeout' => 60
]);
```

**Use cases:**
- Protect external calls
- Prevent cascade failures
- Manage timeouts

---

## Development Plans

### Plan 1: Feature Development

**Use when:** Adding new feature (model, controller, API endpoint)

```
1. Analyze existing patterns (agent: Explore)
2. Create model (php zen make:model)
3. Create migration (php zen make:migration)
4. Create controller (php zen make:controller)
5. Add routes (edit routes/Web.php or Api.php)
6. Create validation (php zen make:request)
7. Create service layer (php zen make:service)
8. Write tests
9. Run php zen lint && php zen test
10. Document changes
```

### Plan 2: Bug Fix

**Use when:** Fixing reported bug

```
1. Reproduce issue (if possible)
2. Identify error location (read_file, grep_search)
3. Trace call stack
4. Identify root cause
5. Write failing test (if applicable)
6. Implement fix
7. Run php zen lint && php zen test
8. Verify issue resolved
9. Document fix
```

### Plan 3: Refactoring

**Use when:** Improving code quality

```
1. Identify refactoring target
2. Understand current implementation
3. Plan new structure
4. Get approval if major changes
5. Apply changes incrementally
6. Run php zen lint after each change
7. Run php zen test after each change
8. Update documentation
```

### Plan 4: Database Migration

**Use when:** Adding/modifying database schema

```
1. Analyze current schema
2. Design new schema
3. Create migration (php zen make:migration)
4. Write migration with up/down methods
5. Test migration: php zen migrate
6. Test rollback: php zen migrate --rollback
7. Create/update seeders
8. Create/update factories
9. Update models
```

### Plan 5: API Development

**Use when:** Building REST/JSON APIs

```
1. Design API structure
2. Create/update routes/Api.php
3. Create controller (php zen make:controller)
4. Create resource transformers
5. Add validation (php zen make:request)
6. Create API tests
7. Run php zen lint && php zen test
8. Document API endpoints
```

---

## IDE-Specific Configurations

### Cursor IDE

**File:** `.cursorrules`

**Features:**
- Code generation with context
- Chat-based assistance
- Multi-file edits
- Terminal integration

**Configuration:**
```
Rules for Cursor:
- Use @filename to reference files
- Use Cmd/Ctrl+K for inline edits
- Use Cmd/Ctrl+L for chat
- Run php zen lint after edits
- Follow AGENTS.md guidelines
```

### Claude (Anthropic)

**File:** `.clinerules`

**Features:**
- Code analysis
- Refactoring
- Documentation
- Security review

**Configuration:**
```
Rules for Claude:
- Read full files before changes
- Use proper PHP imports
- Follow Zenith Framework conventions
- Always verify with php zen lint
- Reference AGENTS.md for patterns
```

### Qwen Code (Alibaba)

**File:** `.qwenrules`

**Features:**
- Autonomous task execution
- Multi-agent coordination
- Built-in skills (review, loop, qc-helper)
- Project context awareness

**Configuration:**
```
Rules for Qwen Code:
- Use /review to check code quality
- Use /loop for recurring tasks
- Use /qc-helper for configuration help
- Follow AGENTS.md strictly
- Run php zen test after changes
- Always use top-level imports
```

### GitHub Copilot

**File:** `.github/copilot-instructions.md`

**Features:**
- Inline code completions
- Chat assistance
- Workspace context

**Configuration:**
```
Instructions for GitHub Copilot:
- Suggest PHP 8.5+ code
- Use strict typing
- Follow PSR-12
- Add proper imports
- Reference existing patterns
```

### Windsurf / Codex

**File:** `.windsurfrules`

**Features:**
- Multi-file editing
- Terminal commands
- Code understanding

**Configuration:**
```
Rules for Windsurf/Codex:
- Read before writing
- Follow existing patterns
- Use php zen commands
- Respect import system
- Verify with lint and tests
```

### Aider

**File:** `.aider.conf.yml`

**Features:**
- Git-aware coding
- Pair programming
- Auto-commits

**Configuration:**
```yaml
# Aider configuration for Zenith Framework
architect: true
auto-commits: true
watch-files: true
dry-run: false
analytics-disable: true
map-tokens: 1024
cache-prompts: true
```

### Roo Code

**File:** `.roorules`

**Features:**
- Autonomous mode
- Skill system
- Task planning

**Configuration:**
```
Rules for Roo Code:
- Use mode:architect for planning
- Use mode:code for implementation
- Run php zen lint after changes
- Follow AGENTS.md guidelines
- Always test changes
```

### Other AI IDEs

**General Rules:**
1. Read AGENTS.md first
2. Understand directory structure
3. Use proper PHP imports
4. Follow existing patterns
5. Run verification commands
6. Document changes

---

## Common Workflows

### 1. Add New Model with Migration

```bash
# Create model and migration
php zen make:model User --migration

# Edit migration file
# database/migrations/XXXX_create_users_table.php

# Run migration
php zen migrate

# Create seeder
php zen make:seeder UserSeeder

# Create factory
php zen make:factory UserFactory

# Seed database
php zen db:seed
```

### 2. Add New API Endpoint

```bash
# Create controller
php zen make:controller Api/UserController

# Add route in routes/Api.php
Route::get('/users', [UserController::class, 'index']);

# Create request validation
php zen make:request StoreUser

# Add to controller
public function store(StoreUser $request): Response
{
    $user = User::create($request->validated());
    return json($user, 201);
}
```

### 3. Add Middleware

```bash
# Create middleware
php zen make:middleware Auth

# Apply globally in boot/Engine.php
# OR apply to specific routes
Route::middleware(['auth'])->group(function() {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
```

### 4. Create UI Component

```bash
# Create component
php zen make:component UserCard

# Edit view
# views/components/UserCard.php

# Use in page
<zen:UserCard :user="$user" />
```

### 5. Add Service Layer

```bash
# Create service
php zen make:service UserService

# Implement business logic
class UserService
{
    public function create(array $data): User
    {
        // Business logic here
        return User::create($data);
    }
}

# Use in controller
public function __construct(protected UserService $users) {}
```

---

## Code Standards

### PHP 8.5 Features

**Strict Typing:**
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Zen\Database\Model;

class User extends Model
{
    // ...
}
```

**Property Hooks:**
```php
class User extends Model
{
    protected string $name {
        get => $this->attributes['name'];
        set {
            if (empty($value)) {
                throw new \InvalidArgumentException('Name cannot be empty');
            }
            $this->attributes['name'] = $value;
        }
    }
}
```

**Asymmetric Visibility:**
```php
class User extends Model
{
    public private(set) string $email;
    protected(set) public string $name;
}
```

**Attributes:**
```php
#[Prefix('/users')]
#[Middleware(['auth'])]
class UserController
{
    #[Get('/', name: 'users.index')]
    public function index(): Response
    {
        return view('users.index', ['users' => User::all()]);
    }
}
```

### Naming Conventions

| Type | Convention | Example |
|------|------------|---------|
| Classes | PascalCase | `UserController`, `UserService` |
| Methods | camelCase | `getUser()`, `createUser()` |
| Variables | camelCase | `$userName`, `$isActive` |
| Constants | UPPER_SNAKE_CASE | `MAX_RETRIES`, `API_VERSION` |
| Database Tables | snake_case (plural) | `users`, `blog_posts` |
| Database Columns | snake_case | `first_name`, `created_at` |
| Routes | kebab-case | `/user-profile`, `/blog-posts` |

### Directory Structure

```
zen/
├── app/
│   ├── Http/
│   │   ├── Controllers/    # Request handlers
│   │   ├── Middleware/     # Request filtering
│   │   └── Requests/       # Form validation
│   ├── Models/             # Database models
│   ├── Services/           # Business logic
│   ├── Providers/          # Service providers
│   └── UI/
│       ├── Components/     # Reusable UI
│       ├── Pages/          # Full pages
│       └── Layouts/        # Page layouts
├── boot/                   # Bootstrap files
├── config/                 # Configuration
├── core/                   # Framework core
├── database/
│   ├── migrations/         # Database migrations
│   ├── seeders/           # Database seeders
│   └── factories/         # Model factories
├── routes/                 # Route definitions
│   ├── Web.php            # Public routes
│   ├── Api.php            # API routes
│   ├── Auth.php           # Auth routes
│   └── Ai.php             # AI routes
├── tests/                  # Test files
├── views/                  # View templates
├── zen                     # CLI entry point
└── .env                   # Environment config
```

---

## Testing & Quality

### Running Tests

```bash
# Run all tests
php zen test

# Run lint check
php zen lint

# Run lint without import checks
php zen lint --no-imports

# Check routes
php zen route:list
php zen route:list --json
```

### Writing Tests

**Unit Test:**
```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\UserService;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    public function test_create_user(): void
    {
        $service = new UserService();
        $user = $service->create([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        
        $this->assertEquals('John Doe', $user->name);
    }
}
```

**Feature Test:**
```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class UserApiTest extends TestCase
{
    public function test_get_users(): void
    {
        $response = $this->get('/api/users');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => ['*' => ['id', 'name', 'email']]
                 ]);
    }
}
```

### Code Quality Checklist

Before committing, AI agents MUST:

- [ ] Run `php zen lint` - No errors
- [ ] Run `php zen test` - All tests pass
- [ ] Check imports - No inline namespaces
- [ ] Follow patterns - Consistent with existing code
- [ ] Add tests - New code has test coverage
- [ ] Update docs - Documentation reflects changes
- [ ] Security review - No exposed secrets or vulnerabilities

---

## Quick Reference

### Essential Commands

```bash
# Development
php zen serve                    # Start dev server
php zen serve --port=8080        # Custom port

# Make files
php zen make:model User
php zen make:controller UserController
php zen make:migration create_users_table
php zen make:seeder UserSeeder
php zen make:factory UserFactory
php zen make:service UserService
php zen make:middleware Auth
php zen make:request StoreUser
php zen make:page Dashboard
php zen make:component UserCard
php zen make:layout admin --type=app

# Database
php zen migrate
php zen migrate --rollback
php zen migrate --reset
php zen db:seed
php zen db:seed UserSeeder

# Code quality
php zen lint
php zen test
php zen route:list
php zen cache:clear
```

### Helper Functions

```php
app()                    // Get container instance
config('database.driver') // Get config value
view('users.index', $data) // Render view
response($content, 200)  // HTTP response
redirect('/users')       // Redirect response
json($data, 200)         // JSON response
session()->get('key')    // Session access
auth()->user()           // Authenticated user
dd($var)                 // Dump and die
abort(404)               // Throw HTTP exception
```

### Common Patterns

**Controller:**
```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\StoreUser;
use App\Services\UserService;
use Zen\Http\Response;

class UserController
{
    public function __construct(
        protected UserService $users
    ) {}

    public function index(): Response
    {
        $users = User::all();
        return view('users.index', compact('users'));
    }

    public function store(StoreUser $request): Response
    {
        $user = $this->users->create($request->validated());
        return redirect()->route('users.show', ['id' => $user->id]);
    }
}
```

**Service:**
```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

class UserService
{
    public function create(array $data): User
    {
        // Business logic
        $user = User::create($data);
        
        // Post-creation logic
        event(new UserCreated($user));
        
        return $user;
    }
}
```

**Model:**
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Zen\Database\Model;

class User extends Model
{
    protected string $table = 'users';
    
    protected array $fillable = ['name', 'email'];
    
    protected array $hidden = ['password'];
    
    // Relationships
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
```

---

## Troubleshooting

### Common Issues

**Issue:** Class not found
```bash
# Solution: Check autoloader
php zen class:resolve
php zen class:resolve --fix
```

**Issue:** Migration fails
```bash
# Solution: Rollback and fix
php zen migrate --rollback
# Fix migration file
php zen migrate
```

**Issue:** Route not found
```bash
# Solution: Check route list
php zen route:list
# Verify routes/Web.php or Api.php
```

**Issue:** Validation fails
```bash
# Solution: Check syntax
php zen lint
# Run tests
php zen test
```

---

## Additional Resources

- **AGENTS.md** - AI agent instructions (this file expands on it)
- **SYNTAX.md** - Complete syntax guide (1000+ lines)
- **CLI_COMMANDS.md** - All 118 CLI commands
- **README.md** - Project overview
- **GUIDE.md** - Full tutorial
- **SECURITY.md** - Security guidelines
- **QUICK_REFERENCE.md** - Quick reference card
- **SKILLS.md** - CLI and API reference

---

## AI Agent Notes

### Best Practices

1. **Read First:** Always read relevant files before making changes
2. **Pattern Match:** Follow existing code structure exactly
3. **Test Everything:** Run lint and tests after every change
4. **Small Steps:** Make incremental changes, not massive rewrites
5. **Document:** Update docs when adding features
6. **Ask Questions:** Use `ask_user_question` when uncertain
7. **Security First:** Never compromise on security
8. **Clean Code:** Write readable, maintainable code

### Things to Avoid

❌ Never use inline namespace prefixes (`\Zen\...`)  
❌ Never skip testing after changes  
❌ Never hardcode secrets or API keys  
❌ Never delete migrations without approval  
❌ Never commit broken code  
❌ Never assume patterns - verify first  
❌ Never use outdated PHP syntax  
❌ Ignore existing TODOs without addressing them  

### When in Doubt

1. Check `AGENTS.md` for rules
2. Search existing patterns (`grep_search`, `glob`)
3. Read related files (`read_file`)
4. Explore codebase (`agent` with Explore)
5. Ask user (`ask_user_question`)

---

## Advanced Tool Usage

### Tool Combination Patterns

#### Pattern 1: Search → Read → Edit
The most common workflow for code modifications.

```
1. grep_search: Find files containing "UserController"
2. read_file: Read the controller file
3. edit: Make targeted changes
4. run_shell_command: Run php zen lint
5. run_shell_command: Run php zen test
```

#### Pattern 2: Explore → Plan → Implement
For complex features requiring architecture understanding.

```
1. agent (Explore): Understand current architecture
2. ask_user_question: Clarify requirements
3. todo_write: Create implementation plan
4. write_file: Create new files
5. edit: Modify existing files
6. run_shell_command: Verify changes
```

#### Pattern 3: Research → Design → Review
For architectural decisions and code reviews.

```
1. web_search: Research best practices
2. agent (general-purpose): Design solution
3. skill: review: Get code review
4. edit: Apply improvements
5. run_shell_command: Verify
```

### Multi-Tool Workflows

### Workflow 1: Complete Feature Development
```bash
# 1. Research existing patterns
grep_search: "class.*Controller" in app/Http/Controllers
read_file: Example controller

# 2. Create files
run_shell_command: php zen make:model Post --migration
run_shell_command: php zen make:controller PostController
run_shell_command: php zen make:request StorePost
run_shell_command: php zen make:service PostService

# 3. Implement
write_file: Migration schema
edit: Controller methods
edit: Add routes to routes/Web.php
edit: Add validation in StoreRequest

# 4. Verify
run_shell_command: php zen migrate
run_shell_command: php zen lint
run_shell_command: php zen test
```

### Workflow 2: Bug Investigation
```bash
# 1. Gather information
read_file: Error log file
grep_search: Error message pattern
read_file: Suspected file

# 2. Trace issue
agent (Explore): "Trace the authentication flow"
grep_search: Function call chain

# 3. Fix issue
edit: Apply fix
run_shell_command: php zen lint
run_shell_command: php zen test

# 4. Verify
run_shell_command: Reproduce original issue (should be fixed)
```

### Workflow 3: Refactoring Large Feature
```bash
# 1. Analysis
agent (Explore - very thorough): "Analyze UserService dependencies"
grep_search: "UserService" across codebase
read_file: UserService and related files

# 2. Planning
todo_write: Create refactoring plan with steps
ask_user_question: Confirm approach if needed

# 3. Incremental refactoring
edit: Extract method 1
run_shell_command: php zen lint
run_shell_command: php zen test

edit: Extract method 2
run_shell_command: php zen lint
run_shell_command: php zen test

# 4. Final verification
run_shell_command: php zen lint
run_shell_command: php zen test
skill: review: Review refactored code
```

### Workflow 4: Database Schema Evolution
```bash
# 1. Analyze current schema
read_file: Existing migrations
grep_search: Schema::create patterns
read_file: Model relationships

# 2. Create migration
run_shell_command: php zen make:migration add_profile_fields
write_file: Migration with up/down methods

# 3. Test migration
run_shell_command: php zen migrate
run_shell_command: Test application
run_shell_command: php zen migrate --rollback
run_shell_command: php zen migrate

# 4. Update related files
edit: Update model fillable array
write_file: Update factory
write_file: Update seeder
run_shell_command: php zen db:seed
```

### Workflow 5: API Development End-to-End
```bash
# 1. Design API
web_search: "REST API best practices 2026"
write_file: API documentation draft

# 2. Create structure
run_shell_command: php zen make:controller Api/PostController --api
run_shell_command: php zen make:request Api/StorePostRequest

# 3. Implement API
edit: Add routes to routes/Api.php
edit: Controller with resource methods
edit: Add API middleware

# 4. Test API
write_file: API feature tests
run_shell_command: php zen test
run_shell_command: php zen route:list --json

# 5. Document
write_file: Update API documentation
skill: review: Review API implementation
```

### Parallel Tool Usage

For maximum efficiency, use parallel tool calls when possible:

```json
// BAD: Sequential (slow)
[
  {"tool": "read_file", "args": {"file_path": "Controller1.php"}},
  {"tool": "read_file", "args": {"file_path": "Controller2.php"}},
  {"tool": "read_file", "args": {"file_path": "Controller3.php"}}
]

// GOOD: Parallel (fast)
[
  {"tool": "read_file", "args": {"file_path": "Controller1.php"}},
  {"tool": "read_file", "args": {"file_path": "Controller2.php"}},
  {"tool": "read_file", "args": {"file_path": "Controller3.php"}}
]
```

### Tool Selection Guide

| Task | Best Tool(s) | Alternative |
|------|--------------|-------------|
| Find files by pattern | `glob` | `agent` (Explore) |
| Search code content | `grep_search` | `agent` (Explore) |
| Read file contents | `read_file` | - |
| Create new file | `write_file` | - |
| Modify existing file | `edit` | - |
| Run commands | `run_shell_command` | - |
| Explore architecture | `agent` (Explore) | Multiple `grep_search` |
| Complex research | `agent` (general-purpose) + `web_search` | `web_fetch` |
| Track tasks | `todo_write` | - |
| Ask user | `ask_user_question` | - |
| Review code | `skill: review` | Manual review |
| Recurring tasks | `skill: loop` | Manual execution |

### Common Tool Mistakes to Avoid

❌ **Mistake:** Using `grep_search` when you need file paths
**Solution:** Use `glob` for file patterns

❌ **Mistake:** Reading entire large files
**Solution:** Use `offset` and `limit` for pagination

❌ **Mistake:** Editing without reading context
**Solution:** Always read 3+ lines of context before/after

❌ **Mistake:** Running commands sequentially when parallel is possible
**Solution:** Batch independent commands

❌ **Mistake:** Not verifying after changes
**Solution:** Always run `php zen lint && php zen test`

❌ **Mistake:** Using inline namespaces in code
**Solution:** Always use top-level `use` statements

❌ **Mistake:** Hardcoding absolute paths
**Solution:** Use project root + relative path

### Tool Power Tips

💡 **Tip:** Use `agent` with different thoroughness levels
- `quick`: Basic searches, simple questions
- `medium`: Moderate analysis, pattern finding
- `very thorough`: Deep architecture review

💡 **Tip:** Combine `grep_search` with `glob` filter
```json
{
  "pattern": "function.*store",
  "glob": "*Controller.php"
}
```

💡 **Tip:** Use parallel agents for independent searches
```json
[
  {"tool": "agent", "args": {"description": "Find all models"}},
  {"tool": "agent", "args": {"description": "Find all controllers"}}
]
```

💡 **Tip:** Use `todo_write` for visibility
- Helps user track your progress
- Keeps you organized
- Shows what's remaining

💡 **Tip:** Use `ask_user_question` early
- Clarify ambiguous requirements
- Confirm major decisions
- Get approval before large changes

💡 **Tip:** Chain shell commands with `&&`
```bash
php zen lint && php zen test && echo "All good!"
```

💡 **Tip:** Use background mode for servers
```json
{
  "command": "php zen serve",
  "is_background": true
}
```

---

**Version:** 2.0.0 (Updated with 77 tools)
**Last Updated:** April 9, 2026
**Maintained By:** Zenith Framework Team
