<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentBlock extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'content',
        'is_global',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'is_global' => 'boolean',
        ];
    }
}
