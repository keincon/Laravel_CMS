<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class DynamicPageSetting extends Model
{
    protected $fillable = [
        'type',
        'label',
        'title',
        'description',
        'url_path',
        'posts_per_page',
        'layout',
        'sidebar_position',
        'template',
        'header_mode',
        'header_id',
        'footer_mode',
        'footer_id',
        'is_enabled',
        'seo_title_template',
        'seo_description_template',
        'seo_robots',
        'message',
        'button_label',
        'button_url',
        'image_url',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'posts_per_page' => 'integer',
            'settings' => 'array',
        ];
    }

    public function header(): BelongsTo
    {
        return $this->belongsTo(Header::class);
    }

    public function footer(): BelongsTo
    {
        return $this->belongsTo(Footer::class);
    }

    public static function forType(string $type): ?self
    {
        return static::allCached()->firstWhere('type', $type);
    }

    /**
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function allCached()
    {
        $cached = Cache::get('cms.dynamic_page_settings');
        if ($cached instanceof \Illuminate\Support\Collection) {
            return $cached;
        }

        Cache::forget('cms.dynamic_page_settings');

        return Cache::remember('cms.dynamic_page_settings', 3600, function () {
            return static::query()->orderBy('id')->get();
        });
    }

    public static function forgetCache(): void
    {
        Cache::forget('cms.dynamic_page_settings');
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetCache());
        static::deleted(fn () => static::forgetCache());
    }
}
