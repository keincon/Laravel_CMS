<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Content\LegacyContentMigrator;
use App\Services\ImportExport\ContentPackService;
use App\Services\ImportExport\WxrImportService;
use Illuminate\Console\Command;

class ContentPackCommand extends Command
{
    protected $signature = 'laravelpress:content
        {action : export|import|migrate-legacy|import-wxr}
        {--path=exports/content-pack.json : Pack/WXR path on the local disk}
        {--dry-run : Validate import without writing}';

    protected $description = 'Export/import LaravelPress content packs, WXR, or migrate legacy posts/pages';

    public function handle(
        ContentPackService $packs,
        LegacyContentMigrator $migrator,
        WxrImportService $wxr,
    ): int {
        $action = (string) $this->argument('action');
        $path = (string) $this->option('path');

        return match ($action) {
            'export' => $this->export($packs, $path),
            'import' => $this->import($packs, $path, (bool) $this->option('dry-run')),
            'migrate-legacy' => $this->migrate($migrator),
            'import-wxr' => $this->importWxr($wxr, $path, (bool) $this->option('dry-run')),
            default => $this->invalid($action),
        };
    }

    private function export(ContentPackService $packs, string $path): int
    {
        $result = $packs->exportToDisk('local', $path);
        $this->info("Exported {$result['count']} items to {$result['path']}");

        return self::SUCCESS;
    }

    private function import(ContentPackService $packs, string $path, bool $dryRun): int
    {
        $result = $packs->importFromDisk($path, 'local', $dryRun);
        $prefix = $dryRun ? '[dry-run] ' : '';
        $this->info("{$prefix}Imported {$result['imported']}, skipped {$result['skipped']}");

        return self::SUCCESS;
    }

    private function importWxr(WxrImportService $wxr, string $path, bool $dryRun): int
    {
        $result = $wxr->importFromDisk($path, 'local', $dryRun);
        $prefix = $dryRun ? '[dry-run] ' : '';
        $this->info("{$prefix}WXR imported={$result['imported']} skipped={$result['skipped']} authors={$result['authors']} terms={$result['terms']} media={$result['media']}");
        foreach ($result['warnings'] as $warning) {
            $this->warn($warning);
        }

        return self::SUCCESS;
    }

    private function migrate(LegacyContentMigrator $migrator): int
    {
        $result = $migrator->migrateAll();
        $this->info("Migrated posts={$result['posts']} pages={$result['pages']}");

        return self::SUCCESS;
    }

    private function invalid(string $action): int
    {
        $this->error("Unknown action [{$action}]. Use export, import, import-wxr, or migrate-legacy.");

        return self::FAILURE;
    }
}
