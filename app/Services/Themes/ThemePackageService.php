<?php

declare(strict_types=1);

namespace App\Services\Themes;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Import/export LaravelPress theme packages (ZIP + theme.json).
 */
final class ThemePackageService
{
    public function __construct(private readonly ThemeManager $themes) {}

    /**
     * @return list<array{slug: string, name: string, version: string, path: string, size: int}>
     */
    public function bundledPacks(): array
    {
        $dir = storage_path('app/theme-packs');
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
                'version' => '1.0.0',
                'path' => $file->getPathname(),
                'size' => $file->getSize(),
            ];
        }

        return $packs;
    }

    public function exportToZip(string $slug, ?string $destination = null): string
    {
        $theme = $this->themes->get($slug);
        if ($theme === null) {
            throw new \InvalidArgumentException("Theme [{$slug}] not found on disk.");
        }

        $source = (string) $theme['path'];
        $destination ??= storage_path('app/theme-packs/'.$slug.'.zip');
        File::ensureDirectoryExists(dirname($destination));

        if (File::exists($destination)) {
            File::delete($destination);
        }

        $zip = new ZipArchive;
        if ($zip->open($destination, ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('Unable to create theme package ZIP.');
        }

        $files = File::allFiles($source);
        foreach ($files as $file) {
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
            throw new \InvalidArgumentException('Theme package not found.');
        }

        $tmp = storage_path('app/tmp/theme-import-'.Str::random(8));
        File::ensureDirectoryExists($tmp);

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Unable to open theme package ZIP.');
        }
        $zip->extractTo($tmp);
        $zip->close();

        try {
            $root = $this->resolvePackageRoot($tmp);
            $manifestPath = $root.DIRECTORY_SEPARATOR.'theme.json';
            if (! File::exists($manifestPath)) {
                throw new \RuntimeException('theme.json missing from package.');
            }

            $manifest = json_decode(File::get($manifestPath), true);
            if (! is_array($manifest)) {
                throw new \RuntimeException('Invalid theme.json.');
            }

            $slug = Str::slug((string) ($manifest['slug'] ?? basename($root)));
            if ($slug === '' || $slug === 'default') {
                // Allow updating default only when package explicitly says so.
                if (($manifest['slug'] ?? '') !== 'default') {
                    throw new \RuntimeException('Invalid theme slug.');
                }
                $slug = 'default';
            }

            $this->assertSafeThemeTree($root);

            $target = $this->themes->themesPath().DIRECTORY_SEPARATOR.$slug;
            if (File::isDirectory($target)) {
                File::deleteDirectory($target);
            }
            File::copyDirectory($root, $target);

            // Ensure slug in manifest matches folder.
            $manifest['slug'] = $slug;
            File::put(
                $target.DIRECTORY_SEPARATOR.'theme.json',
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
            );

            $this->themes->discover();
            $this->themes->syncDiskThemesToDatabase();

            if ($activate) {
                $this->themes->activate($slug);
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
        foreach (array_keys($this->themes->discover()) as $slug) {
            $this->exportToZip($slug);
            $count++;
        }

        return $count;
    }

    private function resolvePackageRoot(string $extracted): string
    {
        if (File::exists($extracted.DIRECTORY_SEPARATOR.'theme.json')) {
            return $extracted;
        }

        foreach (File::directories($extracted) as $dir) {
            if (File::exists($dir.DIRECTORY_SEPARATOR.'theme.json')) {
                return $dir;
            }
        }

        throw new \RuntimeException('Could not locate theme.json in package.');
    }

    private function assertSafeThemeTree(string $root): void
    {
        foreach (File::allFiles($root) as $file) {
            $name = strtolower($file->getFilename());
            // Blade screens are allowed (.blade.php). Raw PHP / shells are not.
            if (str_ends_with($name, '.blade.php')) {
                continue;
            }

            $ext = strtolower($file->getExtension());
            if (in_array($ext, ['php', 'phtml', 'phar', 'exe', 'sh', 'bat', 'cmd'], true)) {
                throw new \RuntimeException("Theme packages may not include executable files (.{$ext}). Use .blade.php for screens.");
            }
        }
    }
}
