# GitHub Copilot Instructions for Zen Framework

## About This Project

Zen Framework is a clean, modern, backend-first PHP framework requiring PHP 8.5+.

**Key Features:**
- Strict routing architecture (separate files for Web, Api, Auth, Ai)
- Built-in UI system (Components, Pages, Layouts)
- ORM system with QueryBuilder
- CLI tools with 40+ commands
- Enterprise-grade security
- Native SSE support for real-time streaming

## Coding Standards

### PHP Version & Style
- PHP 8.5+ with `declare(strict_types=1);`
- PSR-12 coding standards
- Use modern PHP features: property hooks, asymmetric visibility, attributes

### Import System (CRITICAL)
**ALWAYS use top-level imports, NEVER inline namespace prefixes:**

```php
// ❌ WRONG - Do NOT do this
$result = \Zen\Http\Response::json($data);

// ✅ CORRECT - Do this instead
use Zen\Http\Response;

$result = Response::json($data);
```

### Helper Functions
Use built-in helpers when available:
- `app()` - Get container instance
- `config('key')` - Get configuration value
- `view('name', $data)` - Render view
- `response($content, $status)` - HTTP response
- `redirect('/path')` - Redirect response
- `json($data, $status)` - JSON response
- `session()->get('key')` - Session access
- `auth()->user()` - Get authenticated user

### File Organization
- **Controllers:** `app/Http/Controllers/`
- **Models:** `app/Models/`
- **Middleware:** `app/Http/Middleware/`
- **Requests:** `app/Http/Requests/`
- **Services:** `app/Services/`
- **Providers:** `app/Providers/`
- **UI Components:** `app/UI/Components/`
- **Migrations:** `database/migrations/`
- **Seeders:** `database/seeders/`

### Routing
Routes are organized by domain:
- `routes/Web.php` - Public web routes (prefix: `/`)
- `routes/Api.php` - API routes (prefix: `/api`)
- `routes/Auth.php` - Authentication routes (prefix: `/auth`)
- `routes/Ai.php` - AI routes (prefix: `/ai`)

## Code Completion Guidelines

### When Suggesting Controllers
```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
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
}
```

### When Suggesting Models
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
}
```

### When Suggesting Services
```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

class UserService
{
    public function create(array $data): User
    {
        // Business logic here
        return User::create($data);
    }
}
```

## Security Rules for Suggestions

- NEVER suggest hardcoding secrets or API keys
- ALWAYS suggest validation for user input
- NEVER suggest raw SQL queries (use ORM)
- ALWAYS suggest using middleware for authentication
- NEVER suggest logging sensitive data

## Common CLI Commands to Suggest

```bash
# Create files
php zen make:model User
php zen make:controller UserController
php zen make:migration create_users_table
php zen make:service UserService
php zen make:request StoreUser

# Database
php zen migrate
php zen migrate --rollback
php zen db:seed

# Testing & Quality
php zen lint
php zen test
php zen route:list
```

## Verification Steps

After suggesting code changes, always remind users to:
1. Run `php zen lint` to check syntax
2. Run `php zen test` to run tests
3. Verify routes with `php zen route:list` (if routes changed)

## Additional Resources

- `AGENTS.md` - Detailed AI agent instructions
- `AI_AGENT_GUIDE.md` - Comprehensive AI guide for all IDEs
- `SYNTAX.md` - Complete PHP 8.5+ syntax guide
- `CLI_COMMANDS.md` - All CLI commands reference
- `README.md` - Project overview
