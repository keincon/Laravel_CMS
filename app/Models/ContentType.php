<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ContentType extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'singular_label',
        'plural_label',
        'supports',
        'capabilities',
        'hierarchical',
        'has_archive',
        'public',
        'show_in_rest',
        'rest_base',
        'menu_icon',
        'menu_position',
        'is_builtin',
    ];

    protected function casts(): array
    {
        return [
            'supports' => 'array',
            'capabilities' => 'array',
            'hierarchical' => 'boolean',
            'has_archive' => 'boolean',
            'public' => 'boolean',
            'show_in_rest' => 'boolean',
            'is_builtin' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ContentType $type): void {
            if (blank($type->uuid)) {
                $type->uuid = (string) Str::uuid();
            }
            if (blank($type->slug)) {
                $type->slug = Str::slug($type->name);
            }
            $type->rest_base ??= $type->slug;
        });
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }

    public function supports(string $feature): bool
    {
        return in_array($feature, $this->supports ?? [], true);
    }

    /**
     * Built-in post / page definitions.
     *
     * @return list<array<string, mixed>>
     */
    public static function builtinDefinitions(): array
    {
        return [
            [
                'name' => 'post',
                'slug' => 'post',
                'singular_label' => 'Post',
                'plural_label' => 'Posts',
                'supports' => [
                    'title', 'editor', 'excerpt', 'author', 'featured_image',
                    'comments', 'revisions', 'custom_fields', 'archive',
                ],
                'capabilities' => [
                    'create_posts', 'edit_posts', 'edit_others_posts',
                    'publish_posts', 'delete_posts',
                ],
                'hierarchical' => false,
                'has_archive' => true,
                'public' => true,
                'show_in_rest' => true,
                'rest_base' => 'posts',
                'menu_icon' => 'document-text',
                'menu_position' => 5,
                'is_builtin' => true,
            ],
            [
                'name' => 'page',
                'slug' => 'page',
                'singular_label' => 'Page',
                'plural_label' => 'Pages',
                'supports' => [
                    'title', 'editor', 'author', 'featured_image',
                    'revisions', 'custom_fields', 'hierarchy',
                ],
                'capabilities' => [
                    'create_pages', 'edit_pages', 'edit_others_pages',
                    'publish_pages', 'delete_pages',
                ],
                'hierarchical' => true,
                'has_archive' => false,
                'public' => true,
                'show_in_rest' => true,
                'rest_base' => 'pages',
                'menu_icon' => 'document',
                'menu_position' => 20,
                'is_builtin' => true,
            ],
        ];
    }
}
