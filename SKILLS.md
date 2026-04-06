# Zen Framework Skills Reference

Complete reference for developers working with Zen Framework.

## Table of Contents

1. [CLI Commands](#cli-commands)
2. [Helper Functions](#helper-functions)
3. [Validation Rules](#validation-rules)
4. [Configuration](#configuration)
5. [Model Methods](#model-methods)
6. [Builder Methods](#builder-methods)
7. [Cache Methods](#cache-methods)
8. [Session Methods](#session-methods)
9. [Auth Methods](#auth-methods)
10. [AI Methods](#ai-methods)

---

## CLI Commands

### Make Commands

| Command | Description | Options |
|---------|-------------|---------|
| `make:model` | Create Model | `--migration`, `--factory` |
| `make:controller` | Create Controller | `--resource`, `--api`, `--auth` |
| `make:middleware` | Create Middleware | |
| `make:migration` | Create Migration | `--create`, `--table` |
| `make:seeder` | Create Seeder | `--model` |
| `make:factory` | Create Factory | `--model` |
| `make:service` | Create Service | |
| `make:provider` | Create Service Provider | |
| `make:page` | Create Page | |
| `make:component` | Create Component | `--inline`, `--view` |
| `make:layout` | Create Layout | `--type=app,guest,blank,auth,dashboard,custom` |
| `make:request` | Create Form Request | |
| `make:job` | Create Job | `--sync`, `--queue` |
| `make:event` | Create Event | |
| `make:listener` | Create Listener | |

### Remove Commands

| Command | Description |
|---------|-------------|
| `remove:model <name>` | Delete Model |
| `remove:controller <name>` | Delete Controller |
| `remove:middleware <name>` | Delete Middleware |
| `remove:migration <name>` | Delete Migration |
| `remove:seeder <name>` | Delete Seeder |
| `remove:factory <name>` | Delete Factory |
| `remove:service <name>` | Delete Service |
| `remove:provider <name>` | Delete Provider |
| `remove:page <name>` | Delete Page |
| `remove:component <name>` | Delete Component |
| `remove:layout <name>` | Delete Layout |
| `remove:request <name>` | Delete Request |

### Rename Commands

| Command | Description |
|---------|-------------|
| `rename:model <name> --to=<NewName>` | Rename Model |
| `rename:controller <name> --to=<NewName>` | Rename Controller |
| `rename:service <name> --to=<NewName>` | Rename Service |

### Database Commands

| Command | Description |
|---------|-------------|
| `migrate` | Run migrations |
| `migrate --rollback` | Rollback last batch |
| `migrate --reset` | Rollback all |
| `migrate --fresh` | Drop all and recreate |
| `db:seed` | Run all seeders |
| `db:seed <name>` | Run specific seeder |

### Other Commands

| Command | Description | Options |
|---------|-------------|---------|
| `route:list` | List routes | `--compact`, `--json` |
| `cache:clear` | Clear cache | `--all` |
| `serve` | Start server | `--host`, `--port` |
| `lint` | Syntax check | `--no-imports` |
| `test` | Run tests | |
| `class:resolve` | Fix imports | `--fix` |

---

## Helper Functions

### Core

```php
app()                    // Get container instance
app('class')            // Resolve from container
config('key')           // Get config value
config('key', 'default') // Get with default
env('KEY', 'default')   // Get env value
```

### Request/Response

```php
request()               // Get Request instance
request('key')          // Get input
request()->all()        // Get all input
request()->header('X-Token') // Get header
request()->isMethod('GET')   // Check method
request()->isAjax()     // Check AJAX
request()->expectsJson() // Check JSON accept
```

```php
response()             // Create response
response('content')    // With content
response()->json($data) // JSON response
response()->withHeader('X-Custom', 'value')
response()->withStatus(201)
redirect('/path')      // Redirect response
```

### Views

```php
view('path', ['data' => $value])  // Render view
```

### Routing

```php
route('name')           // Generate URL by name
route('name', ['id' => 1]) // With parameters
```

### CSRF

```php
csrf_token()           // Get CSRF token
csrf_field()           // Generate CSRF field
```

### Assets

```php
asset('css/style.css')  // Generate asset URL
```

### Debug

```php
dd($value)              // Dump and die
abort(404)              // Throw HTTP error
abort(403, 'Forbidden')
```

### Session

```php
session()               // Get Session instance
session('key')          // Get session value
session('key', 'default') // Get with default
```

### Auth

```php
auth()                 // Get Auth instance
auth()->user()         // Get current user
auth()->check()        // Is authenticated
auth()->id()           // Get user ID
```

---

## Validation Rules

| Rule | Example | Description |
|------|---------|-------------|
| `required` | `name => 'required'` | Field must not be empty |
| `email` | `email => 'email'` | Valid email address |
| `min:X` | `name => 'min:2'` | Minimum X characters |
| `max:X` | `name => 'max:100'` | Maximum X characters |
| `numeric` | `age => 'numeric'` | Must be numeric |
| `integer` | `age => 'integer'` | Must be integer |
| `string` | `name => 'string'` | Must be string |
| `array` | `tags => 'array'` | Must be array |
| `boolean` | `active => 'boolean'` | Must be boolean |
| `url` | `website => 'url'` | Valid URL |
| `ip` | `ip => 'ip'` | Valid IP address |
| `alpha` | `name => 'alpha'` | Letters only |
| `alphaNum` | `username => 'alphaNum'` | Letters and numbers |
| `date` | `birthdate => 'date'` | Valid date |
| `confirmed` | `password => 'confirmed'` | Must match field_confirmation |
| `unique:table,column` | `email => 'unique:users,email'` | Unique in database |
| `exists:table,column` | `role_id => 'exists:roles,id'` | Exists in database |
| `in:a,b,c` | `status => 'in:active,inactive'` | Must be in list |
| `notIn:a,b,c` | `role => 'notIn:admin'` | Must not be in list |
| `minValue:X` | `age => 'minValue:18'` | Minimum numeric value |
| `maxValue:X` | `age => 'maxValue:100'` | Maximum numeric value |
| `between:X,Y` | `age => 'between:18,100'` | Between X and Y |
| `match:field` | `password => 'match:password_confirmation'` | Must match another field |

### Custom Error Messages

```php
$validator = Validator::make($data, $rules, [
    'email.required' => 'Please enter your email',
    'email.email' => 'Please enter a valid email',
    'name.min' => 'Name must be at least :param characters',
]);
```

---

## Configuration

### app.php

```php
return [
    'name' => 'Zen Framework',
    'env' => 'development',
    'debug' => true,
    'url' => 'http://localhost',
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
];
```

### database.php

```php
return [
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => 'database/database.sqlite',
            'prefix' => '',
        ],
        'mysql' => [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'zen',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ],
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => '127.0.0.1',
            'port' => '5432',
            'database' => 'zen',
            'username' => 'postgres',
            'password' => '',
            'charset' => 'utf8',
            'prefix' => '',
        ],
    ],
];
```

---

## Model Methods

### Static Methods

```php
User::query()              // Create builder
User::all()                // Get all
User::find(1)              // Find by ID
User::findOrFail(1)        // Find or throw exception
User::where('col', 'val') // Where clause
User::create($data)        // Create and save
User::firstWhere('col', 'val') // First matching
User::count()              // Count records
User::paginate(15)         // Paginate results
```

### Instance Methods

```php
$user->save()              // Save model
$user->delete()            // Delete model
$user->fresh()             // Refresh from DB
$user->toArray()           // Convert to array
$user->toJson()            // Convert to JSON
$user->getAttributes()     // Get all attributes
$user->fill($data)         // Fill attributes
$user->getDirty()          // Get changed attributes
```

### Query Builder Methods

```php
// Where
->where('col', 'value')
->where('col', '>=', 'value')
->whereIn('col', [values])
->whereNull('col')
->whereNotNull('col')
->orWhere('col', 'value')

// Joins
->join('table', 't1.col', '=', 't2.col')
->leftJoin('table', 'cond')
->rightJoin('table', 'cond')

// Ordering
->orderBy('col')
->orderBy('col', 'DESC')
->latest('created_at')
->oldest('created_at')

// Limiting
->limit(10)
->offset(20)
->forPage($page, $perPage)

// Aggregates
->count()
->exists()
->max('col')
->min('col')
->avg('col')
->sum('col')

// Insert/Update/Delete
->insert($data)
->update($data)
->delete()

// Results
->get()
->first()
->find($id)
->pluck('col')
->paginate($perPage)
->chunk($count, $callback)
```

---

## Cache Methods

```php
Cache::get('key')                  // Get value
Cache::get('key', 'default')       // Get with default
Cache::has('key')                  // Check exists
Cache::put('key', 'value', 60)     // Set with TTL
Cache::forever('key', 'value')     // Set permanently
Cache::forget('key')               // Remove
Cache::flush()                     // Clear all
Cache::remember('key', 60, fn() => value) // Remember
Cache::rememberForever('key', fn() => value)
Cache::increment('key', 1)        // Increment
Cache::decrement('key', 1)        // Decrement
```

---

## Session Methods

```php
Session::start()                    // Start session
Session::put('key', 'value')       // Set value
Session::get('key')               // Get value
Session::get('key', 'default')    // Get with default
Session::has('key')               // Check exists
Session::forget('key')            // Remove
Session::flush()                  // Clear all
Session::flash('key', 'value')    // Flash value
Session::regenerate()             // Regenerate ID
Session::invalidate()             // Destroy session
Session::all()                    // Get all
Session::token()                  // Get CSRF token
```

---

## Auth Methods

```php
Auth::user()              // Get current user
Auth::id()                // Get user ID
Auth::check()             // Is authenticated
Auth::guest()             // Is guest
Auth::login($user)        // Login user
Auth::loginUsingId(1)    // Login by ID
Auth::logout()            // Logout
Auth::attempt($credentials) // Attempt login
Auth::once($credentials)  // Login once
Auth::guard('web')        // Set guard
Auth::getGuard()          // Get guard
```

---

## AI Methods

```php
// Create chat instance
AI::chat()

// Configure
->provider('openai')      // openai, anthropic, ollama
->model('gpt-4')         // Model name
->temperature(0.7)       // Temperature
->maxTokens(2048)        // Max tokens
->apiKey('key')          // API key
->baseUrl('url')         // Custom endpoint

// Messages
->withSystemMessage('You are helpful')
->withUserMessage('Hello')
->withAssistantMessage('Hi there')

// Send
->send()                  // Get response string
->json()                  // Get response as array
->stream(fn($chunk) => ...) // Stream response
```

### AI Providers

```php
// OpenAI
AI::chat()
    ->provider('openai')
    ->model('gpt-4')
    ->send();

// Anthropic
AI::chat()
    ->provider('anthropic')
    ->model('claude-3')
    ->send();

// Ollama (local)
AI::chat()
    ->provider('ollama')
    ->model('llama2')
    ->send();
```

---

## Route Methods

```php
$router->get($uri, $handler)
$router->post($uri, $handler)
$router->put($uri, $handler)
$router->patch($uri, $handler)
$router->delete($uri, $handler)
$router->any($uri, $handler)

$router->group($prefix, callback)
$router->url('name', ['param' => 'value'])
```

---

## Response Methods

```php
response()                 // Create response
response('content')        // With content
response()->json($data)    // JSON
response()->redirect($url) // Redirect
response()->withHeader('K', 'V')
response()->withStatus(201)
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

AI_API_KEY
```

### Default Paths

```
app/              → Application code
boot/             → Bootstrap
config/           → Configuration
core/             → Framework core
database/         → Database files
database/migrations/
database/seeders/
database/factories/
public/           → Web root
routes/           → Routes
views/            → View templates
views/layouts/
views/components/
views/pages/
```

### Database Drivers

- SQLite (default)
- MySQL
- PostgreSQL

### Cache Drivers

- File (default)
- Array (in-memory)
