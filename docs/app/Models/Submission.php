<?php

declare(strict_types=1);

namespace App\Models;

use Zenith\Database\Model;

class Submission extends Model
{
    protected static string $table = 'submissions';
    protected array $fillable = [
        'assignment_id', 'user_id', 'content', 'attachment', 
        'score', 'feedback', 'status'
    ];
    protected array $casts = [
        'assignment_id' => 'int',
        'user_id' => 'int',
        'score' => 'int',
    ];
    protected string $primaryKey = 'id';

    public function assignment()
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isGraded(): bool
    {
        return $this->status === 'graded';
    }

    public function grade(int $score, string $feedback): void
    {
        $this->score = $score;
        $this->feedback = $feedback;
        $this->status = 'graded';
        $this->save();
    }

    public function markResubmit(string $feedback): void
    {
        $this->feedback = $feedback;
        $this->status = 'resubmit';
        $this->save();
    }

    public function getSubmittedAtFormatted(): string
    {
        return date('M d, Y h:i A', strtotime($this->submitted_at ?? $this->created_at));
    }
}
