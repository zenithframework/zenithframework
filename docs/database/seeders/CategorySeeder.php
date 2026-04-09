<?php

declare(strict_types=1);

use App\Models\Category;

class CategorySeeder
{
    public function run(): void
    {
        // Documentation categories
        Category::create([
            'name' => 'Getting Started',
            'slug' => 'getting-started',
            'description' => 'Introduction to Zenith Framework',
            'icon' => 'rocket',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Category::create([
            'name' => 'Routing',
            'slug' => 'routing',
            'description' => 'Learn about routing in Zenith',
            'icon' => 'route',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        Category::create([
            'name' => 'Database & ORM',
            'slug' => 'database-orm',
            'description' => 'Database operations and Eloquent-style ORM',
            'icon' => 'database',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        Category::create([
            'name' => 'Views & Templates',
            'slug' => 'views-templates',
            'description' => 'Template engine and components',
            'icon' => 'layout',
            'sort_order' => 4,
            'is_active' => true,
        ]);

        Category::create([
            'name' => 'Authentication',
            'slug' => 'authentication',
            'description' => 'User authentication and authorization',
            'icon' => 'shield',
            'sort_order' => 5,
            'is_active' => true,
        ]);

        Category::create([
            'name' => 'Middleware',
            'slug' => 'middleware',
            'description' => 'HTTP middleware system',
            'icon' => 'filter',
            'sort_order' => 6,
            'is_active' => true,
        ]);

        Category::create([
            'name' => 'CLI Commands',
            'slug' => 'cli-commands',
            'description' => 'Console commands and tools',
            'icon' => 'terminal',
            'sort_order' => 7,
            'is_active' => true,
        ]);

        // Course categories
        Category::create([
            'name' => 'Web Development',
            'slug' => 'web-development',
            'description' => 'Full-stack web development courses',
            'icon' => 'globe',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        Category::create([
            'name' => 'API Development',
            'slug' => 'api-development',
            'description' => 'Building RESTful APIs',
            'icon' => 'code',
            'sort_order' => 11,
            'is_active' => true,
        ]);
    }
}
