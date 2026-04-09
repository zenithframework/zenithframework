<?php

declare(strict_types=1);

namespace App\Models;

use Zenith\Database\Model;

class User extends Model
{
    protected static string $table = 'users';
    protected array $fillable = [
        'id',
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'email_verified_at',
        'status',
        'last_login_at',
        'created_at',
        'updated_at',
    ];
    protected array $hidden = ['password'];
    protected array $casts = [
        'email_verified_at' => 'string',
        'last_login_at' => 'string',
    ];
    protected string $primaryKey = 'id';

    public static function boot(): void
    {
        parent::boot();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }
}
