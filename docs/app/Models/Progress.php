<?php

declare(strict_types=1);

namespace App\Models;

use Zenith\Database\Model;

class Progress extends Model
{
    protected static string $table = 'progress';
    protected array $fillable = [
        'user_id', 'lesson_id', 'is_completed', 'time_spent_seconds', 'completed_at'
    ];
    protected array $casts = [
        'user_id' => 'int',
        'lesson_id' => 'int',
        'is_completed' => 'bool',
        'time_spent_seconds' => 'int',
    ];
    protected string $primaryKey = 'id';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    public static function markComplete(int $userId, int $lessonId): void
    {
        $progress = static::where('user_id', '=', $userId)
            ->where('lesson_id', '=', $lessonId)
            ->first();
        
        if (!$progress) {
            $progress = new static();
            $progress->user_id = $userId;
            $progress->lesson_id = $lessonId;
        }
        
        $progress->is_completed = true;
        $progress->completed_at = date('Y-m-d H:i:s');
        $progress->save();
    }

    public static function getUserProgress(int $userId): array
    {
        return static::where('user_id', '=', $userId)->get();
    }

    public static function getCompletedCount(int $userId): int
    {
        return static::where('user_id', '=', $userId)
            ->where('is_completed', '=', 1)
            ->count();
    }

    public function isCompleted(): bool
    {
        return (bool) $this->is_completed;
    }

    public function getTimeSpentFormatted(): string
    {
        $hours = floor($this->time_spent_seconds / 3600);
        $minutes = floor(($this->time_spent_seconds % 3600) / 60);
        
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        
        return "{$minutes}m";
    }
}
