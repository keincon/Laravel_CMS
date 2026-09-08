<?php

declare(strict_types=1);

namespace App\Support\Plugins;

use App\Models\Plugin;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Discovers LaravelPress plugins under /plugins/{slug}/plugin.json.
 * PHP runs only for plugins marked active in the database.
 */
final class PluginManager
{
    /** @var array<string, array<string, mixed>> */
    private array $plugins = [];

    /** @var list<string> */
    private array $booted = [];

    public function __construct(private readonly string $basePath) {}

    public function pluginsPath(): string
    {
        return $this->basePath;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function discover(): array
    {
        $this->plugins = [];

        if (! File::isDirectory($this->basePath)) {
            File::ensureDirectoryExists($this->basePath);

            return [];
        }

        foreach (File::directories($this->basePath) as $dir) {
            $manifestPath = $dir.DIRECTORY_SEPARATOR.'plugin.json';
            if (! File::exists($manifestPath)) {
                continue;
            }

            $json = json_decode(File::get($manifestPath), true);
            if (! is_array($json)) {
                continue;
            }

            $slug = Str::slug((string) ($json['slug'] ?? basename($dir)));
            if ($slug === '') {
                continue;
            }

            $manifest = array_merge($json, [
                'slug' => $slug,
                'name' => (string) ($json['name'] ?? Str::headline($slug)),
                'version' => (string) ($json['version'] ?? '1.0.0'),
                'description' => (string) ($json['description'] ?? ''),
                'path' => $dir,
                'type' => 'native',
            ]);

            $manifest['is_active'] = $this->isActiveInDatabase($slug);

            $this->plugins[$slug] = $manifest;
        }

        return $this->plugins;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        if ($this->plugins === []) {
            $this->discover();
        }

        return $this->plugins;
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

    public function isActive(string $slug): bool
    {
        return (bool) ($this->get($slug)['is_active'] ?? false);
    }

    public function syncDiskToDatabase(): int
    {
        if (! $this->pluginsTableReady()) {
            return 0;
        }

        $count = 0;
        foreach ($this->discover() as $slug => $manifest) {
            Plugin::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => (string) $manifest['name'],
                    'version' => (string) $manifest['version'],
                    'type' => 'native',
                    'settings' => [
                        'description' => $manifest['description'] ?? '',
                        'author' => $manifest['author'] ?? null,
                        'provider' => $manifest['provider'] ?? null,
                    ],
                ]
            );
            $count++;
        }

        return $count;
    }

    public function activate(string $slug): void
    {
        if (! $this->has($slug)) {
            throw new \InvalidArgumentException("Unknown plugin [{$slug}].");
        }

        $manifest = $this->get($slug) ?? [];

        if ($this->pluginsTableReady()) {
            Plugin::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => (string) ($manifest['name'] ?? $slug),
                    'version' => (string) ($manifest['version'] ?? '1.0.0'),
                    'type' => 'native',
                    'is_active' => true,
                    'settings' => [
                        'description' => $manifest['description'] ?? '',
                        'provider' => $manifest['provider'] ?? null,
                    ],
                ]
            );
        }

        $this->plugins[$slug]['is_active'] = true;
        $this->bootPlugin($manifest + ['is_active' => true], app());
    }

    public function deactivate(string $slug): void
    {
        if (! $this->has($slug)) {
            throw new \InvalidArgumentException("Unknown plugin [{$slug}].");
        }

        if ($this->pluginsTableReady()) {
            Plugin::query()->where('slug', $slug)->update(['is_active' => false]);
        }

        $this->plugins[$slug]['is_active'] = false;
        // Providers stay registered until next request — documented behavior.
    }

    public function registerEnabled(Application $app): void
    {
        foreach ($this->discover() as $manifest) {
            if (! ($manifest['is_active'] ?? false)) {
                continue;
            }
            $this->bootPlugin($manifest, $app);
        }
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function bootPlugin(array $manifest, Application $app): void
    {
        $slug = (string) ($manifest['slug'] ?? '');
        if ($slug === '' || in_array($slug, $this->booted, true)) {
            return;
        }

        $path = (string) ($manifest['path'] ?? '');
        if ($path === '' || ! File::isDirectory($path)) {
            return;
        }

        $hooks = $path.DIRECTORY_SEPARATOR.'hooks.php';
        if (File::exists($hooks)) {
            require_once $hooks;
        }

        $providerFile = $path.DIRECTORY_SEPARATOR.ltrim(
            (string) ($manifest['provider_file'] ?? 'src/PluginServiceProvider.php'),
            '/\\'
        );
        if (File::exists($providerFile)) {
            require_once $providerFile;
        }

        $class = $manifest['provider'] ?? null;
        if (is_string($class) && class_exists($class)) {
            $app->register($class);
        }

        $routes = $path.DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.'web.php';
        if (File::exists($routes) && ! $app->routesAreCached()) {
            require $routes;
        }

        $this->booted[] = $slug;
    }

    private function isActiveInDatabase(string $slug): bool
    {
        if (! $this->pluginsTableReady()) {
            return false;
        }

        try {
            return (bool) Plugin::query()->where('slug', $slug)->where('is_active', true)->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    private function pluginsTableReady(): bool
    {
        try {
            return Schema::hasTable('plugins');
        } catch (\Throwable) {
            return false;
        }
    }
}
