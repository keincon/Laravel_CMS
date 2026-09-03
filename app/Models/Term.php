<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Term extends Model
{
    protected $fillable = [
        'uuid',
        'taxonomy_id',
        'name',
        'slug',
        'description',
        'parent_id',
        'count',
    ];

    protected static function booted(): void
    {
        static::creating(function (Term $term): void {
            if (blank($term->uuid)) {
                $term->uuid = (string) Str::uuid();
            }
            if (blank($term->slug) && filled($term->name)) {
                $term->slug = Str::slug($term->name);
            }
        });
    }

    public function taxonomy(): BelongsTo
    {
        return $this->belongsTo(Taxonomy::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'content_term')->withTimestamps();
    }

    public function meta(): HasMany
    {
        return $this->hasMany(TermMeta::class);
    }
}
