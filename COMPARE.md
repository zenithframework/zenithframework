# Zen vs Laravel vs Livewire

A comprehensive comparison of Zen Framework with Laravel and Livewire.

## Philosophy

| Aspect | Zen | Laravel | Livewire |
|--------|-----|---------|----------|
| **Design** | Backend-first, minimal | Full-stack, comprehensive | Laravel + Vue/React hybrid |
| **Architecture** | Strict, predictable | Flexible, many ways | Reactive + Server-side |
| **Philosophy** | "Zero garbage code" | "Developer happiness" | "Modern feels classic" |
| **Learning Curve** | Low | Medium | Medium-High |

## Feature Comparison

### Core Framework

| Feature | Zen | Laravel | Livewire |
|---------|-----|---------|----------|
| **Routing** | ✓ Strict separated files | ✓ | ✓ (uses Laravel) |
| **ORM** | ✓ Basic Builder | ✓ Eloquent | ✓ Eloquent |
| **Migrations** | ✓ | ✓ | ✓ |
| **Validation** | ✓ 28+ rules | ✓ 50+ rules | ✓ Laravel validation |
| **Session** | ✓ | ✓ | ✓ |
| **Auth** | ✓ Basic | ✓ Full | ✓ Jetstream |
| **Cache** | ✓ File/Array | ✓ Multiple drivers | ✓ Laravel cache |
| **Queue** | ✗ | ✓ | ✓ |
| **Events** | ✗ | ✓ | ✓ |
| **Broadcasting** | ✗ | ✓ | ✓ |
| **Mail** | ✗ | ✓ | ✓ |
| **Storage** | ✗ | ✓ | ✓ |

### CLI Tools

| Command | Zen | Laravel | Livewire |
|---------|-----|---------|----------|
| **make:model** | ✓ | ✓ | ✓ |
| **make:controller** | ✓ | ✓ | ✓ |
| **make:migration** | ✓ | ✓ | ✓ |
| **make:seeder** | ✓ | ✓ | ✓ |
| **make:factory** | ✓ | ✓ | ✓ |
| **make:middleware** | ✓ | ✓ | ✓ |
| **make:request** | ✓ | ✓ | ✓ |
| **make:job** | ✓ | ✓ | ✓ |
| **make:event** | ✓ | ✓ | ✓ |
| **make:listener** | ✓ | ✓ | ✓ |
| **make:page** | ✓ | ✗ | ✗ |
| **make:component** | ✓ | ✗ | ✓ |
| **make:layout** | ✓ | ✗ | ✗ |
| **remove:*** | ✓ | ✗ | ✗ |
| **rename:*** | ✓ | ✗ | ✗ |
| **route:list** | ✓ | ✓ | ✓ |
| **cache:clear** | ✓ | ✓ | ✓ |
| **db:seed** | ✓ | ✓ | ✓ |
| **serve** | ✓ | ✓ | ✓ |

### UI/Frontend

| Feature | Zen | Laravel | Livewire |
|---------|-----|---------|----------|
| **Template Engine** | Basic PHP | Blade | Blade + Reactive |
| **Components** | ✓ | ✓ | ✓ |
| **Layouts** | ✓ | ✓ | ✓ |
| **Pages** | ✓ | ✗ | ✓ |
| **HTMX Support** | ✓ Built-in | Via package | ✓ |
| **Tailwind** | ✓ CDN | ✓ | ✓ |
| **Alpine.js** | Not built-in | Via package | ✓ |
| **React** | External | External | Via package |
| **Vue** | External | External | Via package |

### Database Support

| Feature | Zen | Laravel | Livewire |
|---------|-----|---------|----------|
| **MySQL** | ✓ | ✓ | ✓ |
| **PostgreSQL** | ✓ | ✓ | ✓ |
| **SQLite** | ✓ | ✓ | ✓ |
| **SQL Server** | ✗ | ✓ | ✓ |

### AI Integration

| Feature | Zen | Laravel | Livewire |
|---------|-----|---------|----------|
| **OpenAI** | ✓ Built-in | Via package | Via package |
| **Anthropic** | ✓ Built-in | Via package | Via package |
| **Ollama** | ✓ Built-in | Via package | Via package |

## Performance

| Metric | Zen | Laravel | Livewire |
|--------|-----|---------|----------|
| **Startup Time** | Fast | Slow | Medium |
| **Memory Usage** | Low | High | Medium |
| **Request Time** | Fast | Medium | Slow |
| **API Response** | Fast | Fast | N/A |

## When to Use Zen

### ✓ Best for:

- **REST APIs** - Clean, minimal API development
- **Microservices** - Lightweight backend services  
- **Simple Web Apps** - Minimal UI with HTMX
- **Learning** - Understand PHP frameworks from scratch
- **Speed** - Fast prototyping, quick development

### ✗ Not Ideal for:

- **Complex Applications** - Need Laravel's features
- **Real-time Apps** - Need Laravel Echo/Broadcasting
- **Email/Queues** - Need Laravel's queue system
- **Large Teams** - Laravel's ecosystem has more resources

## Migration Guide: Laravel to Zen

### Routes

```php
// Laravel
Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);

// Zen (same syntax in routes/Web.php)
$router->get('/users', [UserController::class, 'index']);
$router->post('/users', [UserController::class, 'store']);
```

### Models

```php
// Laravel
class User extends Model {}

// Zen (similar, with some differences)
class User extends Model {
    protected static string $table = 'users';
    protected array $fillable = ['name', 'email'];
}
```

### Controllers

```php
// Laravel
public function index(Request $request) {
    $users = User::all();
    return view('users.index', compact('users'));
}

// Zen (same!)
public function index(Request $request): Response
{
    $users = User::all();
    return view('pages.users', ['users' => $users]);
}
```

### Validation

```php
// Laravel
$request->validate([
    'name' => 'required|string|min:2',
    'email' => 'required|email',
]);

// Zen (similar)
$validator = Validator::make($request->all(), [
    'name' => 'required|string|min:2',
    'email' => 'required|email',
]);

if ($validator->fails()) {
    return response()->json(['errors' => $validator->errors()], 422);
}
```

### Auth

```php
// Laravel
Auth::attempt($credentials);
Auth::user();
Auth::check();

// Zen (same!)
Auth::attempt($credentials);
Auth::user();
Auth::check();
```

### Responses

```php
// Laravel
return response()->json(['user' => $user]);
return redirect('/dashboard');

// Zen (same!)
return response()->json(['user' => $user]);
return redirect('/dashboard');
```

## Code Comparison

### Creating a Resource API

#### Laravel
```bash
php artisan make:controller UserController --resource
php artisan make:model User -mfc
php artisan migrate
```

#### Zen
```bash
php zen make:controller UserController --resource
php zen make:model User --migration --factory
php zen migrate
```

### Livewire Component vs Zen Component

#### Livewire
```php
<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;

class UserList extends Component
{
    public function render()
    {
        return view('livewire.user-list', [
            'users' => User::all()
        ]);
    }
}
```

#### Zen Component
```php
<?php

namespace App\UI\Components;

use Zen\UI\Component;

class UserList extends Component
{
    public function render(): string
    {
        return view('components.user-list', [
            'users' => \App\Models\User::all()
        ]);
    }
}
```

## Conclusion

**Zen Framework** is ideal when you want:
- Minimal, clean code
- Fast performance
- Simple architecture
- Understanding how frameworks work
- Quick API development

**Laravel** is better when you need:
- Full-featured enterprise application
- Large ecosystem
- More documentation/resources
- Queue, Events, Broadcasting
- Team support

**Livewire** is great when you want:
- Reactive UI without JavaScript
- Laravel-first development
- Fast prototyping with Vue/React hybrid
