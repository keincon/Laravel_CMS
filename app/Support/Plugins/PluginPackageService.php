<?php

declare(strict_types=1);

namespace App\Support\Plugins;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Import/export LaravelPress plugin ZIP packages.
 */
final class PluginPackageService
{
    public function __construct(private readonly PluginManager $plugins) {}

    public function exportToZip(string $slug, ?string $destination = null): string
    {
        $plugin = $this->plugins->get($slug);
        if ($plugin === null) {
            throw new \InvalidArgumentException("Plugin [{$slug}] not found on disk.");
        }

        $source = (string) $plugin['path'];
        $destination ??= storage_path('app/plugin-packs/'.$slug.'.zip');
        File::ensureDirectoryExists(dirname($destination));

        if (File::exists($destination)) {
            File::delete($destination);
        }

        $zip = new ZipArchive;
        if ($zip->open($destination, ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('Unable to create plugin package ZIP.');
        }

        foreach (File::allFiles($source) as $file) {
            $relative = ltrim(str_replace($source, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $zip->addFile($file->getPathname(), str_replace('\\', '/', $relative));
        }
        $zip->close();

        return $destination;
    }

    /**
     * @return array{slug: string, name: string, path: string}
     */
    public function importFromZip(string $zipPath, bool $activate = false): array
    {
        if (! File::exists($zipPath)) {
            throw new \InvalidArgumentException('Plugin package not found.');
        }

        $tmp = storage_path('app/tmp/plugin-import-'.Str::random(8));
        File::ensureDirectoryExists($tmp);

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Unable to open plugin package ZIP.');
        }
        $zip->extractTo($tmp);
        $zip->close();

        try {
            $root = $this->resolvePackageRoot($tmp);
            $manifestPath = $root.DIRECTORY_SEPARATOR.'plugin.json';
            if (! File::exists($manifestPath)) {
                throw new \RuntimeException('plugin.json missing from package.');
            }

            $manifest = json_decode(File::get($manifestPath), true);
            if (! is_array($manifest)) {
                throw new \RuntimeException('Invalid plugin.json.');
            }

            $slug = Str::slug((string) ($manifest['slug'] ?? basename($root)));
            if ($slug === '') {
                throw new \RuntimeException('Invalid plugin slug.');
            }

            $this->assertSafePluginTree($root);

            $target = $this->plugins->pluginsPath().DIRECTORY_SEPARATOR.$slug;
            if (File::isDirectory($target)) {
                File::deleteDirectory($target);
            }
            File::copyDirectory($root, $target);

            $manifest['slug'] = $slug;
            File::put(
                $target.DIRECTORY_SEPARATOR.'plugin.json',
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
            );

            $this->plugins->discover();
            $this->plugins->syncDiskToDatabase();

            if ($activate) {
                $this->plugins->activate($slug);
            }

            return [
                'slug' => $slug,
                'name' => (string) ($manifest['name'] ?? $slug),
                'path' => $target,
            ];
        } finally {
            File::deleteDirectory($tmp);
        }
    }

    public function buildBundledPacksFromDisk(): int
    {
        $count = 0;
        File::ensureDirectoryExists(storage_path('app/plugin-packs'));
        foreach (array_keys($this->plugins->discover()) as $slug) {
            $this->exportToZip($slug);
            $count++;
        }

        return $count;
    }

    /**
     * @return list<array{slug: string, name: string, path: string, size: int}>
     */
    public function bundledPacks(): array
    {
        $dir = storage_path('app/plugin-packs');
        if (! File::isDirectory($dir)) {
            return [];
        }

        $packs = [];
        foreach (File::files($dir) as $file) {
            if (strtolower($file->getExtension()) !== 'zip') {
                continue;
            }
            $slug = $file->getFilenameWithoutExtension();
            $packs[] = [
                'slug' => $slug,
                'name' => Str::headline($slug),
                'path' => $file->getPathname(),
                'size' => $file->getSize(),
            ];
        }

        return $packs;
    }

    private function resolvePackageRoot(string $extracted): string
    {
        if (File::exists($extracted.DIRECTORY_SEPARATOR.'plugin.json')) {
            return $extracted;
        }

        foreach (File::directories($extracted) as $dir) {
            if (File::exists($dir.DIRECTORY_SEPARATOR.'plugin.json')) {
                return $dir;
            }
        }

        throw new \RuntimeException('Could not locate plugin.json in package.');
    }

    private function assertSafePluginTree(string $root): void
    {
        foreach (File::allFiles($root) as $file) {
            $ext = strtolower($file->getExtension());
            if (in_array($ext, ['phar', 'exe', 'sh', 'bat', 'cmd'], true)) {
                throw new \RuntimeException("Plugin packages may not include executable files (.{$ext}).");
            }
        }
    }
}
