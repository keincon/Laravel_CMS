<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MetaType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMeta extends Model
{
    protected $table = 'user_meta';

    protected $fillable = [
        'user_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
