<?php

declare(strict_types=1);

namespace App\Services\Content;

use Illuminate\Support\Facades\File;

/**
 * Soft-retirement of legacy posts/pages tables (least-destructive).
 * Tables remain; dual-write and public/admin fallbacks are disabled.
 */
final class LegacyRetirementService
{
    public function markerPath(): string
    {
        return storage_path('app/cms/legacy-retired.json');
    }

    public function isRetired(): bool
    {
        return (bool) config('cms.legacy.retired', false)
            || File::exists($this->markerPath());
    }

    public function dualWriteEnabled(): bool
    {
        if ($this->isRetired()) {
            return false;
        }

        return (bool) config('cms.legacy.dual_write', false);
    }

    public function publicFallbackEnabled(): bool
    {
        if ($this->isRetired()) {
            return false;
        }

        return (bool) config('cms.legacy.public_fallback', false);
    }

    public function adminUiEnabled(): bool
    {
        if ($this->isRetired()) {
            return false;
        }

        return (bool) config('cms.legacy.admin_ui', false);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function markRetired(array $meta = []): void
    {
        $dir = dirname($this->markerPath());
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        File::put($this->markerPath(), json_encode(array_merge([
            'retired_at' => now()->toIso8601String(),
            'version' => '1.0.0',
            'tables_retained' => true,
            'note' => 'Legacy posts/pages admin + dual-write disabled; tables retained.',
        ], $meta), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        config(['cms.legacy.retired' => true]);
    }

    public function clearRetiredMarker(): void
    {
        if (File::exists($this->markerPath())) {
            File::delete($this->markerPath());
        }

        config([
            'cms.legacy.retired' => filter_var(env('CMS_LEGACY_RETIRED', false), FILTER_VALIDATE_BOOLEAN),
            'cms.legacy.dual_write' => filter_var(env('CMS_LEGACY_DUAL_WRITE', false), FILTER_VALIDATE_BOOLEAN),
            'cms.legacy.public_fallback' => filter_var(env('CMS_LEGACY_PUBLIC_FALLBACK', false), FILTER_VALIDATE_BOOLEAN),
            'cms.legacy.admin_ui' => filter_var(env('CMS_LEGACY_ADMIN_UI', false), FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
