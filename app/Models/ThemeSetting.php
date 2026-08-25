<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ThemeSetting extends Model
{
    protected $fillable = [
        'theme',
        'primary_color',
        'secondary_color',
        'accent_color',
        'success_color',
        'warning_color',
        'danger_color',
        'info_color',
        'background_color',
        'surface_color',
        'text_color',
        'color_mode',
    ];

    public static function defaults(): array
    {
        return [
            'theme' => 'default',
            'primary_color' => '#2563EB',
            'secondary_color' => '#64748B',
            'accent_color' => '#7C3AED',
            'success_color' => '#16A34A',
            'warning_color' => '#D97706',
            'danger_color' => '#DC2626',
            'info_color' => '#0891B2',
            'background_color' => '#F8FAFC',
            'surface_color' => '#FFFFFF',
            'text_color' => '#0F172A',
            'color_mode' => 'system',
        ];
    }

    public static function current(): self
    {
        $cached = Cache::get('theme_settings.current');
        if ($cached instanceof self) {
            return $cached;
        }

        Cache::forget('theme_settings.current');

        return Cache::remember('theme_settings.current', 3600, function () {
            return static::query()->first() ?? static::query()->create(static::defaults());
        });
    }

    public static function forgetCache(): void
    {
        Cache::forget('theme_settings.current');
        Cache::forget('theme.css_variables');
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetCache());
        static::deleted(fn () => static::forgetCache());
    }
}
