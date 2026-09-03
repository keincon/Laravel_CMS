<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Themes\DemoContentSeeder;
use Illuminate\Console\Command;

class DemoSeedCommand extends Command
{
    protected $signature = 'laravelpress:demo-seed
        {--fresh : Soft-delete previous demo content slugs before seeding}';

    protected $description = 'Seed dummy posts, pages, terms, comments, and placeholder media';

    public function handle(DemoContentSeeder $seeder): int
    {
        $result = $seeder->seed(fresh: (bool) $this->option('fresh'));
        $this->info(sprintf(
            'Demo seeded: posts=%d pages=%d terms=%d comments=%d media=%d',
            $result['posts'],
            $result['pages'],
            $result['terms'],
            $result['comments'],
            $result['media'],
        ));

        return self::SUCCESS;
    }
}
