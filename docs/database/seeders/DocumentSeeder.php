<?php

declare(strict_types=1);

use App\Models\Document;

class DocumentSeeder
{
    public function run(): void
    {
        Document::create([
            'title' => 'Introduction to Zenith Framework',
            'slug' => 'introduction',
            'content' => $this->getIntroductionContent(),
            'category_id' => 1,
            'author_id' => 1,
            'version' => '1.0',
            'tags' => json_encode(['introduction', 'overview', 'getting-started']),
            'status' => 'published',
            'sort_order' => 1,
        ]);

        Document::create([
            'title' => 'Installation & Setup',
            'slug' => 'installation-setup',
            'content' => $this->getInstallationContent(),
            'category_id' => 1,
            'author_id' => 1,
            'version' => '1.0',
            'tags' => json_encode(['installation', 'setup', 'getting-started']),
            'status' => 'published',
            'sort_order' => 2,
        ]);

        Document::create([
            'title' => 'Routing Basics',
            'slug' => 'routing-basics',
            'content' => $this->getRoutingContent(),
            'category_id' => 2,
            'author_id' => 2,
            'version' => '1.0',
            'tags' => json_encode(['routing', 'routes', 'basics']),
            'status' => 'published',
            'sort_order' => 1,
        ]);

        Document::create([
            'title' => 'Database Configuration',
            'slug' => 'database-configuration',
            'content' => $this->getDatabaseContent(),
            'category_id' => 3,
            'author_id' => 2,
            'version' => '1.0',
            'tags' => json_encode(['database', 'configuration', 'orm']),
            'status' => 'published',
            'sort_order' => 1,
        ]);
    }

    private function getIntroductionContent(): string
    {
        return <<<'MARKDOWN'
# Welcome to Zenith Framework

Zenith is a clean, modern, backend-first PHP framework built for developers who value simplicity and performance.

## Why Zenith?

- **PHP 8.5+** - Leverages the latest PHP features
- **Strict Routing** - Separate route files for different concerns
- **Built-in UI System** - Components, Pages, and Layouts
- **ORM System** - QueryBuilder with ActiveRecord pattern
- **CLI Tools** - 118+ commands for rapid development
- **Enterprise-Grade** - Security and performance built-in

## Key Features

### 🚀 Fast Development
Get up and running in minutes with our intuitive CLI tools.

### 🔒 Secure by Default
Built-in CSRF protection, rate limiting, and security headers.

### 📦 Modular Architecture
Organize your code with clear separation of concerns.

### 🎨 Beautiful UI System
Create reusable components with our template engine.

## Quick Start

```bash
# Create a new project
php zen new myapp

# Start the development server
php zen serve

# Visit http://localhost:8000
```

## What's Next?

Continue reading our guides to master Zenith Framework:

1. **Installation & Setup** - Get your environment ready
2. **Routing** - Learn how routing works
3. **Database** - Work with databases and models
4. **Views** - Create beautiful interfaces

Happy coding! 🎉
MARKDOWN;
    }

    private function getInstallationContent(): string
    {
        return <<<'MARKDOWN'
# Installation & Setup

Get your Zenith Framework project up and running.

## Requirements

- PHP 8.5 or higher
- Composer
- SQLite, MySQL, or PostgreSQL

## Creating a New Project

```bash
php zen new myapp
cd myapp
```

## Configuration

Copy the example environment file:

```bash
cp .env.example .env
```

Edit `.env` to configure your database:

```env
APP_NAME=MyApp
APP_ENV=development
APP_DEBUG=true

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

## Database Setup

For SQLite (recommended for development):

```bash
touch database/database.sqlite
php zen migrate
```

## Development Server

Start the built-in development server:

```bash
php zen serve
# Server running at http://localhost:8000
```

## Directory Structure

```
myapp/
├── app/
│   ├── Http/Controllers/
│   ├── Models/
│   ├── Pages/
│   └── Services/
├── boot/
├── config/
├── core/
├── database/
├── routes/
├── views/
└── zen
```

## Next Steps

- Read about [Routing](/docs/routing-basics)
- Learn about [Database & ORM](/docs/database-configuration)
- Explore [CLI Commands](/docs/cli-commands)
MARKDOWN;
    }

    private function getRoutingContent(): string
    {
        return <<<'MARKDOWN'
# Routing Basics

Zenith Framework uses a clean, expressive routing system.

## Route Files

Routes are organized by concern in the `routes/` directory:

- **Web.php** - Public routes (prefix: `/`)
- **Api.php** - API routes (prefix: `/api`)
- **Auth.php** - Authentication routes (prefix: `/auth`)
- **Ai.php** - AI routes (prefix: `/ai`)

## Defining Routes

```php
// routes/Web.php

use App\Http\Controllers\PageController;

$router->get('/', [PageController::class, 'home']);
$router->get('/about', [PageController::class, 'about']);
$router->get('/contact', [PageController::class, 'contact']);
```

## Route Parameters

```php
$router->get('/users/{id}', [UserController::class, 'show']);
$router->get('/posts/{slug}', [PostController::class, 'show']);
$router->get('/users/{id?}', [UserController::class, 'show']); // Optional
```

## Route Groups

```php
$router->group('/admin', function ($router) {
    $router->get('/dashboard', [AdminController::class, 'dashboard']);
    $router->get('/users', [AdminController::class, 'users']);
});
```

## Named Routes

```php
$router->get('/profile', [UserController::class, 'profile'])->name('profile');
```

## Middleware

```php
$router->get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth');
```

## Resource Routes

```php
$router->resource('posts', PostController::class);
```

This creates all CRUD routes automatically.

## Next Steps

- Learn about [Controllers](/docs/controllers)
- Explore [Middleware](/docs/middleware)
- Read about [Authentication](/docs/authentication)
MARKDOWN;
    }

    private function getDatabaseContent(): string
    {
        return <<<'MARKDOWN'
# Database Configuration

Zenith Framework provides a powerful ORM and query builder.

## Configuration

Configure your database in `.env`:

```env
# SQLite
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# MySQL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zenith
DB_USERNAME=root
DB_PASSWORD=
```

## Migrations

Create a migration:

```bash
php zen make:migration create_users_table
```

Run migrations:

```bash
php zen migrate
```

## Models

Create a model:

```bash
php zen make:model User
```

Define your model:

```php
class User extends Model
{
    protected static string $table = 'users';
    protected array $fillable = ['name', 'email', 'password'];
    protected array $hidden = ['password'];
}
```

## Querying

```php
// Get all users
$users = User::all();

// Find by ID
$user = User::find(1);

// Where clause
$users = User::where('active', '=', 1)->get();

// Create
User::create(['name' => 'John', 'email' => 'john@example.com']);
```

## Relationships

```php
class User extends Model
{
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
```

## Next Steps

- Learn about [Migrations](/docs/migrations)
- Explore [Relationships](/docs/relationships)
- Read about [Query Builder](/docs/query-builder)
MARKDOWN;
    }
}
