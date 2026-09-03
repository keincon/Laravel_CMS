<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\LaravelPressBootstrapService;
use App\Services\RolePermissionService;
use App\Services\Themes\ThemeManager;
use App\Services\Themes\ThemePackageService;
use Illuminate\Console\Command;

class LaravelPressInstallCommand extends Command
{
    protected $signature = 'laravelpress:install
        {--fresh-caps : Re-sync roles and capabilities}
        {--pack-themes : Build theme ZIP packs under storage/app/theme-packs}';

    protected $description = 'Seed LaravelPress builtins (content types, taxonomies) and optionally sync capabilities';

    public function handle(
        LaravelPressBootstrapService $bootstrap,
        RolePermissionService $roles,
        ThemeManager $themes,
        ThemePackageService $packs,
    ): int {
        $bootstrap->seedBuiltins();
        $this->info('Content types and taxonomies seeded.');

        $synced = $themes->syncDiskThemesToDatabase();
        $this->info("Synced {$synced} themes from disk.");

        if ($this->option('fresh-caps')) {
            $roles->syncDefaults();
            $this->info('Roles and capabilities synced.');
        }

        if ($this->option('pack-themes')) {
            $count = $packs->buildBundledPacksFromDisk();
            $this->info("Built {$count} theme packs.");
        }

        return self::SUCCESS;
    }
}
