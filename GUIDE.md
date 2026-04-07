# Zen Framework Guide

A comprehensive guide to building applications with Zen Framework.

## Table of Contents

1. [Installation](#installation)
2. [Project Structure](#project-structure)
3. [Configuration](#configuration)
4. [Routing](#routing)
5. [Controllers](#controllers)
6. [Models & ORM](#models--orm)
7. [Database & Migrations](#database--migrations)
8. [Views & Templates](#views--templates)
9. [Template Engine](#template-engine)
10. [Session & Auth](#session--auth)
11. [Validation](#validation)
12. [Cache](#cache)
13. [CLI Commands](#cli-commands)
14. [REST API Development](#rest-api-development)
15. [Deployment](#deployment)

---

## Installation

### Option 1: Using zen new (Recommended)

```bash
# Create new project
php zen new my-project

# Navigate to project
cd my-project

# Start development server
php zen serve
```

### Option 2: Manual Clone

```bash
# Clone repository
git clone https://github.com/zenithframework/zenithframework.git my-project
cd my-project

# Install dependencies
# (if using composer)

# Start development server
php zen serve
```

---

## Project Structure

```
zen/
├── app/                    # Application code
│   ├── Http/
│   │   ├── Controllers/    # Controllers
│   │   ├── Middleware/     # Middleware
│   │   └── Requests/       # Form Requests
│   ├── Models/             # Eloquent Models
│   ├── Pages/              # Page classes
│   ├── Services/           # Business logic
│   ├── Providers/         # Service providers
│   └── UI/Components/     # UI Components
├── boot/                   # Bootstrap files
├── config/                 # Configuration
├── core/                   # Framework core
├── database/
│   ├── migrations/        # Database migrations
│   ├── seeders/           # Seeders
│   └── factories/         # Model factories
├── public/                 # Public entry point
├── routes/                  # Route definitions
│   ├── Web.php            # Web routes
│   ├── Api.php            # API routes
│   ├── Auth.php           # Auth routes
│   └── Ai.php             # AI routes
├── views/                  # View templates
│   ├── layouts/           # Layout files
│   ├── components/       # Reusable components
│   └── pages/            # Page templates
└── zen                    # CLI tool
```

---

## Configuration

Create `.env` file:

```env
APP_NAME=Zen Framework
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

CACHE_DRIVER=file
SESSION_DRIVER=file

AI_API_KEY=
```

---

## Routing

### Route Files

Routes are organized in `routes/` directory:

```php
// routes/Web.php
$router->get('/', fn($req) => view('pages.home'));
$router->get('/users', [UserController::class, 'index']);
$router->post('/users', [UserController::class, 'store']);
```

### Route Prefixes

| File  | Prefix |
|-------|---------|
| Web   | /       |
| Api   | /api    |
| Auth  | /auth   |
| Ai    | /ai     |

### Route Parameters

```php
$router->get('/users/{id}', [UserController::class, 'show']);
$router->get('/posts/{slug?}', fn($req, $slug) => $slug ?? 'home');
```

### Named Routes

```php
$router->get('/users', [UserController::class, 'index'], 'users.index');
$router->get('/profile', [UserController::class, 'profile'], 'users.profile');

// Generate URL
route('users.index');
```

---

## Controllers

### Creating Controllers

```bash
php zen make:controller UserController
```

### Controller Methods

```php
<?php

namespace App\Http\Controllers;

use Zen\Http\Request;
use Zen\Http\Response;

class UserController
{
    public function index(Request $request): Response
    {
        $users = User::all();
        return view('pages.users', ['users' => $users]);
    }

    public function show(Request $request, ...$params): Response
    {
        $id = $params[0];
        $user = User::find($id);
        
        if (!$user) {
            return response('User not found', 404);
        }
        
        return view('pages.user', ['user' => $user]);
    }

    public function store(Request $request): Response
    {
        $data = $request->all();
        $user = User::create($data);
        
        return response()->json(['user' => $user], 201);
    }

    public function update(Request $request, ...$params): Response
    {
        $id = $params[0];
        $user = User::findOrFail($id);
        $user->fill($request->all());
        $user->save();
        
        return response()->json(['user' => $user]);
    }

    public function destroy(Request $request, ...$params): Response
    {
        $id = $params[0];
        $user = User::findOrFail($id);
        $user->delete();
        
        return response('', 204);
    }
}
```

---

## Models & ORM

### Creating Models

```bash
php zen make:model User --migration
php zen make:model Post --migration --factory
```

### Model Definition

```php
<?php

namespace App\Models;

use Zen\Database\Model;

class User extends Model
{
    protected static string $table = 'users';
    protected array $fillable = ['name', 'email', 'password'];
    protected array $hidden = ['password'];
    protected array $casts = [
        'id' => 'int',
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = password_hash($value, PASSWORD_DEFAULT);
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->attributes['password'] ?? '');
    }
}
```

### Query Methods

```php
// All users
User::all();

// Find by ID
User::find(1);
User::findOrFail(1);

// Where clauses
User::where('email', $email)->first();
User::query()->where('status', 'active')->paginate(10);

// Create
User::create(['name' => 'John', 'email' => 'john@example.com']);

// Update
$user->update(['name' => 'Jane']);

// Delete
$user->delete();

// Relationships (coming soon)
```

---

## Database & Migrations

### Creating Migrations

```bash
php zen make:migration create_users_table
php zen make:migration add_avatar_to_users --table=users
```

### Migration Structure

```php
<?php

return new class {
    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        
        $qb = new \Zen\Database\QueryBuilder();
        $qb->raw($sql);
    }

    public function down(): void
    {
        $qb = new \Zen\Database\QueryBuilder();
        $qb->raw("DROP TABLE IF EXISTS users");
    }
};
```

### Running Migrations

```bash
php zen migrate              # Run all migrations
php zen migrate --rollback  # Rollback last batch
php zen migrate --reset     # Rollback all
php zen migrate --fresh     # Drop and recreate
```

### Seeders

```bash
php zen make:seeder UserSeeder
php zen db:seed              # Run all seeders
php zen db:seed UserSeeder   # Run specific seeder
```

### Factories

```bash
php zen make:factory UserFactory

// Usage
(new UserFactory())->count(10)->create();
```

---

## Views & Templates

### Layouts

```bash
php zen make:layout dashboard --type=app
php zen make:layout login --type=guest
php zen make:layout auth --type=auth
```

### Layout Types

| Type | Use Case |
|------|----------|
| app | Authenticated layout with sidebar |
| guest | Public landing pages |
| auth | Login/Register pages |
| dashboard | Admin panel |
| blank | Minimal empty |
| custom | User-defined |

### Views

```php
// Return view with data
return view('pages.users', ['users' => $users]);

// View with layout
$layout = new \Zen\UI\Layout('layouts.app');
$layout->with(['title' => 'Dashboard']);
$layout->with(['content' => $content]);
return $layout->render();
```

### Components

```bash
php zen make:component Button
php zen make:component Input
```

---

## Template Engine

Zen Framework includes a powerful template engine with `.zen.php` files. It supports layouts, components, and all modern templating features.

### Template Files

Templates use `.zen.php` extension and go in `views/`:
```
views/
├── layouts/
│   └── main.zen.php
├── pages/
│   └── home.zen.php
└── components/
    └── button.zen.php
```

### Variables

```php
// In PHP controller
return view('pages.home', ['name' => 'John', 'items' => [1, 2, 3]]);

// In template
{{ $name }}           // Escaped output
{!! $html !!}         // Raw output (unescaped)
```

### Conditionals

```php
@if($isActive)
    <p>User is active</p>
@elseif($isPending)
    <p>User is pending</p>
@else
    <p>User is inactive</p>
@endif

@unless($isLoggedIn)
    <p>Please log in</p>
@endunless
```

### Loops

```php
@foreach($items as $item)
    <li>{{ $item }}</li>
@endforeach

@for($i = 0; $i < 10; $i++)
    <p>Count: {{ $i }}</p>
@endfor

@while($count > 0)
    <p>{{ $count }}</p>
    @php $count-- @endphp
@endwhile
```

### Layout Inheritance

```php
// child.zen.php
@extends('layouts.main')

@section('title', 'Page Title')

@section('content')
    <h1>Welcome</h1>
    <p>This is the page content</p>
@endsection
```

```php
// layouts/main.zen.php
<!DOCTYPE html>
<html>
<head>
    <title>@yield('title', 'Default Title')</title>
</head>
<body>
    <header>Header</header>
    
    <main>
        @yield('content')
    </main>
    
    <footer>Footer</footer>
</body>
</html>
```

### Include Partial Templates

```php
@include('components.header')
@includeIf('components.sidebar')
@includeWhen($showSidebar, 'components.sidebar')
```

### Components (Zen Tags)

```php
// Self-closing
<zen:Button type="primary">Click Me</zen:Button>
<zen:Card />

// With content
<zen:Card>
    <p>Card content here</p>
</zen:Card>
```

### Form Directives

```php
<form method="POST" action="/users">
    @csrf
    @method('PUT')
    
    <input type="text" name="name" value="{{ old('name') }}">
    
    @json($data)
</form>
```

### Auth Directives

```php
@auth
    <p>Welcome, {{ Auth::user()->name }}</p>
@endauth

@guest
    <p>Please log in</p>
@endguest
```

### PHP Directives

```php
@php
    $now = date('Y');
    $items = ['a', 'b', 'c'];
@endphp
```

### Template Caching

In production, compiled templates are cached for better performance:

```php
// config/app.php
return [
    'cache' => true,  // Enable template caching
];
```

Cache location: `storage/framework/views/`

---

## Session & Auth

### Session

```php
// Put
session()->put('key', 'value');
session(['key' => 'value']);

// Get
$value = session('key');
$value = session('key', 'default');

// Check
session()->has('key');

// Forget
session()->forget('key');

// Flash
session()->flash('success', 'Saved!');
```

### Authentication

```php
// Login
Auth::attempt(['email' => $email, 'password' => $password]);

// Check
Auth::check();
Auth::guest();

// User
$user = Auth::user();
$id = Auth::id();

// Login with user
Auth::login($user);
Auth::loginUsingId(1);

// Logout
Auth::logout();
```

### Auth Routes

```php
// routes/Auth.php
$router->get('/auth/login', [AuthController::class, 'showLogin']);
$router->post('/auth/login', [AuthController::class, 'login']);
$router->post('/auth/logout', [AuthController::class, 'logout']);
$router->get('/auth/register', [AuthController::class, 'showRegister']);
$router->post('/auth/register', [AuthController::class, 'register']);
```

---

## Validation

### Using Validator

```php
use Zen\Validation\Validator;

$validator = Validator::make($request->all(), [
    'name' => 'required|string|min:2|max:100',
    'email' => 'required|email',
    'password' => 'required|string|min:6|confirmed',
    'age' => 'required|integer|minValue:18|maxValue:100',
]);

if ($validator->fails()) {
    return response()->json(['errors' => $validator->errors()], 422);
}
```

### Validation Rules

| Rule | Description |
|------|-------------|
| required | Field is required |
| email | Valid email |
| min:X | Minimum X characters |
| max:X | Maximum X characters |
| numeric | Must be numeric |
| integer | Must be integer |
| string | Must be string |
| array | Must be array |
| url | Valid URL |
| ip | Valid IP |
| alpha | Letters only |
| alphaNum | Letters and numbers |
| date | Valid date |
| unique:table,column | Unique in database |
| exists:table,column | Exists in database |
| in:a,b,c | Must be in list |
| minValue:X | Minimum numeric value |
| maxValue:X | Maximum numeric value |
| confirmed | Must match field_confirmation |

### Form Requests

```bash
php zen make:request StoreUser
```

```php
<?php

namespace App\Http\Requests;

use App\Http\Requests\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ];
    }
}
```

---

## Cache

```php
// Put
Cache::put('key', 'value', 60); // seconds
Cache::forever('key', 'value');

// Get
$value = Cache::get('key');
$value = Cache::get('key', 'default');

// Check
Cache::has('key');

// Forget
Cache::forget('key');

// Remember
$data = Cache::remember('users', 60, function () {
    return User::all();
});

// Flush
Cache::flush();
```

---

## CLI Commands

### Make Commands

```bash
# Core
php zen make:controller UserController
php zen make:model User
php zen make:middleware Auth
php zen make:migration create_users
php zen make:seeder UserSeeder
php zen make:factory UserFactory
php zen make:service UserService
php zen make:provider AppProvider

# UI
php zen make:page Home
php zen make:component Button
php zen make:layout dashboard --type=app

# HTTP
php zen make:request StoreUser
php zen make:job ProcessOrder
php zen make:event UserRegistered
php zen make:listener SendWelcomeEmail
```

### Remove Commands

```bash
php zen remove:model User
php zen remove:controller UserController
php zen remove:migration create_users
php zen remove:seeder UserSeeder
php zen remove:factory UserFactory
php zen remove:service UserService
php zen remove:provider AppProvider
php zen remove:page Home
php zen remove:component Button
php zen remove:layout dashboard
```

### Rename Commands

```bash
php zen rename:model User --to=Customer
php zen rename:controller Auth --to=Authentication
php zen rename:service User --to=UserService
```

### Database Commands

```bash
php zen migrate
php zen migrate --rollback
php zen migrate --reset
php zen migrate --fresh
php zen db:seed
php zen db:seed UserSeeder
```

### Other Commands

```bash
php zen route:list
php zen route:list --json
php zen cache:clear
php zen serve
php zen serve --host=0.0.0.0 --port=8080
php zen lint
php zen test
php zen class:resolve
php zen class:resolve --fix
```

---

## REST API Development

### API Routes

```php
// routes/Api.php
$router->get('/api/users', [UserController::class, 'index']);
$router->post('/api/users', [UserController::class, 'store']);
$router->get('/api/users/{id}', [UserController::class, 'show']);
$router->put('/api/users/{id}', [UserController::class, 'update']);
$router->delete('/api/users/{id}', [UserController::class, 'destroy']);
```

### JSON Responses

```php
// Success
return response()->json(['user' => $user]);

// With status
return response()->json(['message' => 'Created'], 201);

// Error
return response()->json(['error' => 'Not found'], 404);
```

---

## Deployment

### Requirements

- PHP 8.4+
- PDO Extension
- Write permissions for `storage/` and `database/`

### Steps

1. Clone repository
2. Configure `.env`
3. Run migrations: `php zen migrate`
4. Set web server document root to `public/`
5. Configure PHP settings

### Web Server Config (Apache)

```apache
<VirtualHost *:80>
    DocumentRoot /var/www/zen/public
    ServerName zen.local
    
    <Directory /var/www/zen/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
    </Directory>
</VirtualHost>
```

### Web Server Config (Nginx)

```nginx
server {
    listen 80;
    server_name zen.local;
    root /var/www/zen/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## Next Steps

- Read [COMPARE.md](./COMPARE.md) for framework comparison
- Check [SKILLS.md](./SKILLS.md) for CLI reference
- See [AGENTS.md](./AGENTS.md) for AI agent instructions
