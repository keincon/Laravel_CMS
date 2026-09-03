<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Redirect;
use App\Models\User;
use App\Services\InstallationService;
use App\Services\LaravelPressBootstrapService;
use App\Services\RolePermissionService;
use App\Services\Search\SearchService;
use App\Services\SettingsService;
use App\Support\Modules\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PlatformServicesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(InstallationService::class)->markInstalled('test@example.com');
        app(LaravelPressBootstrapService::class)->seedBuiltins();
        app(RolePermissionService::class)->syncDefaults();
    }

    #[Test]
    public function settings_service_sets_and_autoloads(): void
    {
        $settings = app(SettingsService::class);
        $settings->set('site_name', 'LaravelPress', 'string', 'general', true);

        $this->assertSame('LaravelPress', $settings->get('site_name'));
        $this->assertSame('LaravelPress', $settings->autoload()['site_name'] ?? null);
    }

    #[Test]
    public function search_service_returns_collection(): void
    {
        $results = app(SearchService::class)->search('nothing-here-xyz');
        $this->assertTrue($results->isEmpty());
    }

    #[Test]
    public function seo_module_is_discovered_and_enabled(): void
    {
        $modules = app(ModuleManager::class);
        $discovered = $modules->discover();
        $this->assertArrayHasKey('SEO', $discovered);
        $this->assertTrue($modules->isEnabled('SEO'));
    }

    #[Test]
    public function redirect_middleware_issues_redirect(): void
    {
        Redirect::query()->create([
            'from_path' => '/old-slug',
            'to_path' => '/new-slug',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $this->get('/old-slug')->assertRedirect('/new-slug');
    }

    #[Test]
    public function wp_compat_types_endpoint_works(): void
    {
        $this->getJson('/api/wp/v2/types')
            ->assertOk()
            ->assertJsonStructure(['post', 'page']);
    }

    #[Test]
    public function author_can_open_contents_admin(): void
    {
        $user = User::factory()->create(['username' => 'contentadmin']);
        $user->assignRole('Author');

        $this->actingAs($user)
            ->get('/admin/contents?type=post')
            ->assertOk();
    }
}
