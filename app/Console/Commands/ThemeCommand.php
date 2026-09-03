<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Themes\ThemeManager;
use App\Services\Themes\ThemePackageService;
use Illuminate\Console\Command;

class ThemeCommand extends Command
{
    protected $signature = 'laravelpress:theme
        {action : list|activate|export|import|pack|sync|scaffold}
        {slug? : Theme slug for activate/export/import/scaffold}
        {--path= : ZIP path for import, or destination for export}
        {--name= : Display name when scaffolding}
        {--activate : Activate after import or scaffold}';

    protected $description = 'List, activate, export, import, pack, sync, or scaffold LaravelPress themes';

    public function handle(ThemeManager $themes, ThemePackageService $packs): int
    {
        $action = (string) $this->argument('action');
        $slug = $this->argument('slug');

        return match ($action) {
            'list' => $this->listThemes($themes),
            'activate' => $this->activate($themes, (string) $slug),
            'export' => $this->export($packs, (string) $slug),
            'import' => $this->import($packs),
            'pack' => $this->pack($packs),
            'sync' => $this->sync($themes),
            'scaffold' => $this->scaffold($themes, (string) $slug),
            default => $this->invalid($action),
        };
    }

    private function listThemes(ThemeManager $themes): int
    {
        $active = $themes->activeSlug();
        $rows = [];
        foreach ($themes->discover() as $slug => $manifest) {
            $rows[] = [
                $slug,
                (string) ($manifest['name'] ?? $slug),
                (string) ($manifest['version'] ?? '1.0.0'),
                count($manifest['screens'] ?? []),
                count($manifest['stylesheets'] ?? []),
                count($manifest['scripts'] ?? []),
                $slug === $active ? 'yes' : '',
            ];
        }
        $this->table(['Slug', 'Name', 'Version', 'Screens', 'CSS', 'JS', 'Active'], $rows);

        return self::SUCCESS;
    }

    private function activate(ThemeManager $themes, string $slug): int
    {
        if ($slug === '') {
            $this->error('Provide a theme slug.');

            return self::FAILURE;
        }
        $themes->activate($slug);
        $this->info("Activated theme [{$slug}].");

        return self::SUCCESS;
    }

    private function export(ThemePackageService $packs, string $slug): int
    {
        if ($slug === '') {
            $this->error('Provide a theme slug.');

            return self::FAILURE;
        }
        $dest = $this->option('path') ?: null;
        $path = $packs->exportToZip($slug, $dest ? (string) $dest : null);
        $this->info("Exported to {$path}");

        return self::SUCCESS;
    }

    private function import(ThemePackageService $packs): int
    {
        $path = (string) $this->option('path');
        if ($path === '' || ! is_file($path)) {
            $this->error('Provide --path to an existing ZIP.');

            return self::FAILURE;
        }
        $result = $packs->importFromZip($path, (bool) $this->option('activate'));
        $this->info("Imported theme [{$result['slug']}] at {$result['path']}");

        return self::SUCCESS;
    }

    private function pack(ThemePackageService $packs): int
    {
        $count = $packs->buildBundledPacksFromDisk();
        $this->info("Built {$count} packs in storage/app/theme-packs/");

        return self::SUCCESS;
    }

    private function sync(ThemeManager $themes): int
    {
        $count = $themes->syncDiskThemesToDatabase();
        $this->info("Synced {$count} themes to the database.");

        return self::SUCCESS;
    }

    private function scaffold(ThemeManager $themes, string $slug): int
    {
        if ($slug === '') {
            $this->error('Provide a theme slug to scaffold.');

            return self::FAILURE;
        }

        try {
            $result = $themes->scaffold($slug, $this->option('name') ? (string) $this->option('name') : null);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('activate')) {
            $themes->activate($result['slug']);
        }

        $this->info("Scaffolded theme [{$result['slug']}] at {$result['path']}");
        $this->line('Add screens under pages/ and dynamic/, assets under assets/ (CSS, JS, images).');

        return self::SUCCESS;
    }

    private function invalid(string $action): int
    {
        $this->error("Unknown action [{$action}]. Use list, activate, export, import, pack, sync, or scaffold.");

        return self::FAILURE;
    }
}
