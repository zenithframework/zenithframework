<?php

declare(strict_types=1);

namespace App\Models;

use Zen\Database\Model;

class Post extends Model
{
    protected static string $table = 'posts';
    protected array $fillable = ['title', 'content', 'user_id'];
    protected array $hidden = [];
    protected array $casts = [
        'id' => 'int',
        'user_id' => 'int',
    ];
}
