<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sidebar extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function widgets(): HasMany
    {
        return $this->hasMany(Widget::class)->orderBy('sort_order');
    }

    public function activeWidgets(): HasMany
    {
        return $this->widgets()->where('is_active', true);
    }

    public static function main(): self
    {
        return static::query()->firstOrCreate(
            ['slug' => 'main'],
            ['name' => 'Main Sidebar', 'description' => 'Default blog sidebar']
        );
    }
}
