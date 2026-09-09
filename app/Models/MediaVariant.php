<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MediaVariant extends Model
{
    protected $fillable = [
        'media_id',
        'variant',
        'disk',
        'path',
        'mime_type',
        'width',
        'height',
        'size',
    ];

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function url(): string
    {
        if (($this->disk ?: 'public') === 'public') {
            $path = 'storage/'.ltrim((string) $this->path, '/');

            if (request()?->getHost()) {
                return url($path);
            }

            return '/'.$path;
        }

        return Storage::disk($this->disk)->url($this->path);
    }
}
