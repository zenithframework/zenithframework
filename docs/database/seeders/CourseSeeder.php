<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Lesson;

class CourseSeeder
{
    public function run(): void
    {
        // Course 1
        $course1 = Course::create([
            'title' => 'Zenith Framework Fundamentals',
            'slug' => 'zenith-fundamentals',
            'description' => 'Master the basics of Zenith Framework from installation to deployment.',
            'objectives' => 'Understand routing, models, views, controllers, and database operations',
            'teacher_id' => 2,
            'category_id' => 8,
            'level' => 'beginner',
            'duration_hours' => 8,
            'price' => 0,
            'is_published' => true,
        ]);

        // Lessons for Course 1
        Lesson::create([
            'course_id' => $course1->id,
            'title' => 'Welcome to Zenith Framework',
            'content' => 'Introduction to the framework and its philosophy',
            'duration_minutes' => 15,
            'sort_order' => 1,
            'is_preview' => true,
        ]);

        Lesson::create([
            'course_id' => $course1->id,
            'title' => 'Installation & Setup',
            'content' => 'Step-by-step guide to setting up your development environment',
            'duration_minutes' => 30,
            'sort_order' => 2,
            'is_preview' => true,
        ]);

        Lesson::create([
            'course_id' => $course1->id,
            'title' => 'Understanding Routing',
            'content' => 'Learn how routing works in Zenith Framework',
            'duration_minutes' => 45,
            'sort_order' => 3,
            'is_preview' => false,
        ]);

        Lesson::create([
            'course_id' => $course1->id,
            'title' => 'Working with Models',
            'content' => 'Database operations and ORM patterns',
            'duration_minutes' => 60,
            'sort_order' => 4,
            'is_preview' => false,
        ]);

        Lesson::create([
            'course_id' => $course1->id,
            'title' => 'Building Views & Components',
            'content' => 'Creating reusable UI components',
            'duration_minutes' => 45,
            'sort_order' => 5,
            'is_preview' => false,
        ]);

        // Course 2
        $course2 = Course::create([
            'title' => 'Advanced Zenith: APIs & Authentication',
            'slug' => 'advanced-zenith',
            'description' => 'Build production-ready APIs with authentication and security.',
            'objectives' => 'Master API development, authentication, and middleware',
            'teacher_id' => 2,
            'category_id' => 9,
            'level' => 'intermediate',
            'duration_hours' => 12,
            'price' => 0,
            'is_published' => true,
        ]);

        Lesson::create([
            'course_id' => $course2->id,
            'title' => 'API Design Principles',
            'content' => 'RESTful API design patterns and best practices',
            'duration_minutes' => 40,
            'sort_order' => 1,
            'is_preview' => true,
        ]);

        Lesson::create([
            'course_id' => $course2->id,
            'title' => 'Authentication & JWT',
            'content' => 'Implementing secure authentication systems',
            'duration_minutes' => 60,
            'sort_order' => 2,
            'is_preview' => false,
        ]);

        Lesson::create([
            'course_id' => $course2->id,
            'title' => 'Middleware Deep Dive',
            'content' => 'Advanced middleware patterns and custom middleware creation',
            'duration_minutes' => 45,
            'sort_order' => 3,
            'is_preview' => false,
        ]);
    }
}
