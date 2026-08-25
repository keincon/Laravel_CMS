<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Cache;

class Header extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'content',
        'draft_content',
        'settings',
        'status',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'draft_content' => 'array',
            'settings' => 'array',
            'is_default' => 'boolean',
        ];
    }

    public function revisions(): MorphMany
    {
        return $this->morphMany(ContentRevision::class, 'revisable')->latest();
    }

    public function publishedStructure(): array
    {
        return $this->content ?: static::defaultStructure();
    }

    public function editableStructure(): array
    {
        return $this->draft_content ?: $this->publishedStructure();
    }

    public static function defaultStructure(): array
    {
        return [
            'rows' => [
                [
                    'id' => 'top_bar',
                    'label' => 'Top Bar',
                    'enabled' => false,
                    'components' => [
                        ['id' => 'c1', 'type' => 'text', 'enabled' => true, 'settings' => ['text' => 'Welcome']],
                        ['id' => 'c2', 'type' => 'social', 'enabled' => true, 'settings' => []],
                    ],
                ],
                [
                    'id' => 'main',
                    'label' => 'Main Header',
                    'enabled' => true,
                    'components' => [
                        ['id' => 'c3', 'type' => 'logo', 'enabled' => true, 'settings' => []],
                        ['id' => 'c4', 'type' => 'navigation', 'enabled' => true, 'settings' => ['menu' => 'primary']],
                        ['id' => 'c5', 'type' => 'search', 'enabled' => true, 'settings' => []],
                        ['id' => 'c6', 'type' => 'button', 'enabled' => true, 'settings' => ['label' => 'Contact', 'url' => '/contact']],
                    ],
                ],
                [
                    'id' => 'nav_bar',
                    'label' => 'Navigation Bar',
                    'enabled' => false,
                    'components' => [
                        ['id' => 'c7', 'type' => 'navigation', 'enabled' => true, 'settings' => ['menu' => 'primary']],
                    ],
                ],
            ],
            'style' => [
                'background' => 'var(--color-surface)',
                'text_color' => 'var(--color-text)',
                'sticky' => true,
                'transparent' => false,
                'height' => '72px',
                'position' => 'static',
            ],
            'responsive' => [
                'desktop' => ['logo', 'navigation', 'search', 'button'],
                'tablet' => ['logo', 'navigation', 'button'],
                'mobile' => ['logo', 'menu_toggle'],
            ],
            'visibility' => [
                'show_on' => ['entire_website'],
                'hide_on' => ['login', 'admin', 'setup'],
            ],
            'assignment' => [
                'scope' => 'entire_website',
            ],
        ];
    }

    public static function forgetCache(): void
    {
        Cache::forget('cms.default_header');
        Cache::forget('cms.headers.all');
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetCache());
        static::deleted(fn () => static::forgetCache());
    }
}
