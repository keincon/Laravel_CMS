<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Content\LegacyContentMigrator;
use App\Services\Content\LegacyRetirementService;
use Illuminate\Console\Command;

class LegacyRetireCommand extends Command
{
    protected $signature = 'laravelpress:legacy
        {action=status : status|retire|restore}
        {--force : Skip confirmation on retire}';

    protected $description = 'Retire or inspect legacy posts/pages (soft retirement; tables retained)';

    public function handle(LegacyContentMigrator $migrator, LegacyRetirementService $legacy): int
    {
        return match ((string) $this->argument('action')) {
            'status' => $this->status($legacy),
            'retire' => $this->retire($migrator, $legacy),
            'restore' => $this->restore($legacy),
            default => $this->invalid(),
        };
    }

    private function status(LegacyRetirementService $legacy): int
    {
        $this->info('Legacy retired: '.($legacy->isRetired() ? 'yes' : 'no'));
        $this->line('dual_write: '.($legacy->dualWriteEnabled() ? 'on' : 'off'));
        $this->line('public_fallback: '.($legacy->publicFallbackEnabled() ? 'on' : 'off'));
        $this->line('admin_ui: '.($legacy->adminUiEnabled() ? 'on' : 'off'));
        $this->line('marker: '.$legacy->markerPath());

        return self::SUCCESS;
    }

    private function retire(LegacyContentMigrator $migrator, LegacyRetirementService $legacy): int
    {
        if (! $this->option('force') && ! $this->confirm('Migrate remaining legacy rows and retire posts/pages dual-write?', true)) {
            $this->warn('Aborted.');

            return self::FAILURE;
        }

        $result = $migrator->migrateAll();
        $legacy->markRetired([
            'migrated_posts' => $result['posts'],
            'migrated_pages' => $result['pages'],
        ]);

        $this->info("Migrated posts={$result['posts']} pages={$result['pages']}");
        $this->info('Legacy posts/pages retired (tables retained; dual-write and admin UI disabled).');

        return self::SUCCESS;
    }

    private function restore(LegacyRetirementService $legacy): int
    {
        $legacy->clearRetiredMarker();
        $this->warn('Retirement marker cleared. Set CMS_LEGACY_* env flags if you need dual-write again.');

        return self::SUCCESS;
    }

    private function invalid(): int
    {
        $this->error('Unknown action. Use status, retire, or restore.');

        return self::FAILURE;
    }
}
