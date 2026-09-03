<?php

namespace App\Models;

use App\Support\HasSeoFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use HasSeoFields;
    use SoftDeletes;

    /**
     * Static pages only — never use this model for Blog/Category/etc. systems.
     */
    public const KIND = 'static';

    protected $fillable = [
        'parent_id',
        'title',
        'slug',
        'content',
        'excerpt',
        'custom_css',
        'custom_js',
        'custom_html',
        'status',
        'author_id',
        'featured_image_id',
        'published_at',
        'template',
        'header_mode',
        'header_id',
        'footer_mode',
        'footer_id',
        'sidebar_position',
        'seo_title',
        'seo_description',
        'seo_canonical',
        'seo_robots',
        'seo_image_id',
        'og_title',
        'og_description',
        'og_image_id',
        'og_type',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function isStatic(): bool
    {
        return true;
    }

    public function pageKind(): string
    {
        return self::KIND;
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('title');
    }

    public function header(): BelongsTo
    {
        return $this->belongsTo(Header::class);
    }

    public function footer(): BelongsTo
    {
        return $this->belongsTo(Footer::class);
    }

    public function revisions(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(ContentRevision::class, 'revisable')->latest();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'publish')
            ->where(function (Builder $q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }
}
