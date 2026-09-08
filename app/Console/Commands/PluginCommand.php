<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Plugins\PluginManager;
use App\Support\Plugins\PluginPackageService;
use App\Support\Plugins\PluginScaffoldService;
use Illuminate\Console\Command;

class PluginCommand extends Command
{
    protected $signature = 'laravelpress:plugin
        {action : list|activate|deactivate|scaffold|export|import|pack|sync}
        {slug? : Plugin slug}
        {--path= : ZIP path for import or export destination}
        {--name= : Display name when scaffolding}
        {--activate : Activate after import or scaffold}';

    protected $description = 'Manage LaravelPress plugins (list, activate, scaffold, import/export)';

    public function handle(
        PluginManager $plugins,
        PluginPackageService $packs,
        PluginScaffoldService $scaffold,
    ): int {
        $action = (string) $this->argument('action');
        $slug = (string) ($this->argument('slug') ?? '');

        return match ($action) {
            'list' => $this->listPlugins($plugins),
            'activate' => $this->activate($plugins, $slug),
            'deactivate' => $this->deactivate($plugins, $slug),
            'scaffold' => $this->scaffold($scaffold, $plugins, $slug),
            'export' => $this->export($packs, $slug),
            'import' => $this->import($packs),
            'pack' => $this->pack($packs),
            'sync' => $this->sync($plugins),
            default => $this->invalid($action),
        };
    }

    private function listPlugins(PluginManager $plugins): int
    {
        $rows = [];
        foreach ($plugins->discover() as $slug => $manifest) {
            $rows[] = [
                $slug,
                (string) ($manifest['name'] ?? $slug),
                (string) ($manifest['version'] ?? '1.0.0'),
                ! empty($manifest['is_active']) ? 'yes' : '',
            ];
        }
        $this->table(['Slug', 'Name', 'Version', 'Active'], $rows);

        return self::SUCCESS;
    }

    private function activate(PluginManager $plugins, string $slug): int
    {
        if ($slug === '') {
            $this->error('Provide a plugin slug.');

            return self::FAILURE;
        }
        $plugins->activate($slug);
        $this->info("Activated plugin [{$slug}].");

        return self::SUCCESS;
    }

    private function deactivate(PluginManager $plugins, string $slug): int
    {
        if ($slug === '') {
            $this->error('Provide a plugin slug.');

            return self::FAILURE;
        }
        $plugins->deactivate($slug);
        $this->info("Deactivated plugin [{$slug}].");

        return self::SUCCESS;
    }

    private function scaffold(PluginScaffoldService $scaffold, PluginManager $plugins, string $slug): int
    {
        if ($slug === '') {
            $this->error('Provide a plugin slug to scaffold.');

            return self::FAILURE;
        }
        try {
            $result = $scaffold->scaffold($slug, $this->option('name') ? (string) $this->option('name') : null);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        if ($this->option('activate')) {
            $plugins->activate($result['slug']);
        }
        $this->info("Scaffolded plugin [{$result['slug']}] at {$result['path']}");

        return self::SUCCESS;
    }

    private function export(PluginPackageService $packs, string $slug): int
    {
        if ($slug === '') {
            $this->error('Provide a plugin slug.');

            return self::FAILURE;
        }
        $dest = $this->option('path') ?: null;
        $path = $packs->exportToZip($slug, $dest ? (string) $dest : null);
        $this->info("Exported to {$path}");

        return self::SUCCESS;
    }

    private function import(PluginPackageService $packs): int
    {
        $path = (string) $this->option('path');
        if ($path === '' || ! is_file($path)) {
            $this->error('Provide --path to an existing ZIP.');

            return self::FAILURE;
        }
        $result = $packs->importFromZip($path, (bool) $this->option('activate'));
        $this->info("Imported plugin [{$result['slug']}] at {$result['path']}");

        return self::SUCCESS;
    }

    private function pack(PluginPackageService $packs): int
    {
        $count = $packs->buildBundledPacksFromDisk();
        $this->info("Built {$count} packs in storage/app/plugin-packs/");

        return self::SUCCESS;
    }

    private function sync(PluginManager $plugins): int
    {
        $count = $plugins->syncDiskToDatabase();
        $this->info("Synced {$count} plugins to the database.");

        return self::SUCCESS;
    }

    private function invalid(string $action): int
    {
        $this->error("Unknown action [{$action}].");

        return self::FAILURE;
    }
}
