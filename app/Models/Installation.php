<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Installation extends Model
{
    protected $fillable = [
        'version',
        'installed_at',
        'installed_by',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'installed_at' => 'datetime',
            'meta' => 'array',
        ];
    }
}
