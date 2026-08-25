<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentRevision extends Model
{
    protected $fillable = [
        'revisable_type',
        'revisable_id',
        'user_id',
        'payload',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function revisable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
