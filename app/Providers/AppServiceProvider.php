<?php

namespace App\Providers;

use App\Events\ContentPublished;
use App\Events\ContentUpdated;
use App\Events\SettingsUpdated;
use App\Listeners\InvalidateCmsCaches;
use App\Models\Content;
use App\Policies\ContentPolicy;
use App\Services\Search\DatabaseSearchDriver;
use App\Services\Search\SearchDriver;
use App\Services\Search\SearchService;
use App\Services\SettingsService;
use App\Services\ThemeService;
use App\Services\Themes\TemplateResolver;
use App\Services\Themes\ThemeManager;
use App\Support\Blocks\BlockRegistry;
use App\Support\Facades\Theme;
use App\Support\Hooks\HookRegistry;
use App\Support\Modules\ModuleManager;
use App\Support\Plugins\PluginManager;
use App\Support\Plugins\PluginPackageService;
use App\Support\Plugins\PluginScaffoldService;
use App\Support\Widgets\WidgetRegistry;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ThemeService::class);
        $this->app->alias(ThemeService::class, 'theme');

        $this->app->singleton(HookRegistry::class);
        $this->app->singleton(SettingsService::class);
        $this->app->singleton(\App\Services\CorsSettingsService::class);
        $this->app->singleton(SearchDriver::class, function () {
            return match (config('cms.search.driver', 'database')) {
                'meilisearch' => new \App\Services\Search\MeilisearchSearchDriver,
                default => new DatabaseSearchDriver,
            };
        });
        $this->app->singleton(SearchService::class);
        $this->app->singleton(\App\Support\Security\HtmlSanitizer::class);
        $this->app->singleton(\App\Support\Blocks\BlockRenderer::class);
        $this->app->singleton(\App\Services\Media\MediaUploadValidator::class);

        $this->app->singleton(ThemeManager::class, fn () => new ThemeManager(
            resource_path('views/themes')
        ));
        $this->app->singleton(TemplateResolver::class);

        $this->app->singleton(BlockRegistry::class, function () {
            $registry = new BlockRegistry;
            $registry->registerDefaults();

            return $registry;
        });

        $this->app->singleton(WidgetRegistry::class, function () {
            $registry = new WidgetRegistry;
            $registry->registerDefaults();

            return $registry;
        });

        $this->app->singleton(ModuleManager::class, fn () => new ModuleManager(
            base_path('modules')
        ));
        $this->app->singleton(PluginManager::class, fn () => new PluginManager(
            base_path('plugins')
        ));
        $this->app->singleton(PluginPackageService::class);
        $this->app->singleton(PluginScaffoldService::class);
        $this->app->singleton(\App\Services\WordPress\WordPressBridgeClient::class);
        $this->app->singleton(\App\Services\WordPress\WordPressPluginManager::class);
    }

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute((int) config('cms.api.rate_limit', 120))
                ->by($request->user()?->id ?: $request->ip());
        });

        Gate::before(function ($user, $ability) {
            if (method_exists($user, 'hasRole') && $user->hasRole('Administrator')) {
                return true;
            }

            return null;
        });

        Gate::policy(Content::class, ContentPolicy::class);

        Blade::componentNamespace('App\\View\\Components', 'cms');

        if (! class_exists('Theme', false)) {
            class_alias(Theme::class, 'Theme');
        }

        if (! class_exists('Hooks', false)) {
            class_alias(\App\Support\Hooks\Hooks::class, 'Hooks');
        }

        Event::listen(ContentUpdated::class, [InvalidateCmsCaches::class, 'handleContentUpdated']);
        Event::listen(ContentPublished::class, [InvalidateCmsCaches::class, 'handleContentUpdated']);
        Event::listen(SettingsUpdated::class, [InvalidateCmsCaches::class, 'handleSettingsUpdated']);

        $modules = $this->app->make(ModuleManager::class);
        $modules->discover();
        $modules->registerEnabled($this->app);

        $pluginManager = $this->app->make(PluginManager::class);
        $pluginManager->discover();
        $pluginManager->registerEnabled($this->app);

        $this->app->make(ThemeManager::class)->discover();

        $this->app->make(\App\Services\CorsSettingsService::class)->applyFromDatabase();
    }
}
