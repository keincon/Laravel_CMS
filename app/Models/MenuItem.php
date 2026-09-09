<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    protected $fillable = [
        'menu_id',
        'parent_id',
        'title',
        'url',
        'page_id',
        'sort_order',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function href(): string
    {
        if (filled($this->url)) {
            $url = (string) $this->url;

            return str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '//')
                ? $url
                : url($url);
        }

        $page = $this->relationLoaded('page') ? $this->page : $this->page()->first();
        if ($page) {
            return url('/'.ltrim((string) $page->slug, '/'));
        }

        return '#';
    }
}
