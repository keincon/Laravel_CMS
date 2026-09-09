<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\CmsSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CmsSettingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stores_and_reads_typed_values(): void
    {
        CmsSetting::setValue('flag_on', true, 'boolean');
        CmsSetting::setValue('count', 42, 'integer');
        CmsSetting::setValue('payload', ['a' => 1, 'b' => 'x'], 'json');
        CmsSetting::setValue('label', 'hello', 'string');

        $this->assertTrue(CmsSetting::getValue('flag_on'));
        $this->assertSame(42, CmsSetting::getValue('count'));
        $this->assertSame(['a' => 1, 'b' => 'x'], CmsSetting::getValue('payload'));
        $this->assertSame('hello', CmsSetting::getValue('label'));
        $this->assertSame('fallback', CmsSetting::getValue('missing', 'fallback'));
    }

    #[Test]
    public function set_value_clears_cache_entry(): void
    {
        CmsSetting::setValue('cached_key', 'one');
        $this->assertSame('one', CmsSetting::getValue('cached_key'));
        $this->assertTrue(Cache::has('cms_setting.cached_key'));

        CmsSetting::setValue('cached_key', 'two');
        $this->assertSame('two', CmsSetting::getValue('cached_key'));
    }
}
