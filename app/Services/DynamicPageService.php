<?php

namespace App\Services;

use App\Models\DynamicPageSetting;
use Illuminate\Support\Collection;

class DynamicPageService
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function typeDefinitions(): array
    {
        return config('cms.dynamic_page_types', []);
    }

    public function ensureDefaults(): void
    {
        foreach ($this->typeDefinitions() as $type => $def) {
            DynamicPageSetting::query()->firstOrCreate(
                ['type' => $type],
                [
                    'label' => $def['label'] ?? ucfirst($type),
                    'title' => $def['default_title'] ?? ($def['label'] ?? ucfirst($type)),
                    'description' => null,
                    'url_path' => $def['default_url'] ?? null,
                    'posts_per_page' => $def['default_posts_per_page'] ?? 12,
                    'layout' => $def['default_layout'] ?? 'list',
                    'sidebar_position' => null,
                    'template' => 'default',
                    'header_mode' => 'master',
                    'footer_mode' => 'master',
                    'is_enabled' => true,
                    'seo_robots' => $def['default_seo_robots'] ?? null,
                    'message' => $def['default_message'] ?? null,
                    'button_label' => $def['default_button_label'] ?? null,
                    'button_url' => $def['default_button_url'] ?? null,
                    'settings' => [],
                ]
            );
        }

        DynamicPageSetting::forgetCache();
    }

    public function get(string $type): DynamicPageSetting
    {
        $this->ensureDefaults();

        $setting = DynamicPageSetting::forType($type);

        if (! $setting) {
            throw new \RuntimeException("Unknown dynamic page type [{$type}].");
        }

        return $setting;
    }

    /**
     * @return Collection<int, DynamicPageSetting>
     */
    public function all(): Collection
    {
        $this->ensureDefaults();

        return DynamicPageSetting::allCached();
    }

    public function isEnabled(string $type): bool
    {
        return (bool) $this->get($type)->is_enabled;
    }

    public function postsPerPage(string $type): int
    {
        $n = (int) $this->get($type)->posts_per_page;

        return max(1, min(100, $n ?: 12));
    }

    public function themeView(string $type, ?string $template = null): string
    {
        $theme = app(\App\Services\Themes\ThemeManager::class)->activeSlug();
        $def = $this->typeDefinitions()[$type] ?? [];
        $base = $def['view'] ?? "dynamic.{$type}";

        $candidates = [
            "themes.{$theme}.{$base}",
            "themes.{$theme}.dynamic.{$type}",
            "themes.default.{$base}",
            "themes.default.dynamic.{$type}",
            "site.{$type}",
        ];

        if ($template && $template !== 'default') {
            array_unshift(
                $candidates,
                "themes.{$theme}.dynamic.{$type}-{$template}",
                "themes.default.dynamic.{$type}-{$template}"
            );
        }

        foreach ($candidates as $view) {
            if (view()->exists($view)) {
                return $view;
            }
        }

        return "themes.default.dynamic.{$type}";
    }

    public function staticPageView(?string $template = null): string
    {
        $theme = app(\App\Services\Themes\ThemeManager::class)->activeSlug();
        $template = $template ?: 'default';
        $normalized = str_replace('_', '-', $template);

        $candidates = [
            "themes.{$theme}.pages.{$normalized}",
            "themes.{$theme}.pages.{$template}",
            "themes.{$theme}.pages.default",
            "themes.default.pages.{$normalized}",
            "themes.default.pages.{$template}",
            'themes.default.pages.default',
            'site.page',
            'site.home',
        ];

        foreach ($candidates as $view) {
            if (view()->exists($view)) {
                return $view;
            }
        }

        return "themes.{$theme}.pages.default";
    }

    /**
     * @return list<string>
     */
    public function reservedSlugs(): array
    {
        return array_values(array_unique(array_map(
            fn ($s) => strtolower(trim((string) $s)),
            config('cms.reserved_slugs', [])
        )));
    }

    public function isReservedSlug(string $slug): bool
    {
        $slug = strtolower(trim($slug, '/'));

        return in_array($slug, $this->reservedSlugs(), true);
    }

    public function reservedSlugRegex(): string
    {
        $parts = array_map(
            fn ($s) => preg_quote($s, '/'),
            $this->reservedSlugs()
        );

        return '^(?!'.implode('|', $parts).').*$';
    }
}
