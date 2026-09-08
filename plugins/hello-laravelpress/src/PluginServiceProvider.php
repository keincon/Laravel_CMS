<?php

declare(strict_types=1);

namespace Plugins\HelloLaravelpress;

use App\Support\Hooks\Hooks;
use Illuminate\Support\ServiceProvider;

final class PluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Hooks::action('laravelpress.plugin.hello-laravelpress.booted');
    }
}
