<?php

declare(strict_types=1);

namespace App\Services\WordPress;

/**
 * Manages WordPress plugins via the embedded WP bridge (never loads WP PHP in Laravel).
 */
final class WordPressPluginManager
{
    public function __construct(private readonly WordPressBridgeClient $bridge) {}

    /**
     * @return array{
     *     enabled: bool,
     *     reachable: bool,
     *     message: string,
     *     base_url: string,
     *     admin_url: string,
     *     wordpress_version: string|null
     * }
     */
    public function status(): array
    {
        $ping = $this->bridge->ping();

        return [
            'enabled' => $this->bridge->enabled(),
            'reachable' => (bool) ($ping['ok'] ?? false),
            'message' => (string) ($ping['message'] ?? ''),
            'base_url' => $this->bridge->baseUrl(),
            'admin_url' => $this->bridge->adminUrl(),
            'wordpress_version' => $ping['wordpress_version'] ?? null,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPlugins(): array
    {
        if (! $this->bridge->enabled()) {
            return [];
        }

        try {
            return $this->bridge->listPlugins();
        } catch (\Throwable) {
            return [];
        }
    }

    public function activate(string $pluginFile): void
    {
        $this->bridge->activatePlugin($pluginFile);
    }

    public function deactivate(string $pluginFile): void
    {
        $this->bridge->deactivatePlugin($pluginFile);
    }

    /**
     * @return array<string, mixed>
     */
    public function installFromZip(string $zipPath, bool $activate = false): array
    {
        return $this->bridge->installPluginZip($zipPath, $activate);
    }
}
