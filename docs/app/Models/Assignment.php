<?php

declare(strict_types=1);

namespace App\Models;

use Zenith\Database\Model;

class Assignment extends Model
{
    protected static string $table = 'assignments';
    protected array $fillable = [
        'course_id', 'lesson_id', 'title', 'description', 
        'instructions', 'due_date', 'points'
    ];
    protected array $casts = [
        'course_id' => 'int',
        'lesson_id' => 'int',
        'points' => 'int',
    ];
    protected string $primaryKey = 'id';

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class, 'assignment_id');
    }

    public function getSubmission(int $userId): ?Submission
    {
        return Submission::where('assignment_id', '=', $this->id)
            ->where('user_id', '=', $userId)
            ->first();
    }

    public function getSubmissionsCount(): int
    {
        return Submission::where('assignment_id', '=', $this->id)->count();
    }

    public function isDue(): bool
    {
        return $this->due_date && strtotime($this->due_date) < time();
    }

    public function getDueDateFormatted(): string
    {
        if (!$this->due_date) {
            return 'No due date';
        }
        
        return date('M d, Y', strtotime($this->due_date));
    }
}
