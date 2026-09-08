<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Content extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'content_type_id',
        'title',
        'slug',
        'body',
        'blocks',
        'excerpt',
        'status',
        'visibility',
        'password',
        'author_id',
        'parent_id',
        'featured_media_id',
        'comment_status',
        'template',
            'menu_order',
            'published_at',
            'scheduled_at',
        ];

    protected function casts(): array
    {
        return [
            'blocks' => 'array',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Content $content): void {
            if (blank($content->uuid)) {
                $content->uuid = (string) Str::uuid();
            }
            if (blank($content->slug) && filled($content->title)) {
                $content->slug = Str::slug($content->title);
            }
        });
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ContentType::class, 'content_type_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('menu_order');
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    /**
     * Theme compatibility: legacy Post views expect `$post->content` and `featuredImage`.
     * Prefer stored body; if empty and blocks exist, render blocks on the fly.
     */
    public function getContentAttribute(): ?string
    {
        if (filled($this->attributes['body'] ?? null)) {
            return $this->attributes['body'];
        }

        $blocks = $this->blocks;
        if (is_array($blocks) && $blocks !== []) {
            return app(\App\Support\Blocks\BlockRenderer::class)->render($blocks);
        }

        return $this->attributes['body'] ?? null;
    }

    /**
     * True when the page has HTML in body but no block editor data yet
     * (common for seeded theme pages).
     */
    public function hasHtmlBodyWithoutBlocks(): bool
    {
        $blocks = $this->blocks;

        return ( ! is_array($blocks) || $blocks === []) && filled(trim((string) $this->body));
    }

    /**
     * Blocks for the admin editor. If blocks are empty but body has HTML,
     * wrap body in a single Custom HTML block so editors can see/edit it.
     *
     * @return list<array<string, mixed>>
     */
    public function editorBlocks(): array
    {
        $blocks = $this->blocks;
        if (is_array($blocks) && $blocks !== []) {
            return array_values($blocks);
        }

        $body = (string) $this->body;
        if (trim($body) === '') {
            return [];
        }

        return [[
            'type' => 'html',
            'content' => $body,
            'attrs' => ['from_html_body' => true],
            'innerBlocks' => [],
        ]];
    }

    public function featuredImage(): BelongsTo
    {
        return $this->featuredMedia();
    }

    public function isCommentsOpen(): bool
    {
        return ($this->comment_status ?: 'open') === 'open';
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'content_id');
    }

    public function approvedComments(): HasMany
    {
        return $this->comments()
            ->approved()
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->approved()->oldest(), 'user'])
            ->latest();
    }

    public function meta(): HasMany
    {
        return $this->hasMany(ContentMeta::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ContentRevision::class)->orderByDesc('revision_number');
    }

    public function terms(): BelongsToMany
    {
        return $this->belongsToMany(Term::class, 'content_term')->withTimestamps();
    }

    public function scopeOfType(Builder $query, string $typeSlug): Builder
    {
        return $query->whereHas('type', fn (Builder $q) => $q->where('slug', $typeSlug));
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Published)
            ->where(function (Builder $q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function scopeNotTrashed(Builder $query): Builder
    {
        return $query->where('status', '!=', ContentStatus::Trash);
    }

    public function isHierarchicalCycle(int $parentId): bool
    {
        if ($this->id === $parentId) {
            return true;
        }

        $current = self::query()->find($parentId);
        $guard = 0;

        while ($current !== null && $guard < 100) {
            if ($current->id === $this->id) {
                return true;
            }
            $current = $current->parent;
            $guard++;
        }

        return false;
    }
}
