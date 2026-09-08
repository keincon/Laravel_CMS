<?php

declare(strict_types=1);

namespace App\Services\Themes;

use App\Models\Theme;
use App\Models\ThemeSetting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

final class ThemeManager
{
    /** @var array<string, array<string, mixed>> */
    private array $themes = [];

    public function __construct(private readonly string $themesPath) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function discover(): array
    {
        $this->themes = [];
        if (! File::isDirectory($this->themesPath)) {
            return [];
        }

        foreach (File::directories($this->themesPath) as $dir) {
            $slug = basename($dir);
            $manifestPath = $dir.DIRECTORY_SEPARATOR.'theme.json';
            $manifest = [
                'name' => $slug,
                'slug' => $slug,
                'version' => '1.0.0',
                'description' => '',
                'path' => $dir,
                'stylesheets' => [],
                'scripts' => [],
                'screens' => [],
            ];
            if (File::exists($manifestPath)) {
                $json = json_decode(File::get($manifestPath), true) ?: [];
                $manifest = array_merge($manifest, $json, ['path' => $dir, 'slug' => $slug]);
            }

            $manifest['stylesheets'] = $this->resolveAssetList($dir, $manifest['stylesheets'] ?? null, 'css', ['theme.css']);
            $manifest['scripts'] = $this->resolveAssetList($dir, $manifest['scripts'] ?? null, 'js', ['theme.js']);
            $manifest['screens'] = $this->discoverScreens($dir);
            if (($manifest['stylesheets'][0] ?? null) !== null) {
                $manifest['stylesheet'] = $dir.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.$manifest['stylesheets'][0];
            }

            $this->themes[$slug] = $manifest;
        }

        return $this->themes;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        if ($this->themes === []) {
            $this->discover();
        }

        return $this->themes;
    }

    public function has(string $slug): bool
    {
        return isset($this->all()[$slug]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $slug): ?array
    {
        return $this->all()[$slug] ?? null;
    }

    public function activeSlug(): string
    {
        try {
            if (Schema::hasTable('themes')) {
                $slug = Theme::query()->where('is_active', true)->value('slug');
                if (is_string($slug) && $slug !== '' && $this->has($slug)) {
                    return $slug;
                }
            }
        } catch (\Throwable) {
            // Fall through to config during early boot / tests.
        }

        $configured = (string) config('cms.theme', 'default');

        return $this->has($configured) ? $configured : 'default';
    }

    /**
     * @return array<string, mixed>
     */
    public function active(): array
    {
        $slug = $this->activeSlug();

        return $this->get($slug) ?? [
            'name' => 'Default',
            'slug' => 'default',
            'path' => $this->themesPath.DIRECTORY_SEPARATOR.'default',
            'stylesheets' => [],
            'scripts' => [],
            'screens' => [],
        ];
    }

    public function themesPath(): string
    {
        return $this->themesPath;
    }

    public function activate(string $slug, bool $applyColors = true): void
    {
        if (! $this->has($slug)) {
            throw new \InvalidArgumentException("Unknown theme [{$slug}].");
        }

        $manifest = $this->get($slug) ?? [];

        if (Schema::hasTable('themes')) {
            Theme::query()->update(['is_active' => false]);
            Theme::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => (string) ($manifest['name'] ?? $slug),
                    'version' => (string) ($manifest['version'] ?? '1.0.0'),
                    'is_active' => true,
                    'settings' => [
                        'description' => $manifest['description'] ?? '',
                        'author' => $manifest['author'] ?? null,
                        'screens' => count($manifest['screens'] ?? []),
                        'stylesheets' => $manifest['stylesheets'] ?? [],
                        'scripts' => $manifest['scripts'] ?? [],
                    ],
                ]
            );
        }

        config(['cms.theme' => $slug]);

        if ($applyColors && Schema::hasTable('theme_settings') && is_array($manifest['colors'] ?? null)) {
            $settings = ThemeSetting::current();
            $settings->fill(array_intersect_key($manifest['colors'], array_flip([
                'primary_color', 'secondary_color', 'accent_color',
                'success_color', 'warning_color', 'danger_color', 'info_color',
                'background_color', 'surface_color', 'text_color',
            ])));
            $settings->theme = $slug;
            if (isset($manifest['color_mode']) && in_array($manifest['color_mode'], ['light', 'dark', 'system'], true)) {
                $settings->color_mode = $manifest['color_mode'];
            }
            $settings->save();
            ThemeSetting::forgetCache();
        }
    }

    public function syncDiskThemesToDatabase(): int
    {
        if (! Schema::hasTable('themes')) {
            return 0;
        }

        $count = 0;
        $active = $this->activeSlug();
        foreach ($this->discover() as $slug => $manifest) {
            Theme::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => (string) ($manifest['name'] ?? $slug),
                    'version' => (string) ($manifest['version'] ?? '1.0.0'),
                    'is_active' => $slug === $active,
                    'settings' => [
                        'description' => $manifest['description'] ?? '',
                        'author' => $manifest['author'] ?? null,
                        'screens' => count($manifest['screens'] ?? []),
                        'stylesheets' => $manifest['stylesheets'] ?? [],
                        'scripts' => $manifest['scripts'] ?? [],
                    ],
                ]
            );
            $count++;
        }

        return $count;
    }

    /**
     * @return list<string>
     */
    public function stylesheetUrls(?string $slug = null): array
    {
        $slug ??= $this->activeSlug();
        $theme = $this->get($slug);
        if ($theme === null) {
            return [];
        }

        $urls = [];
        foreach ($theme['stylesheets'] ?? [] as $file) {
            $urls[] = $this->assetUrl($slug, (string) $file);
        }

        return array_values(array_filter($urls));
    }

    /**
     * @return list<string>
     */
    public function scriptUrls(?string $slug = null): array
    {
        $slug ??= $this->activeSlug();
        $theme = $this->get($slug);
        if ($theme === null) {
            return [];
        }

        $urls = [];
        foreach ($theme['scripts'] ?? [] as $file) {
            $urls[] = $this->assetUrl($slug, (string) $file);
        }

        return array_values(array_filter($urls));
    }

    public function assetUrl(string $slug, string $relativePath): ?string
    {
        $theme = $this->get($slug);
        if ($theme === null) {
            return null;
        }

        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return null;
        }

        $full = rtrim((string) $theme['path'], DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.'assets'
            .DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        if (! File::isFile($full)) {
            return null;
        }

        // Prefer public/ copy when present so Docker's php -S can serve files
        // statically (avoids slow sequential Laravel bootstraps for images).
        $this->ensurePublicAsset($slug, $relativePath, $full);

        $url = route('theme.asset', ['theme' => $slug, 'path' => $relativePath]);
        $mtime = @filemtime($full);
        if ($mtime) {
            $url .= (str_contains($url, '?') ? '&' : '?').'v='.$mtime;
        }

        return $url;
    }

    /**
     * Mirror theme package assets into public/themes/{slug}/assets for static serving.
     */
    public function publishAssets(?string $slug = null): int
    {
        $slug ??= $this->activeSlug();
        $theme = $this->get($slug) ?? ($this->discover()[$slug] ?? null);
        if ($theme === null) {
            return 0;
        }

        $srcRoot = rtrim((string) $theme['path'], DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'assets';
        if (! File::isDirectory($srcRoot)) {
            return 0;
        }

        $destRoot = public_path('themes'.DIRECTORY_SEPARATOR.$slug.DIRECTORY_SEPARATOR.'assets');
        File::ensureDirectoryExists($destRoot);

        $copied = 0;
        foreach (File::allFiles($srcRoot) as $file) {
            $rel = ltrim(str_replace('\\', '/', $file->getRelativePathname()), '/');
            if ($rel === '' || str_contains($rel, '..')) {
                continue;
            }
            $dest = $destRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $rel);
            File::ensureDirectoryExists(dirname($dest));
            if (! is_file($dest) || filemtime($dest) < $file->getMTime() || filesize($dest) !== $file->getSize()) {
                File::copy($file->getPathname(), $dest);
                $copied++;
            }
        }

        return $copied;
    }

    private function ensurePublicAsset(string $slug, string $relativePath, string $sourceFull): void
    {
        $dest = public_path(
            'themes'.DIRECTORY_SEPARATOR.$slug.DIRECTORY_SEPARATOR.'assets'
            .DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath)
        );
        if (is_file($dest) && filesize($dest) === @filesize($sourceFull) && filemtime($dest) >= @filemtime($sourceFull)) {
            return;
        }
        File::ensureDirectoryExists(dirname($dest));
        @File::copy($sourceFull, $dest);
    }

    public function publicStylesheetUrl(string $slug): ?string
    {
        return $this->stylesheetUrls($slug)[0] ?? null;
    }

    /**
     * Create a starter theme folder with screens, CSS, and JS stubs.
     *
     * @return array{slug: string, path: string}
     */
    public function scaffold(string $slug, ?string $name = null): array
    {
        $slug = \Illuminate\Support\Str::slug($slug);
        if ($slug === '' || $slug === 'default') {
            throw new \InvalidArgumentException('Choose a non-empty slug other than default.');
        }
        if ($this->has($slug) || File::isDirectory($this->themesPath.DIRECTORY_SEPARATOR.$slug)) {
            throw new \InvalidArgumentException("Theme [{$slug}] already exists.");
        }

        $path = $this->themesPath.DIRECTORY_SEPARATOR.$slug;
        File::ensureDirectoryExists($path.DIRECTORY_SEPARATOR.'assets');
        File::ensureDirectoryExists($path.DIRECTORY_SEPARATOR.'pages');
        File::ensureDirectoryExists($path.DIRECTORY_SEPARATOR.'dynamic');
        File::ensureDirectoryExists($path.DIRECTORY_SEPARATOR.'partials');

        $display = $name ?: \Illuminate\Support\Str::headline($slug);
        $manifest = [
            'name' => $display,
            'slug' => $slug,
            'version' => '1.0.0',
            'author' => '',
            'description' => "Custom theme: {$display}",
            'color_mode' => 'light',
            'colors' => [
                'primary_color' => '#0D9488',
                'secondary_color' => '#64748B',
                'accent_color' => '#0284C7',
                'success_color' => '#16A34A',
                'warning_color' => '#D97706',
                'danger_color' => '#DC2626',
                'info_color' => '#0891B2',
                'background_color' => '#F8FAFC',
                'surface_color' => '#FFFFFF',
                'text_color' => '#0F172A',
            ],
            'stylesheets' => ['theme.css'],
            'scripts' => ['theme.js'],
            'screens' => [
                'pages/default',
                'dynamic/blog',
                'dynamic/post',
            ],
        ];

        File::put(
            $path.DIRECTORY_SEPARATOR.'theme.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
        );

        File::put($path.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'theme.css', <<<CSS
/* {$display} theme styles */
body.theme-{$slug} {
  /* Add layout / typography here */
}
body.theme-{$slug} .theme-hero {
  padding: 2.5rem 0 1rem;
}
CSS
        );

        File::put($path.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'theme.js', <<<JS
/* {$display} front-end behaviors */
(function () {
  document.documentElement.classList.add('theme-{$slug}-ready');
})();
JS
        );

        File::put($path.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.'default.blade.php', <<<'BLADE'
<x-layout.master
    :page="$page ?? null"
    context="page"
    :seo-path="$seoPath ?? null"
    :seo-meta="$seoMeta ?? null"
    :breadcrumbs="$breadcrumbs ?? []"
>
    <article class="theme-screen theme-page">
        <header class="theme-hero">
            <h1>{{ $page->title ?? 'Page' }}</h1>
        </header>
        <div class="theme-body">
            {!! $page->content ?? '' !!}
        </div>
    </article>
</x-layout.master>
BLADE
        );

        File::put($path.DIRECTORY_SEPARATOR.'dynamic'.DIRECTORY_SEPARATOR.'blog.blade.php', <<<'BLADE'
<x-layout.master
    :dynamic-config="$dynamicConfig ?? null"
    context="blog"
    :seo-path="$seoPath ?? null"
    :seo-meta="$seoMeta ?? null"
    :breadcrumbs="$breadcrumbs ?? []"
>
    <div class="theme-screen theme-blog">
        <header class="theme-hero">
            <h1>{{ $dynamicConfig->title ?? 'Blog' }}</h1>
            @if (! empty($dynamicConfig?->description))
                <p>{{ $dynamicConfig->description }}</p>
            @endif
        </header>
        <x-post-grid :posts="$posts" :layout="$layoutStyle ?? 'list'" />
        <x-pagination :paginator="$posts" />
    </div>
</x-layout.master>
BLADE
        );

        File::put($path.DIRECTORY_SEPARATOR.'dynamic'.DIRECTORY_SEPARATOR.'post.blade.php', <<<'BLADE'
<x-layout.master
    :post="$post ?? null"
    context="post"
    :seo-path="$seoPath ?? null"
    :seo-meta="$seoMeta ?? null"
    :breadcrumbs="$breadcrumbs ?? []"
>
    <article class="theme-screen theme-post">
        <header class="theme-hero">
            <h1>{{ $post->title ?? '' }}</h1>
        </header>
        <div class="theme-body">
            {!! $post->content ?? '' !!}
        </div>
    </article>
</x-layout.master>
BLADE
        );

        File::put($path.DIRECTORY_SEPARATOR.'partials'.DIRECTORY_SEPARATOR.'readme.txt', <<<TXT
Theme screens (Blade):
  pages/*.blade.php     — static page templates
  dynamic/*.blade.php   — blog, post, archive, search, 404, …
  partials/             — include with @include('themes.{$slug}.partials.NAME')

Assets (public via /themes/{$slug}/assets/...):
  assets/*.css  assets/*.js  assets/images/*

Missing screens fall back to the default theme.
TXT
        );

        $this->discover();
        $this->syncDiskThemesToDatabase();

        return ['slug' => $slug, 'path' => $path];
    }

    /**
     * @param  mixed  $configured
     * @param  list<string>  $preferred
     * @return list<string>
     */
    private function resolveAssetList(string $dir, mixed $configured, string $extension, array $preferred): array
    {
        $assetsDir = $dir.DIRECTORY_SEPARATOR.'assets';
        if (is_array($configured) && $configured !== []) {
            $list = [];
            foreach ($configured as $file) {
                $file = ltrim(str_replace('\\', '/', (string) $file), '/');
                if ($file !== '' && File::isFile($assetsDir.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $file))) {
                    $list[] = $file;
                }
            }

            return array_values(array_unique($list));
        }

        $found = [];
        foreach ($preferred as $file) {
            if (File::isFile($assetsDir.DIRECTORY_SEPARATOR.$file)) {
                $found[] = $file;
            }
        }

        if ($found !== [] || ! File::isDirectory($assetsDir)) {
            return $found;
        }

        foreach (File::files($assetsDir) as $file) {
            if (strtolower($file->getExtension()) === $extension) {
                $found[] = $file->getFilename();
            }
        }
        sort($found);

        return $found;
    }

    /**
     * @return list<string>
     */
    private function discoverScreens(string $dir): array
    {
        $screens = [];
        foreach (['pages', 'dynamic', 'partials'] as $folder) {
            $folderPath = $dir.DIRECTORY_SEPARATOR.$folder;
            if (! File::isDirectory($folderPath)) {
                continue;
            }
            foreach (File::allFiles($folderPath) as $file) {
                $name = $file->getFilename();
                if (! str_ends_with(strtolower($name), '.blade.php')) {
                    continue;
                }
                $relative = ltrim(str_replace($dir, '', $file->getPathname()), DIRECTORY_SEPARATOR);
                $relative = str_replace('\\', '/', $relative);
                $screens[] = preg_replace('/\.blade\.php$/i', '', $relative) ?: $relative;
            }
        }

        sort($screens);

        return $screens;
    }
}

