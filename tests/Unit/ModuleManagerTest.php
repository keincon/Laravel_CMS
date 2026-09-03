<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Modules\ModuleManager;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModuleManagerTest extends TestCase
{
    #[Test]
    public function it_discovers_and_toggles_modules_on_disk(): void
    {
        $base = storage_path('framework/testing/modules-'.uniqid());
        File::ensureDirectoryExists($base.'/Demo');
        File::put($base.'/Demo/module.json', json_encode([
            'name' => 'Demo',
            'version' => '0.1.0',
            'description' => 'Test module',
            'enabled' => false,
            'provider' => 'DoesNotExist\\Provider',
        ], JSON_PRETTY_PRINT));

        $manager = new ModuleManager($base);
        $discovered = $manager->discover();
        $this->assertArrayHasKey('Demo', $discovered);
        $this->assertFalse($manager->isEnabled('Demo'));

        $manager->setEnabled('Demo', true);
        $this->assertTrue($manager->isEnabled('Demo'));

        $reloaded = json_decode(File::get($base.'/Demo/module.json'), true);
        $this->assertTrue($reloaded['enabled']);

        File::deleteDirectory($base);
    }
}
