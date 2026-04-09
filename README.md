# Zenith Framework v3.0 Enterprise

> **World-Class PHP 8.5+ Framework** - Clean, Modern, Backend-First

A clean, modern, backend-first PHP framework with built-in dynamic UI system, strict routing architecture, enterprise-grade security, and **native SSE support**.

**Version:** 3.0.0 "Enterprise"  
**PHP Requirement:** 8.5+  
**Release Date:** April 9, 2026  
**Website:** [zenithframework.com](https://zenithframework.com)

## 🌟 Why Zen?

✅ **Native SSE Support** - Real-time streaming without WebSockets complexity  
✅ **PHP 8.5 Attributes** - Clean, inline route definitions  
✅ **AI Ready** - Built-in OpenAI, Anthropic, Ollama integration  
✅ **HTMX Support** - First-class Actions system  
✅ **Modern ORM** - 10 relationship types, observers, soft deletes  
✅ **Queue System** - Sync, Database, Redis drivers  
✅ **Mail System** - SMTP, Sendmail, Log drivers  
✅ **File Storage** - Local, S3, FTP support  
✅ **Task Scheduler** - Cron-like scheduling DSL  
✅ **Authorization** - Gates and Policies  
✅ **Security** - WAF, DDoS protection, rate limiting  
✅ **118 CLI Commands** - Complete development toolkit  
✅ **Clean Architecture** - Predictable, no magic  
✅ **Comprehensive Docs** - Complete syntax and CLI guides

## Features

### Template Engine
- **Layout Inheritance** - `@extends`, `@section`, `@yield`
- **Directives** - `@if`, `@else`, `@foreach`, `@for`, `@while`
- **Components** - `<zen:Button>`, `<zen:Card>` with slots
- **Template Caching** - Compiled templates cached in production

### Performance
- **Multi-Level Cache** - Memory → APCu → Redis
- **Advanced Rate Limiting** - TokenBucket, LeakyBucket, SlidingWindow
- **Server Support** - Swoole, Workerman, RoadRunner adapters
- **Connection Pooling** - Pre-connected database sockets

### Real-time
- **WebSocket** - Full-duplex communication with rooms
- **SSE** - Server-Sent Events support
- **Connection Pooling** - Manage multiple connections

### Clustering
- **Load Balancer** - Round-robin, least connections, weighted, IP hash
- **Health Checker** - Node monitoring
- **Failover** - Automatic failover
- **Service Discovery** - Dynamic registration

### Security (Enterprise-Grade)
- **WAF** - Rule-based web application firewall
- **DDoS Protection** - Traffic analysis, JS challenge, CAPTCHA
- **IP Blocking** - Auto-ban, CIDR, geo-blocking, ASN filtering
- **Rate Limiting** - Per-user, per-IP, quota management
- **CSRF Protection** - Token-based form protection
- **Encryption** - AES-256-GCM, HMAC support
- **Two-Factor Auth** - TOTP (Google Authenticator)

### Resilience
- **Circuit Breaker** - Failure isolation
- **Retry Policy** - Exponential backoff
- **Timeout Handler** - Request timeouts
- **Bulkhead** - Resource isolation

### Core Framework
- **Clean & Minimal** - No architectural confusion, zero garbage code
- **Backend-First** - API-first design with JSON responses
- **Strict Routing** - Separate route files for Web, API, Auth, and AI
- **Attribute Routing** - PHP 8.5+ attributes for inline route definitions
- **Built-in UI System** - Components, Pages, and Layouts
- **HTMX-Style Updates** - Server-side rendering with dynamic DOM updates
- **ORM System** - Model, QueryBuilder, and Builder
- **Soft Deletes** - Automatic trash and restore functionality
- **Model Observers** - Hook into model lifecycle events
- **Session & Auth** - Complete authentication system
- **Authorization** - Gates and Policies for fine-grained access control
- **Validation** - 40+ built-in validation rules
- **Cache System** - File, Array, APCu, Redis drivers
- **Queue System** - Sync, Database, Redis drivers
- **Mail System** - SMTP, Sendmail, Log drivers
- **File Storage** - Local, S3, FTP drivers
- **Task Scheduler** - Cron-like scheduling with fluent DSL
- **SSE Support** - Native Server-Sent Events for real-time streaming
- **AI Integration** - OpenAI, Anthropic, and Ollama support

### CLI Tools
- **40+ Commands** - make, remove, rename, migrate, seed, serve, test, lint

## Requirements

- PHP 8.5+
- PDO Extension
- OpenSSL Extension
- cURL Extension
- MBString Extension

## Quick Start

```bash
# Create new project
composer create-project zanith/framework my-project
cd my-project

# Copy environment file
cp .env.example .env

# Run migrations
php zen migrate

# Start development server
php zen serve

# Check version
php zen -v
```

## Quick Examples

### Attribute-Based Routing (PHP 8.5+)

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

    #[Post('/', middleware: ['csrf'])]
    public function store(Request $request): Response
    {
        $user = User::create($request->validated());
        return redirect()->route('users.show', ['id' => $user->id]);
    }
}
```

### Server-Sent Events (Real-Time Streaming)

```php
#[Get('/chat/{roomId}/stream')]
public function stream(Request $request, int $roomId): StreamingResponse
{
    return new StreamingResponse(
        new SseStream(
            dataProvider: fn($lastId) => $this->getNewMessages($roomId, $lastId),
            pollInterval: 1,
            heartbeatInterval: 15,
            timeout: 1800
        )
    );
}
```

### Soft Deletes

```php
class User extends Model
{
    use SoftDeletes;
}

// Soft delete
$user->delete();

// Restore
$user->restore();

// Include trashed
$users = User::withTrashed()->get();
```

### Model Observers

```php
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
}

// Register
Post::observe(PostObserver::class);
```

### Authorization (Gates & Policies)

```php
// Define gate
Gate::define('update-post', function ($user, $post) {
    return $user->id === $post->user_id;
});

// Check gate
if (Gate::allows('update-post', $post)) {
    // User can update
}

// In controller
$this->authorizeAbility('update', $post);
```

### Task Scheduler

```php
// In Console Kernel
$scheduler->command('cache:clear')
    ->hourly()
    ->withoutOverlapping();

$scheduler->command('db:backup')
    ->dailyAt('2:00')
    ->onOneServer();
```

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
├── boot/           # Startup system
├── config/         # Configuration
├── core/           # Framework engine
├── database/       # Migrations, Seeders, Factories
├── routes/         # Route definitions
├── views/          # Views and layouts
└── zen            # CLI entry point
```

## Routing

Routes are organized by domain in `routes/`:
- `Web.php` - Public web routes (UI/pages)
- `Api.php` - API routes (JSON)
- `Auth.php` - Authentication routes
- `Ai.php` - AI-related routes

## CLI Commands

### Make Commands
```bash
php zen make:model User
php zen make:controller UserController
php zen make:migration create_users_table
php zen make:seeder UserSeeder
php zen make:factory UserFactory
php zen make:layout dashboard --type=app
php zen make:middleware Auth
php zen make:request StoreUser
php zen make:job ProcessOrder
php zen make:event UserRegistered
php zen make:listener SendWelcomeEmail
```

### Remove Commands
```bash
php zen remove:model User
php zen remove:controller UserController
php zen remove:migration create_users_table
php zen remove:seeder UserSeeder
```

### Database Commands
```bash
php zen migrate
php zen migrate --rollback
php zen migrate --reset
php zen db:seed
```

### Other Commands
```bash
php zen route:list
php zen cache:clear
php zen serve
php zen lint
php zen test
```

## Configuration

Edit `.env` for configuration:

```env
APP_NAME=Zenith Framework
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

CACHE_DRIVER=file
AI_API_KEY=your-api-key
```

## Documentation

- [**Official Website**](https://zenithframework.com) - 🌐 **Complete documentation and guides**
- [**SYNTAX.md**](./SYNTAX.md) - 📖 **Complete syntax guide** - START HERE
- [**CLI_COMMANDS.md**](./CLI_COMMANDS.md) - 🛠️ **118 CLI commands reference**
- [**SSR_GUIDE.md**](./SSR_GUIDE.md) - ⚡ **SSR engine guide**
- [**SSR_ISR_SSE_GUIDE.md**](./SSR_ISR_SSE_GUIDE.md) - 🚀 **SSR, ISR, and SSE comprehensive guide**
- [**GUIDE.md**](./GUIDE.md) - Full tutorial
- [**SECURITY.md**](./SECURITY.md) - Security policy and best practices
- [**SKILLS.md**](./SKILLS.md) - Complete developer reference
- [**AGENTS.md**](./AGENTS.md) - AI agent instructions
- [**CHANGELOG.md**](./CHANGELOG.md) - Version history
- [**CONTRIBUTING.md**](./CONTRIBUTING.md) - Contribution guidelines

## License

MIT License - see [LICENSE](LICENSE)
