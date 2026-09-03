<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Content;
use App\Models\User;
use App\Services\InstallationService;
use App\Services\LaravelPressBootstrapService;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DemoDataInstallTest extends TestCase
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
    public function admin_can_install_dummy_data_from_settings(): void
    {
        $admin = User::factory()->create(['username' => 'demo-admin']);
        $admin->assignRole('Administrator');

        $this->actingAs($admin)
            ->from(route('admin.settings.general'))
            ->post(route('admin.settings.demo-data'))
            ->assertRedirect(route('admin.settings.general'))
            ->assertSessionHas('success');

        $this->assertGreaterThanOrEqual(
            5,
            Content::query()->ofType('post')->where('status', '!=', 'trash')->count()
        );
    }

    #[Test]
    public function author_cannot_install_dummy_data(): void
    {
        $author = User::factory()->create(['username' => 'demo-author-role']);
        $author->assignRole('Author');

        $this->actingAs($author)
            ->post(route('admin.settings.demo-data'))
            ->assertForbidden();
    }
}
