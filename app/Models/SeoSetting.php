<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class SeoSetting extends Model
{
    protected $fillable = [
        'seo_title',
        'meta_description',
        'keywords',
        'canonical_url',
        'robots',
        'default_image_id',
        'og_title',
        'og_description',
        'og_type',
        'og_image_id',
        'og_site_name',
        'og_locale',
        'twitter_card',
        'sitemap_enabled',
        'permalink_structure',
        'organization_name',
        'organization_logo_url',
    ];

    protected function casts(): array
    {
        return [
            'sitemap_enabled' => 'boolean',
        ];
    }

    public function defaultImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'default_image_id');
    }

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'og_image_id');
    }

    public static function current(): self
    {
        return Cache::remember('seo_settings.current', 3600, function () {
            return static::query()->first() ?? static::query()->create([]);
        });
    }

    public static function forgetCache(): void
    {
        Cache::forget('seo_settings.current');
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetCache());
        static::deleted(fn () => static::forgetCache());
    }
}
