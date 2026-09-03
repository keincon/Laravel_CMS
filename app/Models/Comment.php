<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    protected $fillable = [
        'post_id', 'content_id', 'user_id', 'parent_id', 'author_name', 'author_email',
        'author_url', 'author_ip', 'user_agent', 'content', 'status',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function contentEntry(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function displayName(): string
    {
        return $this->user?->name ?: ($this->author_name ?: 'Anonymous');
    }

    public function displayEmail(): ?string
    {
        return $this->user?->email ?: $this->author_email;
    }
}
