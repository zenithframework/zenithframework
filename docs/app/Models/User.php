<?php

declare(strict_types=1);

namespace App\Models;

use Zenith\Database\Model;

class User extends Model
{
    protected static string $table = 'users';
    protected array $fillable = [
        'name', 'email', 'password', 'role', 'avatar', 'bio', 'phone', 'is_active', 'remember_token'
    ];
    protected array $hidden = ['password', 'remember_token'];
    protected array $casts = [
        'is_active' => 'bool',
    ];
    protected string $primaryKey = 'id';
    protected bool $timestamps = true;

    public function documents()
    {
        return $this->hasMany(Document::class, 'author_id');
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'teacher_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'user_id');
    }

    public function progress()
    {
        return $this->hasMany(Progress::class, 'user_id');
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class, 'user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function enrolledCourses(): array
    {
        return Enrollment::where('user_id', '=', $this->id)->get();
    }

    public function completedLessonsCount(): int
    {
        return Progress::where('user_id', '=', $this->id)
            ->where('is_completed', '=', 1)
            ->count();
    }
}
