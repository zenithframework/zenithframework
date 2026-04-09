<?php

declare(strict_types=1);

namespace App\Models;

use Zenith\Database\Model;

class Lesson extends Model
{
    protected static string $table = 'lessons';
    protected array $fillable = [
        'course_id', 'title', 'content', 'video_url', 
        'duration_minutes', 'sort_order', 'is_preview'
    ];
    protected array $casts = [
        'course_id' => 'int',
        'duration_minutes' => 'int',
        'sort_order' => 'int',
        'is_preview' => 'bool',
    ];
    protected string $primaryKey = 'id';

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function progress()
    {
        return $this->hasMany(Progress::class, 'lesson_id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'lesson_id');
    }

    public static function getByCourse(int $courseId): array
    {
        return static::where('course_id', '=', $courseId)
            ->orderBy('sort_order', 'asc')
            ->get();
    }

    public function isCompletedBy(int $userId): bool
    {
        $progress = Progress::where('lesson_id', '=', $this->id)
            ->where('user_id', '=', $userId)
            ->first();
        
        return $progress && $progress->is_completed;
    }

    public function getDurationFormatted(): string
    {
        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;
        
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        
        return "{$minutes}m";
    }
}
