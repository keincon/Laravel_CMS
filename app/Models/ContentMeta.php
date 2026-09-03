<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MetaType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentMeta extends Model
{
    protected $table = 'content_meta';

    protected $fillable = [
        'content_id',
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

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function decodedValue(): mixed
    {
        $type = $this->type instanceof MetaType ? $this->type : MetaType::String;

        return $type->cast($this->value);
    }
}
