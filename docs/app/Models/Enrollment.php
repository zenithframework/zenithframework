<?php

declare(strict_types=1);

namespace App\Models;

use Zenith\Database\Model;

class Enrollment extends Model
{
    protected static string $table = 'enrollments';
    protected array $fillable = [
        'user_id', 'course_id', 'status', 'progress_percent', 
        'enrolled_at', 'completed_at'
    ];
    protected array $casts = [
        'user_id' => 'int',
        'course_id' => 'int',
        'progress_percent' => 'int',
    ];
    protected string $primaryKey = 'id';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function markComplete(): void
    {
        $this->status = 'completed';
        $this->progress_percent = 100;
        $this->completed_at = date('Y-m-d H:i:s');
        $this->save();
    }

    public function updateProgress(int $percent): void
    {
        $this->progress_percent = min(100, max(0, $percent));
        if ($this->progress_percent >= 100) {
            $this->markComplete();
        } else {
            $this->save();
        }
    }
}
