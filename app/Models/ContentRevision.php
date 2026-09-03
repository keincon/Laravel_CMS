<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentRevision extends Model
{
    protected $fillable = [
        'content_id',
        'revisable_type',
        'revisable_id',
        'user_id',
        'revision_number',
        'title',
        'body',
        'excerpt',
        'blocks',
        'metadata',
        'payload',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'blocks' => 'array',
            'metadata' => 'array',
        ];
    }

    public function revisable(): MorphTo
    {
        return $this->morphTo();
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function author(): BelongsTo
    {
        return $this->user();
    }
}
