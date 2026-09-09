<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $fillable = [
        'disk',
        'path',
        'filename',
        'mime_type',
        'size',
        'alt',
        'uploaded_by',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function variants(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MediaVariant::class);
    }

    public function meta(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MediaMeta::class);
    }

    public function url(): string
    {
        // Prefer request-aware / root-relative URLs so Docker-mapped ports work.
        // Storage::url() uses APP_URL (often http://localhost without :32772), which
        // makes browsers hang waiting on the wrong origin for favicons/media.
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
