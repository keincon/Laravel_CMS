<?php

declare(strict_types=1);

namespace App\Services\Themes;

use Illuminate\Support\Facades\View;

/**
 * Resolves theme Blade templates with WordPress-like fallback chain.
 */
final class TemplateResolver
{
    public function __construct(private readonly ThemeManager $themes) {}

    /**
     * @param  list<string>  $candidates  e.g. ['single-post', 'single', 'index']
     */
    public function resolve(array $candidates): string
    {
        $theme = $this->themes->activeSlug();

        foreach ($candidates as $candidate) {
            $view = "themes.{$theme}.{$candidate}";
            if (View::exists($view)) {
                return $view;
            }

            // Nested dynamic/pages folders used by current CMS
            foreach (["dynamic.{$candidate}", "pages.{$candidate}"] as $nested) {
                $nestedView = "themes.{$theme}.{$nested}";
                if (View::exists($nestedView)) {
                    return $nestedView;
                }
            }
        }

        $fallback = "themes.{$theme}.dynamic.404";
        if (View::exists($fallback)) {
            return $fallback;
        }

        return 'themes.default.dynamic.404';
    }

    public function forContentType(string $typeSlug, ?string $slug = null): string
    {
        $candidates = array_filter([
            $slug ? "single-{$typeSlug}-{$slug}" : null,
            "single-{$typeSlug}",
            $typeSlug === 'page' ? 'page' : 'single',
            'index',
        ]);

        return $this->resolve(array_values($candidates));
    }

    public function forArchive(string $kind): string
    {
        return $this->resolve([$kind, 'archive', 'index']);
    }
}
