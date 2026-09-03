<?php

namespace App\Models;

use App\Support\HasSeoFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Post extends Model
{
    use HasSeoFields;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'status',
        'is_sticky',
        'comment_status',
        'custom_css',
        'custom_js',
        'custom_html',
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
            'is_sticky' => 'boolean',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function header(): BelongsTo
    {
        return $this->belongsTo(Header::class);
    }

    public function footer(): BelongsTo
    {
        return $this->belongsTo(Footer::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function approvedComments(): HasMany
    {
        return $this->comments()->approved()->whereNull('parent_id')->with(['children' => fn ($q) => $q->approved()->oldest(), 'user'])->latest();
    }

    public function revisions(): MorphMany
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

    public function scopeStickyFirst(Builder $query): Builder
    {
        return $query->orderByDesc('is_sticky');
    }

    public function scopeNotTrashed(Builder $query): Builder
    {
        return $query->where('status', '!=', 'trash');
    }

    public function isCommentsOpen(): bool
    {
        return ($this->comment_status ?: 'open') === 'open';
    }
}
