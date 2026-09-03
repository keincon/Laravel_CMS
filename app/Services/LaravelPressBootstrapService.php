<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ContentType;
use App\Models\Taxonomy;
use App\Support\Hooks\Hooks;
use Illuminate\Support\Facades\DB;

/**
 * Seeds built-in content types and taxonomies for LaravelPress.
 */
final class LaravelPressBootstrapService
{
    public function seedBuiltins(): void
    {
        DB::transaction(function (): void {
            foreach (ContentType::builtinDefinitions() as $definition) {
                ContentType::query()->updateOrCreate(
                    ['slug' => $definition['slug']],
                    $definition,
                );
            }

            foreach (Taxonomy::builtinDefinitions() as $definition) {
                Taxonomy::query()->updateOrCreate(
                    ['slug' => $definition['slug']],
                    $definition,
                );
            }
        });

        Hooks::action('laravelpress.builtins.seeded');
    }
}
