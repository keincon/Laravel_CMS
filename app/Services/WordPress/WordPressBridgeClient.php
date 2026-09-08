<?php

declare(strict_types=1);

namespace App\Services\WordPress;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * HTTP client for the LaravelPress mu-plugin bridge inside WordPress.
 */
final class WordPressBridgeClient
{
    public function enabled(): bool
    {
        return (bool) config('wordpress.enabled')
            && (string) config('wordpress.bridge_token') !== ''
            && (string) config('wordpress.base_url') !== '';
    }

    public function baseUrl(): string
    {
        return (string) config('wordpress.base_url');
    }

    public function adminUrl(): string
    {
        return (string) config('wordpress.admin_url', $this->baseUrl().'/wp-admin');
    }

    /**
     * @return array{ok: bool, message: string, wordpress_version?: string}
     */
    public function ping(): array
    {
        if (! $this->enabled()) {
            return [
                'ok' => false,
                'message' => 'WordPress embed is disabled. Set WP_EMBED_ENABLED=true and WP_BRIDGE_TOKEN.',
            ];
        }

        try {
            $response = $this->request('get', '/status');
            if (! $response['ok']) {
                return [
                    'ok' => false,
                    'message' => $response['message'] ?? 'Bridge unreachable',
                ];
            }

            return [
                'ok' => true,
                'message' => 'Connected',
                'wordpress_version' => (string) ($response['data']['wordpress_version'] ?? ''),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPlugins(): array
    {
        $response = $this->request('get', '/plugins');
        if (! $response['ok']) {
            throw new RuntimeException($response['message'] ?? 'Failed to list WordPress plugins.');
        }

        $plugins = $response['data']['plugins'] ?? [];

        return is_array($plugins) ? array_values($plugins) : [];
    }

    public function activatePlugin(string $pluginFile): void
    {
        $response = $this->request('post', '/plugins/activate', ['plugin' => $pluginFile]);
        if (! $response['ok']) {
            throw new RuntimeException($response['message'] ?? 'Activate failed.');
        }
    }

    public function deactivatePlugin(string $pluginFile): void
    {
        $response = $this->request('post', '/plugins/deactivate', ['plugin' => $pluginFile]);
        if (! $response['ok']) {
            throw new RuntimeException($response['message'] ?? 'Deactivate failed.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function installPluginZip(string $zipPath, bool $activate = false): array
    {
        if (! is_file($zipPath)) {
            throw new RuntimeException('ZIP not found.');
        }

        $http = Http::timeout((int) config('wordpress.timeout', 15))
            ->withHeaders([
                'X-LaravelPress-Token' => (string) config('wordpress.bridge_token'),
                'Accept' => 'application/json',
            ])
            ->attach('package', file_get_contents($zipPath) ?: '', basename($zipPath))
            ->post($this->endpoint('/plugins/install'), [
                'activate' => $activate ? '1' : '0',
            ]);

        $json = $http->json() ?? [];
        if (! $http->successful() || empty($json['ok'])) {
            throw new RuntimeException((string) ($json['message'] ?? 'Install failed (HTTP '.$http->status().').'));
        }

        return is_array($json['data'] ?? null) ? $json['data'] : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, message?: string, data?: array<string, mixed>}
     */
    private function request(string $method, string $path, array $payload = []): array
    {
        if (! $this->enabled()) {
            return ['ok' => false, 'message' => 'WordPress bridge not configured.'];
        }

        $http = Http::timeout((int) config('wordpress.timeout', 15))
            ->withHeaders([
                'X-LaravelPress-Token' => (string) config('wordpress.bridge_token'),
                'Accept' => 'application/json',
            ]);

        $url = $this->endpoint($path);
        $response = strtolower($method) === 'get'
            ? $http->get($url, $payload)
            : $http->asJson()->post($url, $payload);

        $json = $response->json();
        if (! is_array($json)) {
            return [
                'ok' => false,
                'message' => 'Invalid bridge response (HTTP '.$response->status().').',
            ];
        }

        if (! $response->successful()) {
            return [
                'ok' => false,
                'message' => (string) ($json['message'] ?? 'HTTP '.$response->status()),
                'data' => is_array($json['data'] ?? null) ? $json['data'] : [],
            ];
        }

        return [
            'ok' => (bool) ($json['ok'] ?? true),
            'message' => (string) ($json['message'] ?? ''),
            'data' => is_array($json['data'] ?? null) ? $json['data'] : $json,
        ];
    }

    private function endpoint(string $path): string
    {
        $base = rtrim($this->baseUrl(), '/');
        $prefix = rtrim((string) config('wordpress.bridge_path', '/wp-json/laravelpress/v1'), '/');

        return $base.$prefix.'/'.ltrim($path, '/');
    }
}
