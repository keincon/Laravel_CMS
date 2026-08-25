<?php

namespace App\Providers;

use App\Services\ThemeService;
use App\Support\Facades\Theme;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ThemeService::class);
        $this->app->alias(ThemeService::class, 'theme');
    }

    public function boot(): void
    {
        Blade::componentNamespace('App\\View\\Components', 'cms');

        if (! class_exists('Theme', false)) {
            class_alias(Theme::class, 'Theme');
        }
    }
}
