<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class LayoutSetting extends Model
{
    protected $fillable = [
        'default_header_id',
        'default_footer_id',
        'container_width',
        'content_width',
        'sidebar_width',
        'page_layout',
        'post_layout',
        'sidebar_position',
        'homepage_type',
        'homepage_page_id',
        'posts_page_id',
    ];

    public function defaultHeader(): BelongsTo
    {
        return $this->belongsTo(Header::class, 'default_header_id');
    }

    public function defaultFooter(): BelongsTo
    {
        return $this->belongsTo(Footer::class, 'default_footer_id');
    }

    public function homepagePage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'homepage_page_id');
    }

    public function postsPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'posts_page_id');
    }

    public static function current(): self
    {
        return Cache::remember('cms.layout_settings', 3600, function () {
            return static::query()->with(['defaultHeader', 'defaultFooter'])->first()
                ?? static::query()->create([]);
        });
    }

    public static function forgetCache(): void
    {
        Cache::forget('cms.layout_settings');
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetCache());
        static::deleted(fn () => static::forgetCache());
    }
}
