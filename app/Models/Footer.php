<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Cache;

class Footer extends Model
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
            'columns' => [
                [
                    'id' => 'col1',
                    'components' => [
                        ['id' => 'f1', 'type' => 'logo', 'enabled' => true, 'settings' => []],
                        ['id' => 'f2', 'type' => 'text', 'enabled' => true, 'settings' => ['text' => 'Building modern websites with Laravel.']],
                        ['id' => 'f3', 'type' => 'social', 'enabled' => true, 'settings' => []],
                    ],
                ],
                [
                    'id' => 'col2',
                    'components' => [
                        ['id' => 'f4', 'type' => 'menu', 'enabled' => true, 'settings' => ['menu' => 'primary', 'title' => 'Company']],
                    ],
                ],
                [
                    'id' => 'col3',
                    'components' => [
                        ['id' => 'f5', 'type' => 'contact', 'enabled' => true, 'settings' => [
                            'title' => 'Contact',
                            'email' => 'hello@example.com',
                            'phone' => '',
                            'address' => '',
                        ]],
                    ],
                ],
                [
                    'id' => 'col4',
                    'components' => [
                        ['id' => 'f6', 'type' => 'newsletter', 'enabled' => true, 'settings' => ['title' => 'Newsletter']],
                    ],
                ],
            ],
            'bottom' => [
                ['id' => 'f7', 'type' => 'copyright', 'enabled' => true, 'settings' => ['text' => '© {year} {site}']],
            ],
            'style' => [
                'background' => 'var(--color-surface)',
                'text_color' => 'var(--color-text)',
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
        Cache::forget('cms.default_footer');
        Cache::forget('cms.footers.all');
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetCache());
        static::deleted(fn () => static::forgetCache());
    }
}
