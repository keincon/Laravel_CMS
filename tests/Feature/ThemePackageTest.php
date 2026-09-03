<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\LaravelPressBootstrapService;
use App\Services\RolePermissionService;
use App\Services\Themes\DemoContentSeeder;
use App\Services\Themes\ThemeManager;
use App\Services\Themes\ThemePackageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ThemePackageTest extends TestCase
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

        app(LaravelPressBootstrapService::class)->seedBuiltins();
        app(RolePermissionService::class)->syncDefaults();
        app(ThemeManager::class)->syncDiskThemesToDatabase();
    }

    #[Test]
    public function it_discovers_bundled_themes_and_activates(): void
    {
        $themes = app(ThemeManager::class);
        $all = $themes->discover();

        $this->assertArrayHasKey('default', $all);
        $this->assertArrayHasKey('aurora', $all);
        $this->assertArrayHasKey('meadow', $all);
        $this->assertArrayHasKey('ink', $all);
        $this->assertArrayHasKey('paper', $all);
        $this->assertArrayHasKey('harbor', $all);

        $themes->activate('aurora');
        $this->assertSame('aurora', $themes->activeSlug());
        $this->assertDatabaseHas('themes', ['slug' => 'aurora', 'is_active' => true]);
    }

    #[Test]
    public function it_exports_and_reimports_a_theme_zip(): void
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive extension required');
        }

        $slug = 'packtest';
        $themeDir = resource_path('views/themes/'.$slug);
        File::ensureDirectoryExists($themeDir.'/assets');
        File::ensureDirectoryExists($themeDir.'/dynamic');
        File::put($themeDir.'/theme.json', json_encode([
            'name' => 'Pack Test',
            'slug' => $slug,
            'version' => '1.0.0',
            'description' => 'Temporary theme for package tests',
            'color_mode' => 'light',
            'stylesheets' => ['theme.css'],
            'scripts' => ['theme.js'],
            'colors' => [
                'primary_color' => '#0D9488',
                'secondary_color' => '#38BDF8',
                'accent_color' => '#0284C7',
                'success_color' => '#16A34A',
                'warning_color' => '#D97706',
                'danger_color' => '#DC2626',
                'info_color' => '#0891B2',
                'background_color' => '#F0FDFA',
                'surface_color' => '#FFFFFF',
                'text_color' => '#134E4A',
            ],
        ], JSON_PRETTY_PRINT));
        File::put($themeDir.'/assets/theme.css', "body.theme-{$slug} { color: #134E4A; }\n");
        File::put($themeDir.'/assets/theme.js', "document.documentElement.classList.add('theme-{$slug}-ready');\n");
        File::put($themeDir.'/dynamic/blog.blade.php', "<div>Packtest blog screen</div>\n");

        try {
            $packs = app(ThemePackageService::class);
            app(ThemeManager::class)->discover();
            $zip = $packs->exportToZip($slug, storage_path('app/tmp/'.$slug.'-test.zip'));
            $this->assertFileExists($zip);

            File::deleteDirectory($themeDir);
            app(ThemeManager::class)->discover();
            $this->assertFalse(app(ThemeManager::class)->has($slug));

            $result = $packs->importFromZip($zip, activate: true);
            $this->assertSame($slug, $result['slug']);
            $this->assertTrue(app(ThemeManager::class)->has($slug));
            $this->assertSame($slug, app(ThemeManager::class)->activeSlug());
            $this->assertFileExists(resource_path('views/themes/'.$slug.'/theme.json'));
            $this->assertFileExists(resource_path('views/themes/'.$slug.'/dynamic/blog.blade.php'));
            $this->assertFileExists(resource_path('views/themes/'.$slug.'/assets/theme.js'));
            $this->assertNotEmpty(app(ThemeManager::class)->scriptUrls($slug));
        } finally {
            if (File::isDirectory($themeDir)) {
                File::deleteDirectory($themeDir);
            }
            @unlink(storage_path('app/tmp/'.$slug.'-test.zip'));
        }
    }

    #[Test]
    public function admin_can_scaffold_a_theme_with_screens_css_js(): void
    {
        $admin = User::factory()->create(['username' => 'theme-scaffold']);
        $admin->assignRole('Administrator');
        $slug = 'scaffolddemo';
        $themeDir = resource_path('views/themes/'.$slug);

        try {
            $this->actingAs($admin)
                ->post(route('admin.appearance.themes.scaffold'), [
                    'slug' => $slug,
                    'name' => 'Scaffold Demo',
                ])
                ->assertRedirect()
                ->assertSessionHas('success');

            $this->assertDirectoryExists($themeDir);
            $this->assertFileExists($themeDir.'/pages/default.blade.php');
            $this->assertFileExists($themeDir.'/dynamic/blog.blade.php');
            $this->assertFileExists($themeDir.'/assets/theme.css');
            $this->assertFileExists($themeDir.'/assets/theme.js');

            $manifest = app(ThemeManager::class)->discover()[$slug] ?? null;
            $this->assertNotNull($manifest);
            $this->assertNotEmpty($manifest['screens']);
            $this->assertContains('theme.css', $manifest['stylesheets']);
            $this->assertContains('theme.js', $manifest['scripts']);
        } finally {
            if (File::isDirectory($themeDir)) {
                File::deleteDirectory($themeDir);
            }
        }
    }

    #[Test]
    public function aurora_exposes_stylesheet_and_script_urls(): void
    {
        $themes = app(ThemeManager::class);
        $this->assertNotEmpty($themes->stylesheetUrls('aurora'));
        $this->assertNotEmpty($themes->scriptUrls('aurora'));
        $this->get(route('theme.asset', ['theme' => 'aurora', 'path' => 'theme.js']))
            ->assertOk();
    }

    #[Test]
    public function admin_can_open_themes_screen_and_demo_seed_runs(): void
    {
        $admin = User::factory()->create(['username' => 'theme-admin']);
        $admin->assignRole('Administrator');

        $this->actingAs($admin)
            ->get(route('admin.appearance.themes'))
            ->assertOk()
            ->assertSee('Aurora');

        $result = app(DemoContentSeeder::class)->seed($admin);
        $this->assertGreaterThanOrEqual(5, $result['posts']);
        $this->assertGreaterThanOrEqual(1, $result['terms']);
    }

    #[Test]
    public function theme_asset_route_serves_css(): void
    {
        $this->get(route('theme.asset', ['theme' => 'aurora', 'path' => 'theme.css']))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/css; charset=UTF-8');
    }

    #[Test]
    public function import_rejects_php_in_package(): void
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive extension required');
        }

        $tmpDir = storage_path('app/tmp/evil-theme');
        File::ensureDirectoryExists($tmpDir);
        File::put($tmpDir.'/theme.json', json_encode([
            'name' => 'Evil',
            'slug' => 'evil',
            'version' => '1.0.0',
        ]));
        File::put($tmpDir.'/shell.php', '<?php echo 1;');
        $zipPath = storage_path('app/tmp/evil-theme.zip');
        if (File::exists($zipPath)) {
            File::delete($zipPath);
        }
        $zip = new \ZipArchive;
        $zip->open($zipPath, \ZipArchive::CREATE);
        $zip->addFile($tmpDir.'/theme.json', 'theme.json');
        $zip->addFile($tmpDir.'/shell.php', 'shell.php');
        $zip->close();

        $admin = User::factory()->create(['username' => 'theme-importer']);
        $admin->assignRole('Administrator');

        $this->actingAs($admin)
            ->post(route('admin.appearance.themes.import'), [
                'package' => new UploadedFile($zipPath, 'evil.zip', 'application/zip', null, true),
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDirectoryDoesNotExist(resource_path('views/themes/evil'));
    }
}
