<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\SettingsUpdated;
use App\Models\CmsSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Admin-managed CORS options stored in CmsSetting and applied to config('cors').
 */
final class CorsSettingsService
{
    public const SETTING_KEY = 'cors';

    /**
     * @return array{
     *     enabled: bool,
     *     paths: list<string>,
     *     allowed_origins: list<string>,
     *     allowed_origins_patterns: list<string>,
     *     allowed_methods: list<string>,
     *     allowed_headers: list<string>,
     *     exposed_headers: list<string>,
     *     max_age: int,
     *     supports_credentials: bool
     * }
     */
    public function defaults(): array
    {
        return [
            'enabled' => true,
            'paths' => ['api/*', 'sanctum/csrf-cookie'],
            'allowed_origins' => ['*'],
            'allowed_origins_patterns' => [],
            'allowed_methods' => ['*'],
            'allowed_headers' => ['*'],
            'exposed_headers' => [],
            'max_age' => 0,
            'supports_credentials' => false,
        ];
    }

    /**
     * @return array{
     *     enabled: bool,
     *     paths: list<string>,
     *     allowed_origins: list<string>,
     *     allowed_origins_patterns: list<string>,
     *     allowed_methods: list<string>,
     *     allowed_headers: list<string>,
     *     exposed_headers: list<string>,
     *     max_age: int,
     *     supports_credentials: bool
     * }
     */
    public function current(): array
    {
        $stored = CmsSetting::getValue(self::SETTING_KEY, null);
        if (! is_array($stored)) {
            return $this->defaults();
        }

        return array_replace($this->defaults(), [
            'enabled' => (bool) ($stored['enabled'] ?? true),
            'paths' => $this->normalizeList($stored['paths'] ?? null, $this->defaults()['paths']),
            'allowed_origins' => $this->normalizeList($stored['allowed_origins'] ?? null, ['*']),
            'allowed_origins_patterns' => $this->normalizeList($stored['allowed_origins_patterns'] ?? null, []),
            'allowed_methods' => $this->normalizeList($stored['allowed_methods'] ?? null, ['*']),
            'allowed_headers' => $this->normalizeList($stored['allowed_headers'] ?? null, ['*']),
            'exposed_headers' => $this->normalizeList($stored['exposed_headers'] ?? null, []),
            'max_age' => max(0, (int) ($stored['max_age'] ?? 0)),
            'supports_credentials' => (bool) ($stored['supports_credentials'] ?? false),
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     enabled: bool,
     *     paths: list<string>,
     *     allowed_origins: list<string>,
     *     allowed_origins_patterns: list<string>,
     *     allowed_methods: list<string>,
     *     allowed_headers: list<string>,
     *     exposed_headers: list<string>,
     *     max_age: int,
     *     supports_credentials: bool
     * }
     */
    public function update(array $input): array
    {
        $settings = [
            'enabled' => (bool) ($input['enabled'] ?? false),
            'paths' => $this->parseLines((string) ($input['paths'] ?? '')),
            'allowed_origins' => $this->parseLines((string) ($input['allowed_origins'] ?? '')),
            'allowed_origins_patterns' => $this->parseLines((string) ($input['allowed_origins_patterns'] ?? '')),
            'allowed_methods' => $this->parseMethods($input['allowed_methods'] ?? ['*']),
            'allowed_headers' => $this->parseLines((string) ($input['allowed_headers'] ?? '*')),
            'exposed_headers' => $this->parseLines((string) ($input['exposed_headers'] ?? '')),
            'max_age' => max(0, min(86400, (int) ($input['max_age'] ?? 0))),
            'supports_credentials' => (bool) ($input['supports_credentials'] ?? false),
        ];

        if ($settings['paths'] === []) {
            $settings['paths'] = $this->defaults()['paths'];
        }
        if ($settings['allowed_origins'] === []) {
            $settings['allowed_origins'] = ['*'];
        }
        if ($settings['allowed_methods'] === []) {
            $settings['allowed_methods'] = ['*'];
        }
        if ($settings['allowed_headers'] === []) {
            $settings['allowed_headers'] = ['*'];
        }

        $this->assertValid($settings);

        CmsSetting::setValue(self::SETTING_KEY, $settings, 'json');
        $this->applyToConfig($settings);
        event(new SettingsUpdated(self::SETTING_KEY));

        return $settings;
    }

    public function applyFromDatabase(): void
    {
        try {
            if (! Schema::hasTable('cms_settings')) {
                return;
            }
            $this->applyToConfig($this->current());
        } catch (\Throwable) {
            // Install / early boot without DB.
        }
    }

    /**
     * @param  array{
     *     enabled?: bool,
     *     paths: list<string>,
     *     allowed_origins: list<string>,
     *     allowed_origins_patterns: list<string>,
     *     allowed_methods: list<string>,
     *     allowed_headers: list<string>,
     *     exposed_headers: list<string>,
     *     max_age: int,
     *     supports_credentials: bool
     * }  $settings
     */
    public function applyToConfig(array $settings): void
    {
        $enabled = (bool) ($settings['enabled'] ?? true);

        config([
            'cors.paths' => $enabled ? array_values($settings['paths']) : [],
            'cors.allowed_methods' => array_values($settings['allowed_methods']),
            'cors.allowed_origins' => array_values($settings['allowed_origins']),
            'cors.allowed_origins_patterns' => array_values($settings['allowed_origins_patterns']),
            'cors.allowed_headers' => array_values($settings['allowed_headers']),
            'cors.exposed_headers' => array_values($settings['exposed_headers']),
            'cors.max_age' => (int) $settings['max_age'],
            'cors.supports_credentials' => (bool) $settings['supports_credentials'],
        ]);
    }

    /**
     * @param  array{
     *     allowed_origins: list<string>,
     *     supports_credentials: bool,
     *     allowed_methods: list<string>
     * }  $settings
     */
    private function assertValid(array $settings): void
    {
        if ($settings['supports_credentials'] && in_array('*', $settings['allowed_origins'], true)) {
            throw ValidationException::withMessages([
                'allowed_origins' => 'When credentials are enabled, Allowed Origins cannot be "*". List explicit origins (e.g. https://app.example.com).',
            ]);
        }

        foreach ($settings['allowed_origins'] as $origin) {
            if ($origin === '*') {
                continue;
            }
            if (! preg_match('#^https?://.+#i', $origin)) {
                throw ValidationException::withMessages([
                    'allowed_origins' => "Invalid origin [{$origin}]. Use * or a full http(s) URL.",
                ]);
            }
        }

        $allowedMethods = ['*', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'];
        foreach ($settings['allowed_methods'] as $method) {
            if (! in_array(strtoupper($method), $allowedMethods, true) && $method !== '*') {
                throw ValidationException::withMessages([
                    'allowed_methods' => "Invalid HTTP method [{$method}].",
                ]);
            }
        }
    }

    /**
     * @param  mixed  $value
     * @param  list<string>  $fallback
     * @return list<string>
     */
    private function normalizeList(mixed $value, array $fallback): array
    {
        if (is_string($value)) {
            $value = $this->parseLines($value);
        }
        if (! is_array($value)) {
            return $fallback;
        }

        $list = [];
        foreach ($value as $item) {
            $item = trim((string) $item);
            if ($item !== '') {
                $list[] = $item;
            }
        }

        return $list === [] ? $fallback : array_values(array_unique($list));
    }

    /**
     * @return list<string>
     */
    private function parseLines(string $raw): array
    {
        $parts = preg_split('/[\r\n,]+/', $raw) ?: [];
        $list = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                $list[] = $part;
            }
        }

        return array_values(array_unique($list));
    }

    /**
     * @param  mixed  $methods
     * @return list<string>
     */
    private function parseMethods(mixed $methods): array
    {
        if (is_string($methods)) {
            return array_map('strtoupper', $this->parseLines($methods));
        }
        if (! is_array($methods)) {
            return ['*'];
        }

        $list = [];
        foreach ($methods as $method) {
            $method = strtoupper(trim((string) $method));
            if ($method !== '') {
                $list[] = $method;
            }
        }

        return array_values(array_unique($list));
    }
}
