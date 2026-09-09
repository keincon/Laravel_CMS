<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Seeders\AoyamaCardSiteSeeder;
use Illuminate\Console\Command;

class AoyamaCardSeedCommand extends Command
{
    protected $signature = 'laravelpress:seed-aoyama
        {--fresh : Soft-delete previous Aoyama demo slugs before seeding}';

    protected $description = 'Seed site content modeled after https://www.aoyama-card.co.jp/';

    public function handle(AoyamaCardSiteSeeder $seeder): int
    {
        $result = $seeder->seed(fresh: (bool) $this->option('fresh'));
        $this->info(sprintf(
            'Aoyama Card site seeded: pages=%d posts=%d terms=%d menu_items=%d media=%d',
            $result['pages'],
            $result['posts'],
            $result['terms'],
            $result['menu_items'],
            $result['media'] ?? 0,
        ));
        $this->line('Theme activated: aoyama. Site name set to 青山キャピタル.');
        $this->line('Seeded menus, header/footer builders, layout, widgets, and custom code.');

        return self::SUCCESS;
    }
}
