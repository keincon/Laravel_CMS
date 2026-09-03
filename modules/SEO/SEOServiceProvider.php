<?php

declare(strict_types=1);

namespace Modules\SEO;

use App\Support\Hooks\Hooks;
use Illuminate\Support\ServiceProvider;

final class SEOServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Module-local bindings can be registered here.
    }

    public function boot(): void
    {
        Hooks::addFilter('content.rendered', function (string $html): string {
            return $html;
        }, 5);

        Hooks::addAction('laravelpress.module.seo.booted', function (): void {});
        Hooks::action('laravelpress.module.seo.booted');
    }
}
