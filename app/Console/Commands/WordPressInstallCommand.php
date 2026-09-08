<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class WordPressInstallCommand extends Command
{
    protected $signature = 'laravelpress:wp
        {action=status : status|hint}
        {--url= : Override WP_EMBED_URL shown in hints}';

    protected $description = 'WordPress embed helpers (status / setup hints)';

    public function handle(): int
    {
        $action = (string) $this->argument('action');

        return match ($action) {
            'status' => $this->status(),
            'hint' => $this->hint(),
            default => $this->invalid($action),
        };
    }

    private function status(): int
    {
        $enabled = (bool) config('wordpress.enabled');
        $url = (string) config('wordpress.base_url');
        $token = (string) config('wordpress.bridge_token');

        $this->table(['Key', 'Value'], [
            ['enabled', $enabled ? 'yes' : 'no'],
            ['base_url', $url ?: '(empty)'],
            ['bridge_token_set', $token !== '' ? 'yes' : 'no'],
        ]);

        if ($enabled && $token !== '') {
            $status = app(\App\Services\WordPress\WordPressPluginManager::class)->status();
            $this->info($status['reachable'] ? 'Bridge reachable.' : 'Bridge not reachable: '.$status['message']);
        }

        return self::SUCCESS;
    }

    private function hint(): int
    {
        $url = $this->option('url') ?: config('wordpress.base_url') ?: 'http://localhost:8080';
        $this->line('1. Start WordPress profile:');
        $this->line('   docker compose --profile wordpress up -d');
        $this->line('2. Open '.$url.' and finish the WordPress installer.');
        $this->line('3. Set in .env:');
        $this->line('   WP_EMBED_ENABLED=true');
        $this->line('   WP_EMBED_URL='.$url);
        $this->line('   WP_BRIDGE_TOKEN=laravelpress-dev-bridge-token');
        $this->line('4. Mu-plugin is mounted from wordpress-bridge/mu-plugins.');
        $this->line('5. Manage WP plugins from Admin → Plugins → WordPress tab.');

        return self::SUCCESS;
    }

    private function invalid(string $action): int
    {
        $this->error("Unknown action [{$action}]. Use status or hint.");

        return self::FAILURE;
    }
}
