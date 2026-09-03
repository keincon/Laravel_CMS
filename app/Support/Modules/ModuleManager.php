<?php

declare(strict_types=1);

namespace App\Support\Modules;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\File;

/**
 * Discovers enabled modules under /modules/{Name}/module.json.
 * Uploaded PHP is never executed unless an admin enables the module.
 */
final class ModuleManager
{
    /** @var array<string, array<string, mixed>> */
    private array $modules = [];

    public function __construct(private readonly string $basePath) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function discover(): array
    {
        $this->modules = [];

        if (! File::isDirectory($this->basePath)) {
            return [];
        }

        foreach (File::directories($this->basePath) as $dir) {
            $manifestPath = $dir.DIRECTORY_SEPARATOR.'module.json';
            if (! File::exists($manifestPath)) {
                continue;
            }

            /** @var array<string, mixed> $manifest */
            $manifest = json_decode(File::get($manifestPath), true, 512, JSON_THROW_ON_ERROR);
            $name = (string) ($manifest['name'] ?? basename($dir));
            $manifest['path'] = $dir;
            $manifest['enabled'] = (bool) ($manifest['enabled'] ?? false);
            $this->modules[$name] = $manifest;
        }

        return $this->modules;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function enabled(): array
    {
        if ($this->modules === []) {
            $this->discover();
        }

        return array_values(array_filter(
            $this->modules,
            static fn (array $m): bool => (bool) ($m['enabled'] ?? false),
        ));
    }

    public function registerEnabled(Application $app): void
    {
        foreach ($this->enabled() as $module) {
            $class = $module['provider'] ?? null;
            if (! is_string($class) || ! class_exists($class)) {
                continue;
            }

            $app->register($class);
        }
    }

    public function isEnabled(string $name): bool
    {
        if ($this->modules === []) {
            $this->discover();
        }

        return (bool) ($this->modules[$name]['enabled'] ?? false);
    }

    /**
     * Persist enabled flag into module.json (admin toggle).
     */
    public function setEnabled(string $name, bool $enabled): void
    {
        if ($this->modules === []) {
            $this->discover();
        }

        if (! isset($this->modules[$name])) {
            throw new \InvalidArgumentException("Unknown module [{$name}].");
        }

        $path = $this->modules[$name]['path'].DIRECTORY_SEPARATOR.'module.json';
        /** @var array<string, mixed> $manifest */
        $manifest = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        $manifest['enabled'] = $enabled;
        File::put($path, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

        $this->modules[$name]['enabled'] = $enabled;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        if ($this->modules === []) {
            $this->discover();
        }

        return $this->modules;
    }
}
