<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\InstallationService;
use App\Services\LaravelPressBootstrapService;
use App\Services\RolePermissionService;
use App\Services\WordPress\WordPressPluginManager;
use App\Support\Plugins\PluginManager;
use App\Support\Plugins\PluginPackageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PluginSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $dir = storage_path('app/cms');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($dir.'/installed.json', json_encode([
            'installed_at' => now()->toIso8601String(),
            'version' => '1.0.0',
        ]));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(InstallationService::class)->markInstalled('test@example.com');
        app(LaravelPressBootstrapService::class)->seedBuiltins();
        app(RolePermissionService::class)->syncDefaults();
        app(PluginManager::class)->syncDiskToDatabase();
    }

    #[Test]
    public function it_discovers_hello_plugin_and_activates(): void
    {
        $plugins = app(PluginManager::class);
        $all = $plugins->discover();
        $this->assertArrayHasKey('hello-laravelpress', $all);

        $plugins->activate('hello-laravelpress');
        $this->assertTrue($plugins->isActive('hello-laravelpress'));
        $this->assertDatabaseHas('plugins', [
            'slug' => 'hello-laravelpress',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function admin_can_open_plugins_page_and_scaffold(): void
    {
        $admin = User::factory()->create(['username' => 'plugin-admin']);
        $admin->assignRole('Administrator');

        $this->actingAs($admin)
            ->get(route('admin.plugins.index'))
            ->assertOk()
            ->assertSee('Hello LaravelPress');

        $slug = 'scaffoldplug';
        $path = base_path('plugins/'.$slug);
        try {
            $this->actingAs($admin)
                ->post(route('admin.plugins.scaffold'), [
                    'slug' => $slug,
                    'name' => 'Scaffold Plug',
                    'activate' => '1',
                ])
                ->assertRedirect()
                ->assertSessionHas('success');

            $this->assertDirectoryExists($path);
            $this->assertFileExists($path.'/plugin.json');
            $this->assertTrue(app(PluginManager::class)->isActive($slug));
        } finally {
            if (File::isDirectory($path)) {
                File::deleteDirectory($path);
            }
        }
    }

    #[Test]
    public function it_exports_and_imports_plugin_zip(): void
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive required');
        }

        $slug = 'packplug';
        $pluginDir = base_path('plugins/'.$slug);
        File::ensureDirectoryExists($pluginDir.'/src');
        File::put($pluginDir.'/plugin.json', json_encode([
            'name' => 'Pack Plug',
            'slug' => $slug,
            'version' => '1.0.0',
            'description' => 'temp',
        ], JSON_PRETTY_PRINT));
        File::put($pluginDir.'/hooks.php', "<?php\n");

        try {
            $packs = app(PluginPackageService::class);
            app(PluginManager::class)->discover();
            $zip = $packs->exportToZip($slug, storage_path('app/tmp/'.$slug.'-test.zip'));
            $this->assertFileExists($zip);

            File::deleteDirectory($pluginDir);
            app(PluginManager::class)->discover();
            $this->assertFalse(app(PluginManager::class)->has($slug));

            $result = $packs->importFromZip($zip, activate: true);
            $this->assertSame($slug, $result['slug']);
            $this->assertTrue(app(PluginManager::class)->has($slug));
            $this->assertTrue(app(PluginManager::class)->isActive($slug));
        } finally {
            if (File::isDirectory($pluginDir)) {
                File::deleteDirectory($pluginDir);
            }
            @unlink(storage_path('app/tmp/'.$slug.'-test.zip'));
        }
    }

    #[Test]
    public function wordpress_bridge_client_lists_plugins_when_mocked(): void
    {
        config([
            'wordpress.enabled' => true,
            'wordpress.base_url' => 'http://wp.test',
            'wordpress.bridge_token' => 'secret-token',
            'wordpress.bridge_path' => '/wp-json/laravelpress/v1',
        ]);

        Http::fake([
            'http://wp.test/wp-json/laravelpress/v1/status' => Http::response([
                'ok' => true,
                'data' => ['wordpress_version' => '6.7.0'],
            ], 200),
            'http://wp.test/wp-json/laravelpress/v1/plugins' => Http::response([
                'ok' => true,
                'data' => [
                    'plugins' => [
                        [
                            'file' => 'akismet/akismet.php',
                            'name' => 'Akismet',
                            'version' => '5.0',
                            'description' => 'Spam',
                            'author' => 'Automattic',
                            'active' => false,
                        ],
                    ],
                ],
            ], 200),
            'http://wp.test/wp-json/laravelpress/v1/plugins/activate' => Http::response([
                'ok' => true,
                'data' => ['plugin' => 'akismet/akismet.php'],
            ], 200),
        ]);

        $manager = app(WordPressPluginManager::class);
        $status = $manager->status();
        $this->assertTrue($status['reachable']);
        $this->assertSame('6.7.0', $status['wordpress_version']);

        $list = $manager->listPlugins();
        $this->assertCount(1, $list);
        $this->assertSame('Akismet', $list[0]['name']);

        $manager->activate('akismet/akismet.php');
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/plugins/activate')
                && $request['plugin'] === 'akismet/akismet.php';
        });
    }

    #[Test]
    public function author_cannot_access_plugins_admin(): void
    {
        $author = User::factory()->create(['username' => 'plugin-author']);
        $author->assignRole('Author');

        $this->actingAs($author)
            ->get(route('admin.plugins.index'))
            ->assertForbidden();
    }
}
