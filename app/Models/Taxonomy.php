<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Taxonomy extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'singular_label',
        'plural_label',
        'hierarchical',
        'public',
        'show_in_rest',
        'rest_base',
        'content_types',
        'capabilities',
        'is_builtin',
    ];

    protected function casts(): array
    {
        return [
            'hierarchical' => 'boolean',
            'public' => 'boolean',
            'show_in_rest' => 'boolean',
            'content_types' => 'array',
            'capabilities' => 'array',
            'is_builtin' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Taxonomy $taxonomy): void {
            if (blank($taxonomy->uuid)) {
                $taxonomy->uuid = (string) Str::uuid();
            }
            if (blank($taxonomy->slug)) {
                $taxonomy->slug = Str::slug($taxonomy->name);
            }
            $taxonomy->rest_base ??= $taxonomy->slug;
        });
    }

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function builtinDefinitions(): array
    {
        return [
            [
                'name' => 'category',
                'slug' => 'category',
                'singular_label' => 'Category',
                'plural_label' => 'Categories',
                'hierarchical' => true,
                'public' => true,
                'show_in_rest' => true,
                'rest_base' => 'categories',
                'content_types' => ['post'],
                'capabilities' => ['manage_categories'],
                'is_builtin' => true,
            ],
            [
                'name' => 'tag',
                'slug' => 'post_tag',
                'singular_label' => 'Tag',
                'plural_label' => 'Tags',
                'hierarchical' => false,
                'public' => true,
                'show_in_rest' => true,
                'rest_base' => 'tags',
                'content_types' => ['post'],
                'capabilities' => ['manage_categories'],
                'is_builtin' => true,
            ],
        ];
    }
}
