<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\CorsSettingsService;
use App\Services\InstallationService;
use App\Services\LaravelPressBootstrapService;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CorsSettingsTest extends TestCase
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
    public function admin_can_view_and_update_cors_settings(): void
    {
        $admin = User::factory()->create(['username' => 'cors-admin']);
        $admin->assignRole('Administrator');

        $this->actingAs($admin)
            ->get(route('admin.settings.cors'))
            ->assertOk()
            ->assertSee('Allowed origins');

        $this->actingAs($admin)
            ->put(route('admin.settings.cors.update'), [
                'enabled' => '1',
                'paths' => "api/*\nsanctum/csrf-cookie",
                'allowed_origins' => "https://app.example.com\nhttps://admin.example.com",
                'allowed_origins_patterns' => '',
                'allow_all_methods' => '1',
                'allowed_headers' => "Authorization\nContent-Type\nAccept",
                'exposed_headers' => 'X-Total-Count',
                'max_age' => 600,
                'supports_credentials' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $settings = app(CorsSettingsService::class)->current();
        $this->assertTrue($settings['enabled']);
        $this->assertSame(['https://app.example.com', 'https://admin.example.com'], $settings['allowed_origins']);
        $this->assertTrue($settings['supports_credentials']);
        $this->assertSame(600, $settings['max_age']);
        $this->assertSame(['https://app.example.com', 'https://admin.example.com'], config('cors.allowed_origins'));
        $this->assertTrue(config('cors.supports_credentials'));
    }

    #[Test]
    public function credentials_with_wildcard_origin_is_rejected(): void
    {
        $admin = User::factory()->create(['username' => 'cors-bad']);
        $admin->assignRole('Administrator');

        $this->actingAs($admin)
            ->from(route('admin.settings.cors'))
            ->put(route('admin.settings.cors.update'), [
                'enabled' => '1',
                'paths' => 'api/*',
                'allowed_origins' => '*',
                'allow_all_methods' => '1',
                'allowed_headers' => '*',
                'max_age' => 0,
                'supports_credentials' => '1',
            ])
            ->assertRedirect(route('admin.settings.cors'))
            ->assertSessionHasErrors('allowed_origins');
    }

    #[Test]
    public function api_preflight_reflects_saved_origin(): void
    {
        app(CorsSettingsService::class)->update([
            'enabled' => true,
            'paths' => 'api/*',
            'allowed_origins' => 'https://spa.example.com',
            'allowed_methods' => ['GET', 'OPTIONS'],
            'allowed_headers' => '*',
            'exposed_headers' => '',
            'max_age' => 120,
            'supports_credentials' => false,
        ]);

        $response = $this->call(
            'OPTIONS',
            '/api/v1/posts',
            [],
            [],
            [],
            [
                'HTTP_ORIGIN' => 'https://spa.example.com',
                'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
            ]
        );

        $this->assertContains($response->getStatusCode(), [200, 204]);
        $response->assertHeader('Access-Control-Allow-Origin', 'https://spa.example.com');
    }

    #[Test]
    public function disabled_cors_clears_paths(): void
    {
        app(CorsSettingsService::class)->update([
            'enabled' => false,
            'paths' => 'api/*',
            'allowed_origins' => '*',
            'allowed_methods' => ['*'],
            'allowed_headers' => '*',
            'exposed_headers' => '',
            'max_age' => 0,
            'supports_credentials' => false,
        ]);

        $this->assertSame([], config('cors.paths'));
    }
}
