<?php

declare(strict_types=1);

use App\Models\User;

class UserSeeder
{
    public function run(): void
    {
        // Admin user
        User::create([
            'name' => 'Admin',
            'email' => 'admin@zenithframework.com',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'admin',
            'bio' => 'System Administrator',
            'is_active' => true,
        ]);

        // Teacher user
        User::create([
            'name' => 'John Teacher',
            'email' => 'teacher@zenithframework.com',
            'password' => password_hash('teacher123', PASSWORD_DEFAULT),
            'role' => 'teacher',
            'bio' => 'Senior Instructor & Framework Expert',
            'is_active' => true,
        ]);

        // Student user
        User::create([
            'name' => 'Jane Student',
            'email' => 'student@zenithframework.com',
            'password' => password_hash('student123', PASSWORD_DEFAULT),
            'role' => 'student',
            'bio' => 'Learning Zenith Framework',
            'is_active' => true,
        ]);

        // Additional teacher
        User::create([
            'name' => 'Mike Instructor',
            'email' => 'mike@zenithframework.com',
            'password' => password_hash('instructor123', PASSWORD_DEFAULT),
            'role' => 'teacher',
            'bio' => 'Full Stack Developer & Educator',
            'is_active' => true,
        ]);

        // Additional student
        User::create([
            'name' => 'Alex Learner',
            'email' => 'alex@zenithframework.com',
            'password' => password_hash('learner123', PASSWORD_DEFAULT),
            'role' => 'student',
            'bio' => 'Beginner Developer',
            'is_active' => true,
        ]);
    }
}
