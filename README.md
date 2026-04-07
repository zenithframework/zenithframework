# Zen Framework v2.0

A clean, modern, backend-first PHP framework with built-in dynamic UI system, strict routing architecture, and enterprise-grade performance and security.

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
- **Built-in UI System** - Components, Pages, and Layouts
- **HTMX-Style Updates** - Server-side rendering with dynamic DOM updates
- **ORM System** - Model, QueryBuilder, and Builder
- **Session & Auth** - Complete authentication system
- **Validation** - 28+ built-in validation rules
- **Cache System** - File and Array drivers
- **AI Integration** - OpenAI, Anthropic, and Ollama support

### CLI Tools
- **40+ Commands** - make, remove, rename, migrate, seed, serve, test, lint

## Requirements

- PHP 8.4+
- PDO Extension

## Quick Start

```bash
# Create new project
composer create-project zanith/framework my-project
cd my-project

# Run migrations
php zen migrate

# Start development server
php zen serve
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
APP_NAME=Zen Framework
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

CACHE_DRIVER=file
AI_API_KEY=your-api-key
```

## Documentation

- [GUIDE.md](./GUIDE.md) - Full tutorial
- [COMPARE.md](./COMPARE.md) - Comparison with other frameworks
- [SKILLS.md](./SKILLS.md) - CLI and API reference
- [AGENTS.md](./AGENTS.md) - AI agent instructions

## License

MIT License - see [LICENSE](LICENSE)
