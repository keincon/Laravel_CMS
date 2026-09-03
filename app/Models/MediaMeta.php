<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MetaType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaMeta extends Model
{
    protected $table = 'media_meta';

    protected $fillable = [
        'media_id',
        'key',
        'type',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'type' => MetaType::class,
        ];
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
