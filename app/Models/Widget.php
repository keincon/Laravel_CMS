<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Widget extends Model
{
    protected $fillable = [
        'sidebar_id',
        'type',
        'title',
        'settings',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function sidebar(): BelongsTo
    {
        return $this->belongsTo(Sidebar::class);
    }

    /**
     * @return array<string, string>
     */
    public static function types(): array
    {
        $registry = app(\App\Support\Widgets\WidgetRegistry::class);
        if ($registry->all() === []) {
            $registry->registerDefaults();
        }

        $types = [];
        foreach ($registry->all() as $id => $definition) {
            $types[$id] = (string) ($definition['label'] ?? $id);
        }

        return $types;
    }
}
