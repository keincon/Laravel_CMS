<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\InstallationService;
use App\Services\LaravelPressBootstrapService;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class GutenbergEditorAdminTest extends TestCase
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
    public function content_create_loads_gutenberg_editor_assets(): void
    {
        $user = User::factory()->create(['username' => 'editoruser']);
        $user->assignRole('Administrator');

        $this->actingAs($user)
            ->get('/admin/contents/create?type=post')
            ->assertOk()
            ->assertSee('block-editor-root', false)
            ->assertSee('laravelpress-editor.js', false)
            ->assertSee('admin-editor.css', false);
    }
}
