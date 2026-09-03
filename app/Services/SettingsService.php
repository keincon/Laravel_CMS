<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CmsSetting;
use App\Support\Hooks\Hooks;
use Illuminate\Support\Facades\Cache;

/**
 * Cached site settings with autoload group support.
 */
final class SettingsService
{
    private const AUTOLOAD_KEY = 'cms.settings.autoload';

    /** @var array<string, mixed>|null */
    private ?array $autoload = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->autoload();
        if (array_key_exists($key, $all)) {
            return $all[$key];
        }

        return CmsSetting::getValue($key, $default);
    }

    public function set(string $key, mixed $value, string $type = 'string', string $group = 'general', bool $autoload = true): void
    {
        $stored = match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => is_string($value) ? $value : json_encode($value),
            'integer' => (string) (int) $value,
            default => (string) $value,
        };

        CmsSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $stored,
                'type' => $type,
                'group' => $group,
                'autoload' => $autoload,
            ]
        );

        $this->forget($key);
        Hooks::action('settings.updated', $key, $value);
        event(new \App\Events\SettingsUpdated($key));
    }

    /**
     * @return array<string, mixed>
     */
    public function autoload(): array
    {
        if ($this->autoload !== null) {
            return $this->autoload;
        }

        $this->autoload = Cache::remember(self::AUTOLOAD_KEY, 3600, function (): array {
            $out = [];
            try {
                $query = CmsSetting::query();
                if (\Illuminate\Support\Facades\Schema::hasColumn('cms_settings', 'autoload')) {
                    $query->where('autoload', true);
                }
                foreach ($query->get() as $row) {
                    $out[$row->key] = $this->cast($row);
                }
            } catch (\Throwable) {
                return [];
            }

            return $out;
        });

        return $this->autoload;
    }

    /**
     * @return array<string, mixed>
     */
    public function group(string $group): array
    {
        $out = [];
        try {
            $query = CmsSetting::query()->where('group', $group);
            foreach ($query->get() as $row) {
                $out[$row->key] = $this->cast($row);
            }
        } catch (\Throwable) {
            return [];
        }

        return $out;
    }

    public function forget(?string $key = null): void
    {
        $this->autoload = null;
        Cache::forget(self::AUTOLOAD_KEY);
        if ($key !== null) {
            Cache::forget("cms_setting.{$key}");
        }
    }

    private function cast(CmsSetting $setting): mixed
    {
        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            'json' => json_decode((string) $setting->value, true),
            default => $setting->value,
        };
    }
}
