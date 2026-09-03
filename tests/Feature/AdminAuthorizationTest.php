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

class AdminAuthorizationTest extends TestCase
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
    public function subscriber_cannot_access_posts_or_settings(): void
    {
        $user = User::factory()->create(['username' => 'subauth']);
        $user->assignRole('Subscriber');

        $this->actingAs($user)
            ->get('/admin/posts')
            ->assertForbidden();

        $this->actingAs($user)
            ->get('/admin/settings/general')
            ->assertForbidden();

        $this->actingAs($user)
            ->get('/admin/users')
            ->assertForbidden();
    }

    #[Test]
    public function author_can_access_posts_but_not_settings_or_users(): void
    {
        $user = User::factory()->create(['username' => 'authorauth']);
        $user->assignRole('Author');

        // Content-first: /admin/posts redirects into LaravelPress contents UI.
        $this->actingAs($user)
            ->get('/admin/posts')
            ->assertRedirect(route('admin.contents.index', ['type' => 'post']));

        $this->actingAs($user)
            ->get('/admin/contents?type=post')
            ->assertOk();

        $this->actingAs($user)
            ->get('/admin/posts?legacy=1')
            ->assertOk();

        $this->actingAs($user)
            ->get('/admin/settings/general')
            ->assertForbidden();

        $this->actingAs($user)
            ->get('/admin/users')
            ->assertForbidden();
    }

    #[Test]
    public function guest_is_redirected_from_admin(): void
    {
        $this->get('/admin/posts')->assertRedirect('/login');
    }
}
