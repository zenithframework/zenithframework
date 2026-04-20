<?php

declare(strict_types=1);

namespace App\Models;

use Zenith\Database\Model;

class Tenant extends Model
{
    protected static string $table = 'tenants';
    protected array $fillable = [
        'id',
        'name',
        'slug',
        'domain',
        'subdomain',
        'plan',
        'status',
        'owner_id',
        'settings',
        'subscription_id',
        'trial_ends_at',
        'subscription_ends_at',
        'created_at',
        'updated_at',
    ];
    protected array $hidden = ['settings'];
    protected array $casts = [
        'settings' => 'json',
        'trial_ends_at' => 'string',
        'subscription_ends_at' => 'string',
    ];
    protected string $primaryKey = 'id';

    public static function boot(): void
    {
        parent::boot();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isTrial(): bool
    {
        if (!$this->trial_ends_at) {
            return false;
        }
        return strtotime($this->trial_ends_at) > time();
    }

    public function hasActiveSubscription(): bool
    {
        if (!$this->subscription_ends_at) {
            return false;
        }
        return strtotime($this->subscription_ends_at) > time();
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        $settings = $this->settings ?? [];
        return $settings[$key] ?? $default;
    }

    public function setSetting(string $key, mixed $value): void
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        $this->settings = $settings;
        $this->save();
    }
}
