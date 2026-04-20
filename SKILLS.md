# Zenith Framework Skills Reference

Complete reference for developers working with Zenith Framework.

## Table of Contents

1. [Project Structure](#project-structure)
2. [CLI Commands](#cli-commands)
3. [Routing](#routing)
4. [Controllers](#controllers)
5. [Pages](#pages)
6. [Models](#models)
7. [Views & Templates](#views--templates)
8. [Validation](#validation)
9. [Middleware](#middleware)
10. [Services](#services)
11. [Service Providers](#service-providers)
12. [Helper Functions](#helper-functions)
13. [Request](#request)
14. [Response](#response)
15. [Redirect](#redirect)
16. [Session](#session)
17. [Auth](#auth)
18. [Cache](#cache)
19. [Database & QueryBuilder](#database--querybuilder)
20. [Migrations](#migrations)
21. [AI](#ai)
22. [Testing](#testing)
23. [Configuration](#configuration)
24. [Bootstrap & Entry Point](#bootstrap--entry-point)
25. [Container (DI)](#container-di)

---

## Project Structure

```
zen/
├── app/
│   ├── Http/Controllers/     # Controllers (plain classes)
│   ├── Http/Middleware/      # Middleware (implements MiddlewareInterface)
│   ├── Http/Requests/        # Form Request validation
│   ├── Models/               # Eloquent-style models (extends Model)
│   ├── Pages/                # Page classes (render() method)
│   ├── Services/             # Business logic services
│   ├── Providers/            # Service providers (extends ServiceProvider)
│   └── UI/Components/        # UI components
├── boot/                     # Bootstrap files
│   ├── Autoloader.php        # PSR-4 autoloader
│   ├── ConfigLoader.php      # Config loader
│   ├── Engine.php            # Request handler + middleware pipeline
│   ├── Ignition.php          # Application bootstrap
│   ├── RouteLoader.php       # Route file loader
│   └── ServiceProvider.php   # Base provider class
├── config/                   # Configuration
│   ├── app.php               # App config + providers
│   └── database.php          # Database connections
├── core/                     # Framework core
│   ├── AI/                   # AI integration (OpenAI, Anthropic, Ollama)
│   ├── Auth/                 # Authentication
│   ├── Cache/                # Cache system
│   ├── Console/Commands/     # CLI commands
│   ├── Container.php         # Dependency injection container
│   ├── Database/             # Model, QueryBuilder, Schema
│   ├── Http/                 # Request, Response, Redirect
│   ├── Log/                  # Logging
│   ├── Middleware/           # MiddlewareInterface
│   ├── Routing/              # Router, Route, RouteGroup
│   ├── Session/              # Session management
│   ├── Support/              # Helpers, Str, Arr
│   ├── UI/                   # Page, Component, Layout
│   └── Validation/           # Validator
├── database/
│   ├── migrations/           # Migration files
│   ├── seeders/              # Database seeders
│   └── factories/            # Model factories
├── public/
│   └── index.php             # Web entry point
├── routes/                   # Route files
│   ├── Web.php               # Public routes (prefix: /)
│   ├── Api.php               # API routes (prefix: /api)
│   ├── Auth.php              # Auth routes (prefix: /auth)
│   └── Ai.php                # AI routes (prefix: /ai)
├── tests/                    # Test files
├── views/                    # View templates
│   ├── layouts/              # Layout files (.zen.php)
│   ├── components/           # Reusable components
│   └── pages/                # Page templates
├── zen                       # CLI entry point
└── .env                      # Environment config
```

---

## CLI Commands

### Development

| Command | Description | Options |
|---------|-------------|---------|
| `php zen serve` | Start dev server | `--host`, `--port` |
| `php zen lint` | Syntax check | `--no-imports` |
| `php zen test` | Run tests | |

### Make Commands

| Command | Description | Options |
|---------|-------------|---------|
| `php zen make:model <Name>` | Create Model | `--migration`, `--factory` |
| `php zen make:controller <Name>` | Create Controller | `--resource`, `--api`, `--auth` |
| `php zen make:middleware <Name>` | Create Middleware | |
| `php zen make:migration <Name>` | Create Migration | `--create`, `--table` |
| `php zen make:seeder <Name>` | Create Seeder | `--model` |
| `php zen make:factory <Name>` | Create Factory | `--model` |
| `php zen make:service <Name>` | Create Service | |
| `php zen make:provider <Name>` | Create Service Provider | |
| `php zen make:page <Name>` | Create Page | |
| `php zen make:component <Name>` | Create Component | |
| `php zen make:layout <Name>` | Create Layout | `--type=app,guest,blank,auth,dashboard,custom` |
| `php zen make:request <Name>` | Create Form Request | |

### Remove Commands

| Command | Description |
|---------|-------------|
| `php zen remove:model <Name>` | Delete Model |
| `php zen remove:controller <Name>` | Delete Controller |
| `php zen remove:middleware <Name>` | Delete Middleware |
| `php zen remove:migration <Name>` | Delete Migration |
| `php zen remove:seeder <Name>` | Delete Seeder |
| `php zen remove:factory <Name>` | Delete Factory |
| `php zen remove:service <Name>` | Delete Service |
| `php zen remove:provider <Name>` | Delete Provider |
| `php zen remove:page <Name>` | Delete Page |
| `php zen remove:component <Name>` | Delete Component |
| `php zen remove:layout <Name>` | Delete Layout |
| `php zen remove:request <Name>` | Delete Request |

### Rename Commands

| Command | Description |
|---------|-------------|
| `php zen rename:model <Name> --to=<NewName>` | Rename Model |
| `php zen rename:controller <Name> --to=<NewName>` | Rename Controller |
| `php zen rename:service <Name> --to=<NewName>` | Rename Service |

### Database Commands

| Command | Description | Options |
|---------|-------------|---------|
| `php zen migrate` | Run migrations | |
| `php zen migrate --rollback` | Rollback last batch | |
| `php zen migrate --reset` | Rollback all | |
| `php zen migrate --fresh` | Drop all and recreate | |
| `php zen db:seed` | Run all seeders | |
| `php zen db:seed <Name>` | Run specific seeder | |

### Route Commands

| Command | Description |
|---------|-------------|
| `php zen route:list` | List all routes |
| `php zen route:list --json` | List routes as JSON |
| `php zen route:cache` | Compile route cache |
| `php zen route:test <uri> [method]` | Test a route |
| `php zen route:fix` | Analyze routes for issues |
| `php zen route:convert` | Convert closures to controllers |

### Cache & Other

| Command | Description | Options |
|---------|-------------|---------|
| `php zen cache:clear` | Clear cache | `--all` |
| `php zen optimize:clear` | Clear view, cache, sessions, logs | |
| `php zen class:resolve` | Fix imports | `--fix` |

---

## Routing

### Route Files

Routes are split across files with automatic prefixes:

| File | URL Prefix | Purpose |
|------|-----------|---------|
| `routes/Web.php` | `/` | Public web pages |
| `routes/Api.php` | `/api` | JSON API endpoints |
| `routes/Auth.php` | `/auth` | Authentication |
| `routes/Ai.php` | `/ai` | AI integration |

Each route file **must return** `$router`:

```php
<?php

declare(strict_types=1);

use App\Http\Controllers\UserController;
use App\Http\Controllers\HelloController;
use App\Pages\Welcome;

// Page class
$router->get('/', [Welcome::class, 'render']);

// Closure route
$router->get('/test', fn() => view('pages.test', [
    'title' => 'Test Page',
]));

// Controller with URI parameter
$router->get('/hello/{name}', [HelloController::class, 'greet']);

// CRUD routes
$router->get('/users', [UserController::class, 'index']);
$router->get('/users/{id}', [UserController::class, 'show']);
$router->post('/users', [UserController::class, 'store']);
$router->put('/users/{id}', [UserController::class, 'update']);
$router->delete('/users/{id}', [UserController::class, 'destroy']);

return $router;
```

### Handler Formats

```php
// 1. Controller class + method (most common)
$router->get('/users', [UserController::class, 'index']);

// 2. Closure
$router->get('/greeting', fn() => view('pages.greeting'));

// 3. String format
$router->get('/users', 'UserController@index');

// 4. Invokable controller
$router->get('/health', [HealthController::class, '__invoke']);
```

### Route Parameters

```php
// Single parameter - passed as typed argument to controller method
$router->get('/users/{id}', [UserController::class, 'show']);

// Multiple parameters
$router->get('/users/{userId}/posts/{postId}', [PostController::class, 'show']);

// Controller receives parameters as typed arguments:
public function show(Request $request, int $id): Response
{
    $user = User::findOrFail($id);
    return view('users.show', compact('user'));
}
```

### Named Routes

```php
$router->get('/dashboard', [DashboardController::class, 'index'], name: 'dashboard');

// Generate URL
$url = route('dashboard');
$url = route('user.profile', ['id' => 5]);
```

### Route Groups

```php
// Prefix
$router->prefix('/admin');
$router->get('/dashboard', [AdminController::class, 'dashboard']);
$router->get('/users', [AdminController::class, 'users']);

// Middleware
$router->middleware(['auth', 'admin']);
$router->get('/settings', [SettingsController::class, 'index']);

// Group with callback
$router->group('/api/v1', function ($router) {
    $router->get('/users', [Api\UserController::class, 'index']);
    $router->post('/users', [Api\UserController::class, 'store']);
});
```

### Resource Routes

```php
// Full CRUD (7 routes: index, create, store, show, edit, update, destroy)
$router->resource('posts', PostController::class);

// API resource (5 routes: no create/edit views)
$router->apiResource('products', ProductController::class);
```

### Router API

```php
$router->get($uri, $handler, ?name: null): Route
$router->post($uri, $handler, ?name: null): Route
$router->put($uri, $handler, ?name: null): Route
$router->patch($uri, $handler, ?name: null): Route
$router->delete($uri, $handler, ?name: null): Route
$router->options($uri, $handler, ?name: null): Route
$router->any($uri, $handler, ?name: null): Route

$router->group($prefix, $callback): self
$router->prefix($prefix): self
$router->middleware($middleware): self
$router->controller($controller): self
$router->name($name): self
$router->resource($name, $controller): void
$router->apiResource($name, $controller): void

$router->url($name, $params = []): string
```

---

## Controllers

Controllers are **plain PHP classes**. They do **NOT** need to extend a base controller.

### Basic Controller

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Zen\Http\Request;
use Zen\Http\Response;
use App\Models\User;

class UserController
{
    public function index(Request $request): Response
    {
        $users = User::all();
        return view('users.index', compact('users'));
    }

    public function show(Request $request, int $id): Response
    {
        $user = User::findOrFail($id);
        return view('users.show', compact('user'));
    }

    public function store(Request $request): Response
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
        ]);

        $user = User::create($data);

        return redirect('/users/' . $user->id);
    }

    public function update(Request $request, int $id): Response
    {
        $user = User::findOrFail($id);
        $user->fill($request->body);
        $user->save();

        return redirect('/users/' . $user->id);
    }

    public function destroy(Request $request, int $id): Response
    {
        User::findOrFail($id)->delete();
        return redirect('/users');
    }
}
```

### API Controller

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Zen\Http\Request;
use Zen\Http\Response;
use App\Models\Post;

class PostController
{
    public function index(Request $request): Response
    {
        $posts = Post::all();
        return json(['posts' => $posts]);
    }

    public function show(Request $request, int $id): Response
    {
        $post = Post::find($id);
        if ($post === null) {
            return json(['error' => 'Post not found'], 404);
        }
        return json(['post' => $post]);
    }

    public function store(Request $request): Response
    {
        $post = Post::create($request->body);
        return json(['post' => $post], 201);
    }

    public function update(Request $request, int $id): Response
    {
        $post = Post::findOrFail($id);
        $post->fill($request->body);
        $post->save();
        return json(['post' => $post]);
    }

    public function destroy(Request $request, int $id): Response
    {
        Post::findOrFail($id)->delete();
        return json(['message' => 'Post deleted']);
    }
}
```

### Controller with Base Class (Optional)

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Zen\Http\Request;
use Zen\Http\Response;
use Zen\Container;

abstract class Controller
{
    protected Container $container;

    public function __construct()
    {
        $this->container = app(Container::class);
    }

    protected function request(): Request
    {
        return app(Request::class);
    }

    protected function response(array|string $content, int $status = 200): Response
    {
        return new Response($content, $status);
    }

    protected function json(array $data, int $status = 200): Response
    {
        return new Response(json_encode($data), $status, [
            'Content-Type' => 'application/json',
        ]);
    }

    protected function redirect(string $uri): \Zen\Http\Redirect
    {
        return new \Zen\Http\Redirect($uri);
    }

    protected function back(): \Zen\Http\Redirect
    {
        return new \Zen\Http\Redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }

    protected function view(string $view, array $data = []): Response
    {
        $content = view($view, $data);
        return new Response($content);
    }

    protected function abort(int $code, string $message = ''): void
    {
        abort($code, $message);
    }
}
```

### Invokable Controller

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Zen\Http\Request;
use Zen\Http\Response;

class HealthCheckController
{
    public function __invoke(Request $request): Response
    {
        return json([
            'status' => 'ok',
            'timestamp' => now(),
        ]);
    }
}
```

### Route Registration

```php
// routes/Web.php
use App\Http\Controllers\HealthCheckController;

$router->get('/health', [HealthCheckController::class, '__invoke']);
```

---

## Pages

Page classes are plain PHP classes with a `render()` method returning a string.

```php
<?php

declare(strict_types=1);

namespace App\Pages;

class Welcome
{
    public function render(): string
    {
        return view('pages.welcome');
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Pages;

class Home
{
    public function render(): string
    {
        return view('pages.home');
    }
}
```

### Usage in Routes

```php
use App\Pages\Welcome;
use App\Pages\Home;

$router->get('/', [Welcome::class, 'render']);
$router->get('/home', [Home::class, 'render']);
```

---

## Models

Models extend `Zen\Database\Model` and use an Eloquent-style API.

### Basic Model

```php
<?php

declare(strict_types=1);

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

    // Mutator
    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = password_hash($value, PASSWORD_DEFAULT);
    }

    // Custom method
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->attributes['password'] ?? '');
    }
}
```

### Model Properties

```php
protected static string $table = 'users';     // Table name (auto-pluralized if not set)
protected array $fillable = [];                // Mass assignable attributes
protected array $hidden = [];                  // Hidden from toArray()/toJson()
protected array $casts = [];                   // Type casting
protected $timestamps = true;                  // Auto timestamps
```

### Model API

```php
// Static methods
User::all()                           // Get all records
User::find($id)                       // Find by ID (returns model or null)
User::findOrFail($id)                 // Find or throw exception
User::where('col', 'val')             // Start query
User::create($data)                   // Create and save
User::updateOrCreate($match, $values) // Find or create
User::firstWhere('col', 'val')        // First matching record
User::count()                         // Count records
User::paginate(15)                    // Paginate results
User::table()                         // Get table name

// Instance methods
$user->save()                         // Save to database
$user->delete()                       // Delete from database
$user->fill($data)                    // Fill attributes
$user->fresh()                        // Refresh from DB
$user->toArray()                      // Convert to array
$user->toJson()                       // Convert to JSON
$user->getAttributes()                // Get all attributes
$user->getDirty()                     // Get changed attributes
$user->isFillable($key)               // Check if fillable
$user->setAttribute($key, $value)     // Set single attribute
$user->getAttribute($key)             // Get single attribute
$user->getHidden()                    // Get hidden fields
$user->setHidden(['password'])        // Set hidden fields
```

---

## Views & Templates

### View Files

View files use `.zen.php` extension and support Blade-like directives.

```php
// Render view
view('pages.welcome')
view('pages.user', ['user' => $user])
view('emails.welcome', ['name' => $user->name])
```

### Layout Directives

```blade
@extends('layouts.main')

@section('title', 'Page Title')

@section('content')
    <h1>Welcome</h1>
    <p>Page content here</p>
@endsection
```

### Control Structures

```blade
@if($condition)
    <p>True</p>
@elseif($otherCondition)
    <p>Other</p>
@else
    <p>False</p>
@endif

@unless($condition)
    <p>Condition is false</p>
@endunless

@isset($variable)
    <p>Variable is set</p>
@endisset

@empty($variable)
    <p>Variable is empty</p>
@endempty
```

### Loops

```blade
@foreach($users as $user)
    <p>{{ $user->name }}</p>
@endforeach

@forelse($users as $user)
    <p>{{ $user->name }}</p>
@empty
    <p>No users found</p>
@endforelse

@for($i = 0; $i < 10; $i++)
    <p>{{ $i }}</p>
@endfor

@while($condition)
    <p>Looping...</p>
@endwhile
```

### Output

```blade
{{-- Escaped output --}}
{{ $variable }}
{{ $user->name }}
{{ 2 + 2 }}

{{-- Raw output (unescaped) --}}
{!! $html !!}

{{-- JSON output --}}
@json($data)

{{-- PHP block --}}
@php
    $total = $price * $quantity;
@endphp
```

### Includes & Components

```blade
@include('partials.header')
@includeIf('partials.sidebar')

@component('alert', ['type' => 'success'])
    Operation completed!
@endcomponent
```

### Forms & CSRF

```blade
<form method="POST">
    @csrf
    @method('PUT')
    <button type="submit">Submit</button>
</form>
```

### Auth Directives

```blade
@auth
    <p>Welcome, {{ auth()->user()->name }}</p>
@endauth

@guest
    <p>Please log in</p>
@endguest

@auth('admin')
    <p>Admin area</p>
@endauth
```

### Stacks

```blade
{{-- In layout --}}
@stack('scripts')

{{-- In page --}}
@push('scripts')
    <script src="/app.js"></script>
@endpush

@prepend('scripts')
    <script src="/setup.js"></script>
@endprepend
```

### Environment

```blade
@env('local')
    <p>Development mode</p>
@endenv

@production
    <p>Production analytics</p>
@endproduction
```

### Zen Components

```blade
<zen:button variant="primary" size="lg">Click Me</zen:button>
<zen:counter />
<zen:alert type="success">Success message</zen:alert>
```

---

## Validation

### Basic Validation

```php
use Zen\Validation\Validator;

$validator = Validator::make($request->all(), [
    'name' => 'required|string|min:2|max:255',
    'email' => 'required|email|unique:users,email',
    'password' => 'required|min:8|confirmed',
    'age' => 'numeric|minValue:18|maxValue:100',
    'website' => 'url',
    'role' => 'in:admin,user,guest',
]);

if ($validator->fails()) {
    return response()->json(['errors' => $validator->errors()], 422);
}

$cleanData = $validator->validated();
```

### Request Validation Helper

```php
// Throws on failure
$data = $request->validate([
    'name' => 'required',
    'email' => 'required|email',
]);
```

### Validation Rules

| Rule | Example | Description |
|------|---------|-------------|
| `required` | `'name' => 'required'` | Must not be empty |
| `email` | `'email' => 'email'` | Valid email |
| `min:N` | `'name' => 'min:2'` | Minimum length/value |
| `max:N` | `'name' => 'max:255'` | Maximum length/value |
| `numeric` | `'price' => 'numeric'` | Numeric value |
| `integer` | `'age' => 'integer'` | Integer value |
| `string` | `'name' => 'string'` | String type |
| `array` | `'tags' => 'array'` | Array type |
| `boolean` | `'active' => 'boolean'` | Boolean type |
| `url` | `'website' => 'url'` | Valid URL |
| `ip` | `'ip' => 'ip'` | Valid IP |
| `alpha` | `'name' => 'alpha'` | Letters only |
| `alphaNum` | `'user' => 'alphaNum'` | Letters + numbers |
| `date` | `'dob' => 'date'` | Valid date |
| `confirmed` | `'password' => 'confirmed'` | Must match `_confirmation` |
| `unique:table,col` | `'email' => 'unique:users,email'` | Unique in DB |
| `exists:table,col` | `'role' => 'exists:roles,id'` | Exists in DB |
| `in:a,b,c` | `'status' => 'in:active,inactive'` | Must be in list |
| `notIn:a,b` | `'role' => 'notIn:admin'` | Must not be in list |
| `minValue:N` | `'age' => 'minValue:18'` | Minimum value |
| `maxValue:N` | `'age' => 'maxValue:100'` | Maximum value |
| `between:a,b` | `'age' => 'between:18,100'` | Between values |
| `match:field` | `'pass' => 'match:confirm'` | Must match field |

### Custom Error Messages

```php
$validator = Validator::make($data, $rules, [
    'email.required' => 'Please enter your email address',
    'email.email' => 'Please enter a valid email address',
    'name.min' => 'Name must be at least :param characters',
]);
```

---

## Middleware

### Middleware Interface

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Zen\Http\Request;
use Zen\Http\Response;
use Zen\Middleware\MiddlewareInterface;

class Auth implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (!auth()->check()) {
            return redirect('/auth/login');
        }

        return $next($request);
    }
}
```

### Built-in Middleware

| Middleware | Alias | Purpose |
|-----------|-------|---------|
| `Auth` | `auth` | Require authentication |
| `Guest` | `guest` | Require NOT authenticated |
| `Csrf` | `csrf` | CSRF token verification |

### Apply Middleware

```php
// Global middleware (in boot/Engine.php)
$engine->middleware(['csrf']);
$engine->addMiddleware('auth');

// Route middleware (in route file)
$router->get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth']);
```

### CSRF Middleware

```php
// Skip specific routes
$csrfMiddleware->except(['/webhook', '/api/callback']);
```

---

## Services

Services are plain PHP classes for business logic.

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Zen\Auth\Auth;
use Zen\Validation\Validator;

class AuthService
{
    public function login(array $credentials): ?User
    {
        if (Auth::attempt($credentials)) {
            return Auth::user();
        }
        return null;
    }

    public function logout(): void
    {
        Auth::logout();
    }

    public function register(array $data): User
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|min:2',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            throw new \RuntimeException(json_encode($validator->errors()));
        }

        $user = User::create($data);
        Auth::login($user);

        return $user;
    }

    public function getUser(): ?User
    {
        return Auth::user();
    }

    public function isAuthenticated(): bool
    {
        return Auth::check();
    }
}
```

### Usage in Controller

```php
class AuthController
{
    public function __construct(
        private AuthService $authService,
    ) {}

    public function login(Request $request): Response
    {
        $user = $this->authService->login($request->only(['email', 'password']));

        if ($user === null) {
            return response('Invalid credentials', 401);
        }

        return redirect('/dashboard');
    }
}
```

---

## Service Providers

Providers register services in the DI container.

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Zen\Boot\ServiceProvider;
use Zen\Database\QueryBuilder;
use Zen\Session\Session;
use Zen\Cache\Cache;
use App\Services\AuthService;

class AppProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app()->singleton(AuthService::class, fn() => new AuthService());
        $this->app()->bind(QueryBuilder::class, fn() => new QueryBuilder());
        $this->app()->bind(Session::class, fn() => new Session());
        $this->app()->bind(Cache::class, fn() => new Cache());
    }

    public function boot(): void
    {
        session()->set('csrf_token', csrf_token());
    }
}
```

### Register Providers

```php
// config/app.php
return [
    // ...
    'providers' => [
        \App\Providers\AppProvider::class,
    ],
];
```

---

## Helper Functions

### Core

```php
app()                      // Container instance
app('class')               // Resolve from container
app(Container::class)      // Resolve typed class
config('app.name')         // Get config value (dot notation)
config('app.name', 'Zen')  // Get with default
env('APP_NAME', 'Zen')     // Get env value
```

### Views

```php
view('pages.welcome')                     // Render view
view('pages.user', ['user' => $user])     // With data
render_php_template($file, $data)         // Render PHP template
render_zen_template($file, $data)         // Render with Zen directives
render_zen_with_layout($template, $layout, $data)  // Render with layout
```

### Request/Response

```php
request()                    // Current Request
request('key')               // Get input value
request()->all()             // All input
request()->only(['a', 'b'])  // Specific keys
request()->header('X-Token') // Get header

response()                   // Create response
response('Hello')            // With content
response('Not Found', 404)   // With status
response()->json($data)      // JSON response
response()->jsonData($data)  // Raw JSON (no wrapper)

json(['status' => 'ok'])     // JSON helper
json(['error' => 'Not Found'], 404)
```

### Routing

```php
redirect('/dashboard')       // Redirect response
redirect('/path', 301)       // With status

route('dashboard')           // Generate URL by name
route('user.profile', ['id' => 5])  // With parameters
```

### Assets

```php
asset('css/app.css')         // Asset URL (prefixes with app URL)
```

### CSRF

```php
csrf_token()                 // Get CSRF token
session_token()              // Alias for csrf_token()
csrf_field()                 // Generate CSRF input field
```

### Session

```php
session()                    // Session instance
session('key')               // Get session value
session('key', 'default')    // Get with default
session()->get('key')        // Get value
session()->put('key', 'val') // Set value
```

### Auth

```php
auth()                       // Auth instance
auth('web')                  // Auth with guard
auth()->check()              // Is authenticated
auth()->user()               // Current user
auth()->id()                 // Current user ID
```

### Debug

```php
dd($value)                   // Dump and die
abort(404)                   // Throw HTTP error
abort(403, 'Forbidden')      // With message
```

---

## Request

```php
$request = request();

// Input
$request->input('name')                  // Get input with key
$request->input('name', 'default')       // With default
$request->all()                          // All input
$request->only(['name', 'email'])        // Specific keys
$request->except(['password'])           // Exclude keys
$request->has('name')                    // Check has key
$request->hasAny(['name', 'email'])      // Check has any
$request->filled('name')                 // Check not empty
$request->body                           // Raw body (for JSON/API)

// Headers
$request->header('X-Token')              // Get header
$request->header('Authorization')        // Auth header
$request->bearerToken()                  // Bearer token

// Method
$request->isMethod('POST')               // Check HTTP method
$request->isAjax()                       // Check AJAX request
$request->expectsJson()                  // Expects JSON

// URL
$request->url()                          // Current URL
$request->fullUrl()                      // Full URL with query
$request->path()                         // Path only
$request->segment(1)                     // URL segment
$request->segments()                     // All segments
$request->is('api/*')                    // Match pattern

// Info
$request->ip()                           // Client IP
$request->userAgent()                    // User agent string

// Files
$request->file('avatar')                // Get uploaded file

// Validation (throws on failure)
$data = $request->validate([
    'name' => 'required',
    'email' => 'required|email',
]);
```

---

## Response

### Creating Responses

```php
// Basic
response('Hello World')
response('Not Found', 404)
response('Created', 201, ['X-Custom' => 'value'])

// JSON
response()->json(['status' => 'ok'])            // Wrapped: {status, data}
response()->jsonData(['key' => 'value'])        // Raw JSON

// Redirect
response()->redirect('/dashboard')
response()->back()
response()->toRoute('dashboard')

// Download
response()->download('/path/to/file.pdf')
response()->download('/path/to/file.pdf', 'report.pdf')

// Stream
response()->stream(function () {
    echo "chunk 1";
    ob_flush();
    flush();
})

// Cookie
response()->cookie('name', 'value', 60)
response()->withCookie('remember', 'token', 43200)
```

### Static Factories

```php
Response::make($content, $status, $headers)   // Factory
Response::noContent()                          // 204
Response::notFound('Not Found')                // 404
Response::unauthorized('Unauthorized')         // 401
Response::forbidden('Forbidden')               // 403
Response::error('Server Error', 500)           // 500
```

### Headers & Status

```php
$response->withHeader('X-Custom', 'value')
$response->withHeaders(['X-A' => '1', 'X-B' => '2'])
$response->withoutHeader('X-Custom')
$response->withContent('New content')
$response->withStatus(201)
```

### Status Checkers

```php
$response->isSuccessful()    // 2xx
$response->isRedirect()      // 3xx
$response->isClientError()   // 4xx
$response->isServerError()   // 5xx
```

### Getters

```php
$response->getContent()
$response->getStatusCode()
$response->getHeaders()
```

---

## Redirect

```php
Redirect::to('/dashboard')           // Basic redirect
Redirect::to('/path', 301)           // With status
Redirect::back()                     // Back to referer
Redirect::route('dashboard')         // By named route
Redirect::route('user', ['id' => 5]) // With params
Redirect::home()                     // To root
Redirect::away('https://external.com') // External URL
```

---

## Session

```php
Session::start()                     // Start session
Session::put('key', 'value')         // Set value
Session::set('key', 'value')         // Fluent set (returns Session)
Session::get('key')                  // Get value
Session::get('key', 'default')       // With default
Session::has('key')                  // Check exists
Session::forget('key')               // Remove
Session::flush()                     // Clear all
Session::flash('key', 'value')       // One-time value
Session::regenerate()                // Regenerate ID
Session::invalidate()                // Destroy session
Session::all()                       // Get all data
Session::token()                     // Get CSRF token
Session::isStarted()                 // Check if started
```

### Configuration

```php
Session::$name = 'ZEN_SESSION';      // Cookie name
Session::$secure = false;             // Secure flag
Session::$httpOnly = true;            // HttpOnly
Session::$sameSite = 'Lax';           // SameSite
Session::setConfig([...]);            // Configure all at once
Session::setName('CUSTOM_NAME');      // Set cookie name
```

---

## Auth

```php
Auth::check()                        // Is authenticated
Auth::guest()                        // Is guest
Auth::user()                         // Current user (Model or null)
Auth::id()                           // Current user ID
Auth::login($user)                   // Login user instance
Auth::loginUsingId(1)                // Login by ID
Auth::logout()                       // Logout
Auth::attempt($credentials)          // Attempt login
Auth::once($credentials)             // Login without session
Auth::guard('web')                   // Set guard
Auth::getGuard()                     // Get guard name
Auth::loadFromSession()              // Load from session
```

### Attempt Login

```php
if (Auth::attempt(['email' => $email, 'password' => $password])) {
    // Success
    return redirect('/dashboard');
}

return response('Invalid credentials', 401);
```

---

## Cache

```php
Cache::get('key')                    // Get value
Cache::get('key', 'default')         // With default
Cache::has('key')                    // Check exists
Cache::set('key', 'value', 3600)     // Set with TTL (seconds)
Cache::forever('key', 'value')       // Set permanently
Cache::forget('key')                 // Remove
Cache::flush()                       // Clear all
Cache::remember('key', 3600, fn() => expensiveOperation())
Cache::rememberForever('key', fn() => data())
Cache::increment('key', 1)           // Increment
Cache::decrement('key', 1)           // Decrement
Cache::setDriver('file')             // Switch driver
```

### Drivers

- `file` — File-based (default)
- `array` — In-memory

---

## Database & QueryBuilder

### Model Queries

```php
// Basic
User::all()
User::find(1)
User::findOrFail(1)
User::count()
User::paginate(15)

// Where
User::where('status', 'active')->get()
User::where('age', '>=', 18)->get()
User::whereIn('role', ['admin', 'user'])->get()
User::whereNull('deleted_at')->get()
User::whereNotNull('email_verified_at')->get()
User::orWhere('name', 'John')->get()

// Grouping
User::groupBy('role')->having('count(*)', '>', 1)->get()

// Ordering
User::orderBy('name')->get()
User::orderBy('created_at', 'DESC')->get()
User::latest()->get()
User::oldest()->get()

// Limiting
User::limit(10)->get()
User::offset(20)->limit(10)->get()
User::forPage(2, 15)->get()

// Aggregates
User::count()
User::max('age')
User::min('age')
User::avg('age')
User::sum('score')
User::exists()

// Joins
QueryBuilder::table('posts')
    ->join('users', 'posts.user_id', '=', 'users.id')
    ->leftJoin('categories', 'posts.category_id', '=', 'categories.id')
    ->select(['posts.title', 'users.name', 'categories.name'])
    ->get();
```

### QueryBuilder

```php
use Zen\Database\QueryBuilder;

$qb = new QueryBuilder();
$qb->table('users')
    ->select(['id', 'name', 'email'])
    ->where('status', 'active')
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->get();

// CRUD
$qb->table('users')->insert(['name' => 'John']);
$qb->table('users')->where('id', 1)->update(['name' => 'Jane']);
$qb->table('users')->where('id', 1)->delete();
$qb->lastInsertId();

// Raw SQL
$qb->raw('SELECT * FROM users WHERE status = ?', ['active']);

// Transactions
$qb->beginTransaction();
try {
    $qb->table('users')->insert(['name' => 'John']);
    $qb->table('posts')->insert(['title' => 'Hello', 'user_id' => $qb->lastInsertId()]);
    $qb->commit();
} catch (\Throwable $e) {
    $qb->rollBack();
    throw $e;
}

// Auto transaction
$qb->transaction(function ($qb) {
    $qb->table('users')->insert(['name' => 'John']);
    $qb->table('posts')->insert(['title' => 'Hello']);
});

// Chunk processing
$qb->table('users')->chunk(100, function ($users) {
    foreach ($users as $user) {
        // Process
    }
});

// Reset
$qb->reset();
```

---

## Migrations

Migrations return anonymous classes with `up()` and `down()` methods.

```php
<?php

declare(strict_types=1);

use Zen\Database\Schema;

return new class {
    public function up(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

### Schema Builder

```php
// Create table
Schema::create('users', function ($table) {
    $table->id();                              // BIGINT AUTO_INCREMENT PRIMARY KEY
    $table->string('name');                    // VARCHAR(255)
    $table->string('email', 100);              // VARCHAR(100)
    $table->text('bio')->nullable();           // TEXT, nullable
    $table->integer('age');                    // INT
    $table->unsignedBigInteger('user_id');     // UNSIGNED BIGINT
    $table->boolean('active')->default(true);  // BOOLEAN
    $table->decimal('price', 8, 2);            // DECIMAL(8,2)
    $table->timestamp('published_at');         // TIMESTAMP
    $table->json('settings');                  // JSON
    $table->enum('status', ['draft', 'published']);
    $table->foreign('user_id')
          ->references('id')
          ->on('users')
          ->onDelete('cascade');
    $table->unique('email');
    $table->index('status');
    $table->timestamps();                      // created_at, updated_at
});

// Modify table
Schema::table('users', function ($table) {
    $table->string('phone')->nullable();
});

// Drop table
Schema::dropIfExists('users');
```

---

## AI

```php
use Zen\AI\AI;

// Basic chat
$response = AI::chat()
    ->provider('openai')
    ->model('gpt-3.5-turbo')
    ->apiKey($apiKey)
    ->withUserMessage('Hello, how are you?')
    ->send();

// With system message
$response = AI::chat()
    ->provider('openai')
    ->model('gpt-4')
    ->temperature(0.7)
    ->maxTokens(2048)
    ->withSystemMessage('You are a helpful assistant.')
    ->withUserMessage('Explain PHP 8.4 features')
    ->send();

// Multi-turn conversation
$response = AI::chat()
    ->provider('anthropic')
    ->model('claude-3')
    ->withSystemMessage('You are a code reviewer.')
    ->withUserMessage('Review this code')
    ->withAssistantMessage('The code looks good.')
    ->withUserMessage('What about security?')
    ->send();

// Get response as array
$responseArray = AI::chat()
    ->provider('openai')
    ->model('gpt-4')
    ->withUserMessage('Hello')
    ->json();

// Streaming
AI::chat()
    ->provider('openai')
    ->model('gpt-4')
    ->withUserMessage('Write a long story')
    ->stream(function ($chunk) {
        echo $chunk;
        ob_flush();
        flush();
    });
```

### Providers

| Provider | Models |
|----------|--------|
| `openai` | `gpt-3.5-turbo`, `gpt-4`, `gpt-4o` |
| `anthropic` | `claude-3`, `claude-3-opus`, `claude-3-sonnet` |
| `ollama` | `llama2`, `mistral`, `codellama` (local) |

### Configuration

```php
// Via config
$apiKey = config('ai.openai_key') ?? getenv('AI_API_KEY');

// Or directly
AI::chat()->apiKey('sk-...');
AI::chat()->baseUrl('https://custom-endpoint.com/v1');
```

---

## Testing

### Writing Tests

```php
<?php

declare(strict_types=1);

namespace Tests;

class UserTest extends TestCase
{
    public function test_true_is_true(): void
    {
        $this->assertTrue(true);
    }

    public function test_false_is_false(): void
    {
        $this->assertFalse(false);
    }

    public function test_addition(): void
    {
        $this->assertEquals(4, 2 + 2);
    }

    public function test_array_has_key(): void
    {
        $arr = ['name' => 'John', 'age' => 30];
        $this->assertArrayHasKey('name', $arr);
    }

    public function test_assert_null(): void
    {
        $this->assertNull(null);
    }

    public function test_string_contains(): void
    {
        $this->assertStringContainsString('Hello', 'Hello World');
    }

    public function test_count(): void
    {
        $this->assertCount(3, ['a', 'b', 'c']);
    }
}
```

### Assertions

```php
$this->assertTrue($condition)
$this->assertFalse($condition)
$this->assertEquals($expected, $actual)
$this->assertNull($value)
$this->assertNotNull($value)
$this->assertCount($expected, $array)
$this->assertArrayHasKey($key, $array)
$this->assertStringContainsString($needle, $haystack)
```

### Test Conventions

- Tests extend `Tests\TestCase`
- Test methods prefixed with `test_`
- Run via `php zen test`

---

## Configuration

### Accessing Config

```php
config('app.name')                // Get value
config('app.name', 'Default')     // With default
config('database.default')        // Default connection
config('app.providers')           // Service providers
```

### app.php

```php
<?php

return [
    'name' => 'Zenith Framework',
    'env' => 'development',
    'debug' => true,
    'url' => 'http://localhost',
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'key' => env('APP_KEY', 'base64:' . base64_encode(random_bytes(32))),
    'cipher' => 'AES-256-CBC',
    'providers' => [
        \App\Providers\AppProvider::class,
    ],
];
```

### database.php

```php
<?php

return [
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => dirname(__DIR__) . '/database/database.sqlite',
            'prefix' => '',
        ],
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'zen'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ],
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'zen'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
    ],
];
```

---

## Bootstrap & Entry Point

### Ignition (Application Bootstrap)

```php
// public/index.php
define('ZEN_START', microtime(true));

require_once __DIR__ . '/../boot/Ignition.php';

use Zen\Boot\Ignition;
use Zen\Boot\Engine;
use Zen\Http\Request;
use Zen\Http\Response;
use Zen\Routing\Router;
use Zen\Session\Session;
use Zen\Auth\Auth;

// Bootstrap
$container = Ignition::fire();

// Capture request
$request = Request::capture();

// Start session & auth
Session::start();
Auth::loadFromSession();

// Get router
$router = $container->make(Router::class);

// Handle request
$response = $container->make(Engine::class)->handle($request);

// Send response
$response->send();
```

### RouteLoader

Loads route files with prefixes:

| File | Prefix |
|------|--------|
| `routes/Web.php` | `/` |
| `routes/Api.php` | `/api` |
| `routes/Auth.php` | `/auth` |
| `routes/Ai.php` | `/ai` |

Caches routes in production mode.

### Engine

```php
$engine = new Engine($container, $router);

// Global middleware
$engine->middleware(['csrf']);
$engine->addMiddleware('auth');

// Handle request
$response = $engine->handle($request);
```

---

## Container (DI)

```php
$container = app(Container::class);
```

### Binding

```php
// Bind (new instance each time)
$container->bind(ServiceInterface::class, fn() => new ConcreteService());

// Singleton (shared instance)
$container->singleton(Logger::class, fn() => new FileLogger());

// Instance (bind existing object)
$container->instance('db', $databaseConnection);
```

### Resolving

```php
// Resolve by string
$service = app('service');
$service = app(ServiceInterface::class);

// Resolve typed class (auto-wired)
$controller = $container->make(UserController::class);

// Check if bound
$container->has(ServiceInterface::class);
```

### Auto-Wiring

Constructor dependencies are resolved automatically via reflection:

```php
class UserController
{
    public function __construct(
        private UserService $userService,  // Auto-resolved
        private Logger $logger,            // Auto-resolved
    ) {}
}
```

---

## Quick Reference

### Environment Variables

```
APP_NAME
APP_ENV
APP_DEBUG
APP_URL

DB_CONNECTION
DB_HOST
DB_PORT
DB_DATABASE
DB_USERNAME
DB_PASSWORD

CACHE_DRIVER
SESSION_DRIVER

MAIL_DRIVER
MAIL_FROM_ADDRESS
MAIL_HOST
MAIL_PORT
MAIL_USERNAME
MAIL_PASSWORD

STORAGE_DRIVER
AWS_ACCESS_KEY_ID
AWS_SECRET_ACCESS_KEY
AWS_DEFAULT_REGION
AWS_BUCKET

QUEUE_CONNECTION

AI_API_KEY
```

### Database Drivers

- SQLite (default)
- MySQL
- PostgreSQL

### Cache Drivers

- File (default)
- Array (in-memory)
