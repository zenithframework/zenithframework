<?php

declare(strict_types=1);

namespace App\Models;

use Zenith\Database\Model;

class Course extends Model
{
    protected static string $table = 'courses';
    protected array $fillable = [
        'title', 'slug', 'description', 'objectives', 'teacher_id', 
        'category_id', 'thumbnail', 'level', 'duration_hours', 
        'price', 'is_published', 'enrollment_count'
    ];
    protected array $casts = [
        'is_published' => 'bool',
        'teacher_id' => 'int',
        'category_id' => 'int',
        'duration_hours' => 'int',
        'price' => 'float',
        'enrollment_count' => 'int',
    ];
    protected string $primaryKey = 'id';

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class, 'course_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'course_id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'course_id');
    }

    public static function findBySlug(string $slug): ?static
    {
        return static::where('slug', '=', $slug)->first();
    }

    public static function getPublished(): array
    {
        return static::where('is_published', '=', 1)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getLessonsCount(): int
    {
        return Lesson::where('course_id', '=', $this->id)->count();
    }

    public function getEnrolledCount(): int
    {
        return Enrollment::where('course_id', '=', $this->id)
            ->where('status', '=', 'active')
            ->count();
    }

    public function isEnrolled(int $userId): bool
    {
        return Enrollment::where('course_id', '=', $this->id)
            ->where('user_id', '=', $userId)
            ->count() > 0;
    }
}
