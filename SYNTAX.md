# Zenith Framework - Complete Syntax & Feature Guide

> **World-Class PHP 8.5+ Framework** - Clean, Modern, Backend-First

## Table of Contents

1. [Getting Started](#getting-started)
2. [Routing](#routing)
   - [File-Based Routing](#file-based-routes)
   - [Attribute-Based Routing](#attribute-based-routes)
   - [Route Groups](#route-groups)
   - [Resource Routes](#resource-routes)
3. [Controllers](#controllers)
4. [Models & Database](#models--database)
   - [Basic Models](#basic-models)
   - [Relationships](#relationships)
   - [Query Builder](#query-builder)
   - [Soft Deletes](#soft-deletes)
   - [Model Observers](#model-observers)
5. [Validation](#validation)
6. [Server-Sent Events (SSE)](#server-sent-events-sse)
7. [Queue & Jobs](#queue--jobs)
8. [Mail System](#mail-system)
9. [File Storage](#file-storage)
10. [Events & Listeners](#events--listeners)
11. [Authentication](#authentication)
12. [Authorization (Gates & Policies)](#authorization-gates--policies)
13. [Caching](#caching)
14. [Session Management](#session-management)
15. [Middleware](#middleware)
16. [Service Providers](#service-providers)
17. [Dependency Injection](#dependency-injection)
18. [Facades](#facades)
19. [Logging](#logging)
20. [AI Integration](#ai-integration)
21. [CLI Commands](#cli-commands)
22. [Testing](#testing)
23. [Configuration](#configuration)

---

## Getting Started

### Installation

```bash
git clone <repository-url>
cd zen
composer install
cp .env.example .env
php zen serve
```

### Quick Start

```php
// routes/Web.php
$router->get('/', function() {
    return view('welcome');
});
```

---

## Routing

### File-Based Routes

Define routes in separate files organized by context:

```php
// routes/Web.php - Public routes (prefix: /)
$router->get('/', function() {
    return view('welcome');
});

$router->get('/users', [UserController::class, 'index']);
$router->post('/users', [UserController::class, 'store']);

// routes/Api.php - API routes (prefix: /api)
$router->get('/users', [Api\UserController::class, 'index']);

// routes/Auth.php - Auth routes (prefix: /auth)
$router->post('/login', [AuthController::class, 'login']);

// routes/Ai.php - AI routes (prefix: /ai)
$router->post('/chat', [AiController::class, 'chat']);
```

### Attribute-Based Routes

Use PHP 8.5 attributes for cleaner, inline route definitions:

```php
<?php

namespace App\Http\Controllers;

use Zen\Routing\Attributes\{Get, Post, Put, Delete, Prefix, Middleware};
use Zen\Http\Request;
use Zen\Http\Response;

#[Prefix('/users')]
#[Middleware(['auth'])]
class UserController
{
    #[Get('/', name: 'users.index')]
    public function index(): Response
    {
        $users = User::paginate(15);
        return view('users.index', compact('users'));
    }

    #[Get('/{id}', name: 'users.show')]
    public function show(int $id): Response
    {
        $user = User::findOrFail($id);
        return view('users.show', compact('user'));
    }

    #[Post('/', middleware: ['csrf', 'verified'])]
    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
        ]);

        $user = User::create($validated);
        return redirect()->route('users.show', ['id' => $user->id]);
    }

    #[Put('/{id}')]
    public function update(Request $request, int $id): Response
    {
        $user = User::findOrFail($id);
        $user->update($request->validated());
        
        return redirect()->back()->with('success', 'User updated');
    }

    #[Delete('/{id}')]
    public function destroy(int $id): Response
    {
        User::findOrFail($id)->delete();
        return redirect()->route('users.index');
    }
}
```

### Route Groups

Fluent API for grouping routes with shared configuration:

```php
$router->group(function($group) {
    $group->prefix('admin')
          ->middleware(['auth', 'admin'])
          ->name('admin')
          ->get('/dashboard', [AdminController::class, 'index'])
          ->get('/users', [AdminController::class, 'users'])
          ->post('/users', [AdminController::class, 'createUser']);
});

// Nested groups
$router->group(function($group) {
    $group->prefix('api')
          ->middleware('api')
          ->name('api')
          ->group(function($api) {
              $api->prefix('v1')->group(function($v1) {
                  $v1->apiResource('posts', ApiPostController::class);
                  $v1->apiResource('users', ApiUserController::class);
              });
          });
});
```

### Resource Routes

CRUD routes generated automatically:

```php
// Standard resource (all 7 methods)
$router->resource('posts', PostController::class);

// API resource (no create/edit forms - 5 methods)
$router->apiResource('api/posts', ApiPostController::class);

// Multiple resources
$router->resources([
    'posts' => PostController::class,
    'users' => UserController::class,
    'comments' => CommentController::class,
]);
```

---

## Controllers

### Basic Controller

```php
<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Zen\Http\Request;
use Zen\Http\Response;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'show']);
    }

    public function index(): Response
    {
        $posts = Post::with(['user', 'tags'])
            ->latest()
            ->paginate(15);

        return view('posts.index', compact('posts'));
    }

    public function show(int $id): Response
    {
        $post = Post::with(['user', 'comments.user', 'tags'])
            ->findOrFail($id);
            
        return view('posts.show', compact('post'));
    }

    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $post = $request->user()->posts()->create($validated);

        return redirect()->route('posts.show', ['id' => $post->id])
            ->with('success', 'Post created successfully');
    }

    public function update(Request $request, int $id): Response
    {
        $post = Post::findOrFail($id);
        $post->update($request->validated());

        return redirect()->back()->with('success', 'Post updated');
    }

    public function destroy(int $id): Response
    {
        Post::findOrFail($id)->delete();
        return redirect()->route('posts.index')
            ->with('success', 'Post deleted');
    }

    // JSON response
    public function apiIndex(): Response
    {
        $posts = Post::with(['user', 'tags'])->paginate(15);
        return json($posts);
    }
}
```

---

## Models & Database

### Basic Models

```php
<?php

namespace App\Models;

use Zen\Database\Model;
use Zen\Database\Traits\SoftDeletes;

class User extends Model
{
    use SoftDeletes;

    protected $table = 'users';
    protected $fillable = ['name', 'email', 'password'];
    protected $hidden = ['password'];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Accessor
    protected function getNameAttribute(mixed $value): string
    {
        return ucfirst($value);
    }

    // Mutator
    protected function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = password_hash($value, PASSWORD_DEFAULT);
    }

    // Query Scope
    public function scopeActive($query): mixed
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query): mixed
    {
        return $query->whereNotNull('email_verified_at');
    }
}
```

### Relationships

```php
<?php

namespace App\Models;

use Zen\Database\Model;

class Post extends Model
{
    // BelongsTo
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // HasMany
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    // HasOne
    public function featuredImage(): HasOne
    {
        return $this->hasOne(Image::class, 'post_id');
    }

    // BelongsToMany (Many-to-Many)
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)
                    ->withPivot('created_at')
                    ->withTimestamps();
    }

    // MorphMany (Polymorphic)
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    // HasManyThrough
    public function commentsFromFollowers(): HasManyThrough
    {
        return $this->hasManyThrough(
            Comment::class,
            Follower::class,
            'post_id',
            'user_id',
            'id',
            'user_id'
        );
    }
}
```

### Query Builder

```php
// Basic queries
$users = User::all();
$users = User::where('active', 1)->get();
$user = User::find(1);
$user = User::firstWhere('email', 'test@example.com');

// Advanced queries
$users = User::where('active', 1)
    ->where('verified', 1)
    ->orderBy('name')
    ->limit(10)
    ->get();

$users = User::whereIn('id', [1, 2, 3])->get();
$users = User::whereNull('email_verified_at')->get();
$users = User::latest()->get();

// Aggregates
$count = User::count();
$max = User::max('age');
$avg = User::avg('age');

// Relationships
$posts = Post::with(['user', 'comments', 'tags'])->paginate(15);
$posts = Post::whereHas('comments', function($query) {
    $query->where('approved', true);
})->get();

// FirstOrCreate & UpdateOrCreate
$user = User::firstOrCreate(
    ['email' => 'test@example.com'],
    ['name' => 'John Doe']
);

$user = User::updateOrCreate(
    ['email' => 'test@example.com'],
    ['name' => 'Jane Doe']
);
```

### Soft Deletes

```php
// Delete (soft)
$user = User::find(1);
$user->delete(); // Sets deleted_at timestamp

// Check if trashed
if ($user->trashed()) {
    echo "User is soft-deleted";
}

// Restore
$user->restore();

// Include soft deletes in query
$users = User::withTrashed()->get();

// Only soft deletes
$users = User::onlyTrashed()->get();

// Restore all trashed
User::restoreTrashed();

// Force delete (permanent)
$user->forceDelete();
```

### Model Observers

Hook into model lifecycle events:

```php
<?php

namespace App\Observers;

use App\Models\Post;
use Zen\Database\Observer;

class PostObserver extends Observer
{
    public function creating(Post $post): void
    {
        $post->slug = str($post->title)->slug();
    }

    public function created(Post $post): void
    {
        event(new PostCreated($post));
    }

    public function updating(Post $post): void
    {
        $post->updated_by = auth()->id();
    }

    public function updated(Post $post): void
    {
        Cache::forget("post:{$post->id}");
    }

    public function deleting(Post $post): void
    {
        $post->comments()->delete();
    }
}

// Register observer in AppServiceProvider
public function boot(): void
{
    Post::observe(PostObserver::class);
}
```

---

## Validation

### Basic Validation

```php
public function store(Request $request): Response
{
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:8|confirmed',
        'status' => 'required|in:draft,published,archived',
        'age' => 'nullable|integer|between:18,100',
        'website' => 'nullable|url',
        'tags' => 'array',
    ]);

    // If validation fails, automatically redirects back with errors
}
```

### Rule Objects (Laravel 13+ Style)

```php
use Zen\Validation\Rule;

$validated = $request->validate([
    'name' => Rule::required()->toString(),
    'email' => Rule::required()->email()->unique('users')->toString(),
    'password' => Rule::required()->min(8)->confirmed()->toString(),
    'age' => Rule::integer()->between(18, 100)->toString(),
    'status' => Rule::required()->in(['active', 'inactive'])->toString(),
    'website' => Rule::nullable()->url()->toString(),
]);
```

### Custom Closure Rule

```php
use Zen\Validation\Rule;

$rule = Rule::when(function($attribute, $value, $fail) {
    if (strtolower($value) === $value) {
        $fail("The {$attribute} must contain uppercase letters.");
    }
});
```

---

## Server-Sent Events (SSE)

Native SSE support for real-time server-to-browser communication:

### Basic SSE Controller

```php
<?php

namespace App\Http\Controllers;

use Zen\Http\Request;
use Zen\Http\SseEvent;
use Zen\Http\SseStream;
use Zen\Http\StreamingResponse;

class ChatController
{
    #[Get('/chat/{roomId}/stream')]
    public function stream(Request $request, int $roomId): StreamingResponse
    {
        $lastEventId = $request->header('Last-Event-ID');

        return new StreamingResponse(
            new SseStream(
                dataProvider: function (?string $lastId) use ($roomId): array {
                    $messages = $this->getNewMessages($roomId, $lastId);

                    return array_map(
                        fn($msg) => new SseEvent(
                            data: [
                                'id' => $msg['id'],
                                'user' => $msg['user'],
                                'message' => $msg['message'],
                            ],
                            event: 'message',
                            id: (string) $msg['id']
                        ),
                        $messages
                    );
                },
                pollInterval: 1,
                heartbeatInterval: 15,
                timeout: 1800
            )
        );
    }

    protected function getNewMessages(int $roomId, ?string $lastId): array
    {
        $query = Message::where('room_id', $roomId);
        if ($lastId) {
            $query->where('id', '>', (int) $lastId);
        }
        return $query->with('user')
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->toArray();
    }
}
```

### Client-Side JavaScript

```javascript
const source = new EventSource('/chat/1/stream');

source.addEventListener('message', (e) => {
    const data = JSON.parse(e.data);
    appendMessage(data.user, data.message);
});

// Browser automatically sends Last-Event-ID on reconnect
```

### Helper Methods

```php
// From data provider
return StreamingResponse::fromDataProvider(
    dataProvider: fn($lastId) => [new SseEvent(data: ['time' => now()])],
    pollInterval: 1,
    heartbeatInterval: 15,
    timeout: 300
);

// From subscription (pubsub)
return StreamingResponse::fromSubscription(
    subscription: $pubSubSubscription,
    heartbeatInterval: 15,
    timeout: 300
);
```

---

## Queue & Jobs

### Creating Jobs

```bash
php zen make:job SendEmail
```

### Job Class

```php
<?php

namespace App\Jobs;

use Zen\Queue\Job;
use App\Models\User;

class SendEmail extends Job
{
    public int $timeout = 120;
    public int $maxAttempts = 3;
    public string $queue = 'emails';

    public function handle(): void
    {
        $user = User::find($this->payload['user_id']);
        Mail::to($user->email)
            ->subject($this->payload['subject'])
            ->send(new WelcomeMail($user));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to send email', [
            'user_id' => $this->payload['user_id'],
            'error' => $exception->getMessage()
        ]);
    }
}
```

### Dispatching Jobs

```php
// Dispatch to queue
SendEmail::dispatch(['user_id' => 1, 'subject' => 'Welcome!']);

// Dispatch immediately (synchronous)
SendEmail::dispatchSync(['user_id' => 1]);

// Dispatch with delay
SendEmail::dispatch(['user_id' => 1])->delay(now()->addMinutes(10));
```

### Running Queue Worker

```bash
# Start worker
php zen queue:work

# Specific queue
php zen queue:work emails

# Max attempts
php zen queue:work --tries=5
```

---

## Mail System

### Creating Mailables

```bash
php zen make:mail WelcomeMail
```

### Mailable Class

```php
<?php

namespace App\Mail;

use Zen\Mail\Mailable;
use App\Models\User;

class WelcomeMail extends Mailable
{
    public function __construct(
        protected User $user
    ) {
    }

    public function build(): static
    {
        return $this->to($this->user->email)
                    ->subject('Welcome to Zenith Framework!')
                    ->view('emails.welcome')
                    ->with([
                        'user' => $this->user,
                        'loginUrl' => url('/login')
                    ]);
    }
}
```

### Sending Emails

```php
// Send immediately
Mail::to($user->email)->send(new WelcomeMail($user));

// Send to multiple recipients
Mail::to([$user1, $user2, $user3])->send(new WelcomeMail($user));

// With CC and BCC
Mail::to($user)
    ->cc($manager)
    ->bcc($admin)
    ->send(new WelcomeMail($user));

// Queue email
Mail::to($user)->queue(new WelcomeMail($user));

// Send later
Mail::to($user)
    ->later(now()->addHours(1), new WelcomeMail($user));
```

---

## File Storage

### Configuration

```php
// config/storage.php
return [
    'default' => env('STORAGE_DISK', 'local'),
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
        ],
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
        ],
    ],
];
```

### Usage

```php
// Upload file
Storage::put('file.jpg', $contents);
Storage::putFile('photos', $file);

// Get file
$contents = Storage::get('file.jpg');

// Check if exists
if (Storage::exists('file.jpg')) {
    // File exists
}

// Delete file
Storage::delete('file.jpg');

// Get URL
$url = Storage::url('file.jpg');

// Temporary URL (S3)
$tempUrl = Storage::temporaryUrl(
    'file.jpg',
    now()->addMinutes(5)
);

// File information
$size = Storage::size('file.jpg');
$lastModified = Storage::lastModified('file.jpg');

// Directories
Storage::makeDirectory('photos/vacation');
Storage::directories('photos');

// Visibility
Storage::setVisibility('file.jpg', 'public');
$visibility = Storage::getVisibility('file.jpg');
```

---

## Events & Listeners

### Defining Events

```php
<?php

namespace App\Events;

use App\Models\Post;

class PostCreated
{
    public function __construct(
        public Post $post
    ) {
    }
}
```

### Defining Listeners

```php
<?php

namespace App\Listeners;

use App\Events\PostCreated;
use App\Notifications\PostPublished;

class NotifyFollowers
{
    public function handle(PostCreated $event): void
    {
        $followers = $event->post->user->followers;
        
        foreach ($followers as $follower) {
            $follower->notify(new PostPublished($event->post));
        }
    }
}
```

### Registering Events

```php
// In AppServiceProvider
protected array $eventListeners = [
    'post.created' => [
        NotifyFollowers::class,
        SendToSocialMedia::class,
    ],
];

// Or dispatch manually
event(new PostCreated($post));
```

---

## Authentication

### Basic Authentication

```php
// Login
if (Auth::attempt(['email' => $email, 'password' => $password])) {
    return redirect('/dashboard');
}

// Check if authenticated
if (Auth::check()) {
    $user = Auth::user();
}

// Logout
Auth::logout();

// Login using ID
Auth::loginUsingId(1);

// Login using model
Auth::login($user);

// Once (no session)
if (Auth::once(['email' => $email, 'password' => $password])) {
    // Single request only
}
```

---

## Authorization (Gates & Policies)

### Gates

```php
// In AppServiceProvider
public function boot(): void
{
    Gate::define('update-post', function ($user, $post) {
        return $user->id === $post->user_id;
    });
}

// Usage
if (Gate::allows('update-post', $post)) {
    // User can update
}

if (Gate::denies('update-post', $post)) {
    abort(403);
}
```

### Policies

```bash
php zen make:policy PostPolicy
```

```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Post;

class PostPolicy
{
    public function view(User $user, Post $post): bool
    {
        return $user->id === $post->user_id || $post->is_published;
    }

    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }
}

// Usage
$this->authorize('update', $post);
$this->authorize('delete', $post);
```

---

## Caching

### Basic Usage

```php
// Store
Cache::put('key', 'value', 3600);

// Retrieve
$value = Cache::get('key', 'default');

// Remember (get or set)
$value = Cache::remember('key', 3600, function() {
    return User::all();
});

// Forever
Cache::forever('key', 'value');

// Forget
Cache::forget('key');

// Clear all
Cache::flush();

// Increment/Decrement
Cache::increment('counter');
Cache::decrement('counter');

// Cache::touch() - extend TTL without re-fetching
Cache::touch('key', 7200);
```

---

## Session Management

```php
// Store
Session::put('key', 'value');

// Retrieve
$value = Session::get('key');

// Flash data (next request only)
Session::flash('message', 'Post saved!');

// Forget
Session::forget('key');

// Clear all
Session::flush();

// Regenerate ID
Session::regenerate();

// CSRF Token
$token = Session::token();
```

---

## Middleware

### Creating Middleware

```bash
php zen make:middleware Auth
```

```php
<?php

namespace App\Http\Middleware;

use Zen\Http\Request;
use Zen\Http\Response;
use Zen\Middleware\MiddlewareInterface;

class Authenticate implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        return $next($request);
    }
}
```

### Applying Middleware

```php
// In routes
$router->get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified']);

// In controller
public function __construct()
{
    $this->middleware('auth')->except(['index', 'show']);
}
```

---

## Service Providers

```php
<?php

namespace App\Providers;

use Zen\Boot\ServiceProvider;
use App\Services\PostService;

class AppServiceProvider extends ServiceProvider
{
    protected array $bindings = [
        PostServiceInterface::class => PostService::class,
    ];

    protected array $singletons = [
        Cache::class,
    ];

    protected array $eventListeners = [
        'post.created' => [NotifyFollowers::class],
    ];

    public function register(): void
    {
        // Bind services
    }

    public function boot(): void
    {
        // Boot services
    }
}
```

---

## Dependency Injection

### Constructor Injection

```php
public function __construct(
    protected PostService $postService,
    protected UserService $userService
) {
}
```

### Method Injection

```php
public function store(
    Request $request,
    PostService $postService
): Response {
    $post = $postService->create($request->validated());
    return json($post);
}
```

### Contextual Binding

```php
public function register(): void
{
    $this->app->when(PhotoController::class)
              ->needs(Filesystem::class)
              ->give(S3Filesystem::class);
}
```

---

## Facades

```php
use Zen\Facades\Cache;
use Zen\Facades\Auth;
use Zen\Facades\Mail;
use Zen\Facades\Event;
use Zen\Facades\Log;
use Zen\Facades\Queue;

// Usage
Cache::put('key', 'value', 3600);
Auth::attempt($credentials);
Mail::to($user)->send(new WelcomeMail());
Event::dispatch(new PostCreated($post));
Log::info('User logged in', ['user_id' => 123]);
Queue::push(new SendEmailJob($user));
```

---

## Logging

```php
Log::emergency('System is down');
Log::alert('Server overloaded');
Log::critical('Database connection lost');
Log::error('Something went wrong', ['error' => $e->getMessage()]);
Log::warning('Low disk space');
Log::notice('New user registered');
Log::info('User logged in', ['user_id' => 123]);
Log::debug('Query executed', ['sql' => $sql]);
```

---

## AI Integration

```php
use Zen\AI\AI;

// Basic chat
$response = AI::chat()
    ->provider('openai')
    ->model('gpt-4')
    ->message('user', 'Hello!')
    ->send();

// With system message
$response = AI::chat()
    ->provider('anthropic')
    ->model('claude-3')
    ->system('You are a helpful assistant')
    ->message('user', 'Explain PHP 8.5')
    ->send();

// Streaming
AI::chat()
    ->provider('ollama')
    ->model('llama3')
    ->stream(function($chunk) {
        echo $chunk;
    })
    ->send();
```

---

## CLI Commands

```bash
# Development
php zen serve
php zen serve --port=8080

# Make Commands
php zen make:model User
php zen make:controller UserController
php zen make:middleware Auth
php zen make:migration create_users_table
php zen make:seeder UserSeeder
php zen make:factory UserFactory
php zen make:service UserService
php zen make:provider AppServiceProvider
php zen make:page Dashboard
php zen make:component Alert
php zen make:layout app
php zen make:request StoreUser
php zen make:job SendEmail
php zen make:event PostCreated
php zen make:listener NotifyFollowers

# Database
php zen migrate
php zen migrate:rollback
php zen migrate:fresh
php zen db:seed

# Routes
php zen route:list
php zen route:test /api/users GET

# Code Quality
php zen lint
php zen test

# Cache
php zen cache:clear
php zen optimize:clear

# Remove
php zen remove:model User
php zen remove:controller UserController

# Rename
php zen rename:model User --to=Customer
```

---

## Testing

```bash
# Run tests
php zen test

# Lint code
php zen lint
php zen lint --no-imports
```

---

## Configuration

All configuration files are in `config/` directory:

- `app.php` - Application settings
- `database.php` - Database connections
- `cache.php` - Cache drivers
- `mail.php` - Mail configuration
- `queue.php` - Queue settings
- `storage.php` - File storage disks
- `security.php` - WAF, firewall, rate limits
- `server.php` - Server configuration

Environment variables in `.env`:

```env
APP_NAME="Zenith Framework"
APP_ENV=development
APP_DEBUG=true

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
MAIL_MAILER=log

STORAGE_DISK=local
```

---

## What Makes Zen World-Class

✅ **Native SSE Support** - Real-time streaming without WebSockets complexity
✅ **PHP 8.5 Attributes** - Clean, inline route definitions
✅ **AI Ready** - Built-in OpenAI, Anthropic, Ollama integration
✅ **HTMX Support** - First-class Actions system
✅ **Modern ORM** - 10 relationship types, observers, soft deletes
✅ **Queue System** - Sync, Database, Redis drivers
✅ **Mail System** - SMTP, Sendmail, Log drivers
✅ **File Storage** - Local, S3, FTP support
✅ **Event System** - Decoupled architecture
✅ **Security** - WAF, DDoS protection, rate limiting
✅ **40+ CLI Commands** - Complete development toolkit
✅ **Clean Architecture** - Predictable, no magic
✅ **Zero Compilation** - Runs PHP directly

---

**Built with ❤️ for PHP developers who demand excellence**
