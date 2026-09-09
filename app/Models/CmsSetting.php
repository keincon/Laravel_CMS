<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CmsSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'autoload',
    ];

    protected function casts(): array
    {
        return [
            'autoload' => 'boolean',
        ];
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        try {
            // Cache scalars only — Eloquent models break after unserialize (incomplete object).
            $payload = Cache::remember("cms_setting.{$key}", 3600, function () use ($key) {
                $setting = static::query()->where('key', $key)->first();
                if (! $setting) {
                    return null;
                }

                return [
                    'type' => $setting->type,
                    'value' => $setting->value,
                ];
            });

            if (! is_array($payload)) {
                return $default;
            }

            return match ($payload['type'] ?? 'string') {
                'boolean' => filter_var($payload['value'], FILTER_VALIDATE_BOOLEAN),
                'integer' => (int) $payload['value'],
                'json' => json_decode((string) $payload['value'], true),
                default => $payload['value'],
            };
        } catch (\Throwable) {
            return $default;
        }
    }

    public static function setValue(string $key, mixed $value, string $type = 'string'): void
    {
        $stored = match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => is_string($value) ? $value : json_encode($value),
            default => (string) $value,
        };

        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $stored, 'type' => $type]
        );

        Cache::forget("cms_setting.{$key}");
    }
}
