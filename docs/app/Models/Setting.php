<?php

declare(strict_types=1);

namespace App\Models;

use Zenith\Database\Model;

class Setting extends Model
{
    protected static string $table = 'settings';
    protected array $fillable = ['key', 'value', 'type'];
    protected array $casts = [];
    protected string $primaryKey = 'id';

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', '=', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'json' => json_decode($setting->value, true),
            'boolean' => (bool) $setting->value,
            'integer' => (int) $setting->value,
            default => $setting->value,
        };
    }

    public static function set(string $key, mixed $value, string $type = 'string'): void
    {
        $setting = static::where('key', '=', $key)->first();
        
        if (!$setting) {
            $setting = new static();
            $setting->key = $key;
        }

        $setting->value = match ($type) {
            'json' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };
        $setting->type = $type;
        $setting->save();
    }

    public static function getMany(array $keys): array
    {
        $result = [];
        
        foreach ($keys as $key) {
            $result[$key] = static::get($key);
        }
        
        return $result;
    }

    public function getValue(): mixed
    {
        return match ($this->type) {
            'json' => json_decode($this->value, true),
            'boolean' => (bool) $this->value,
            'integer' => (int) $this->value,
            default => $this->value,
        };
    }
}
