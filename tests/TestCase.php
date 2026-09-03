<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        Cache::flush();
        $this->withoutMiddleware(PreventRequestForgery::class);

        // Reset legacy flags each test (markRetired() mutates config + marker file).
        $marker = storage_path('app/cms/legacy-retired.json');
        if (is_file($marker)) {
            @unlink($marker);
        }
        config([
            'cms.legacy.retired' => filter_var(env('CMS_LEGACY_RETIRED', false), FILTER_VALIDATE_BOOLEAN),
            'cms.legacy.dual_write' => filter_var(env('CMS_LEGACY_DUAL_WRITE', false), FILTER_VALIDATE_BOOLEAN),
            'cms.legacy.public_fallback' => filter_var(env('CMS_LEGACY_PUBLIC_FALLBACK', false), FILTER_VALIDATE_BOOLEAN),
            'cms.legacy.admin_ui' => filter_var(env('CMS_LEGACY_ADMIN_UI', false), FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
