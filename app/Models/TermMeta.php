<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MetaType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermMeta extends Model
{
    protected $table = 'term_meta';

    protected $fillable = [
        'term_id',
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

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }
}
