<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\Seeders\AoyamaCardSiteSeeder;
use Illuminate\Database\Seeder;

/**
 * Seeds content modeled after https://www.aoyama-card.co.jp/
 *
 * Prefer: php artisan laravelpress:seed-aoyama [--fresh]
 */
class AoyamaCardSeeder extends Seeder
{
    public function run(): void
    {
        $result = app(AoyamaCardSiteSeeder::class)->seed(fresh: false);

        $this->command?->info(sprintf(
            'Aoyama Card site seeded: pages=%d posts=%d terms=%d menu_items=%d',
            $result['pages'],
            $result['posts'],
            $result['terms'],
            $result['menu_items'],
        ));
    }
}
