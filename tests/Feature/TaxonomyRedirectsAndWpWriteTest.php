<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Taxonomy;
use App\Models\User;
use App\Services\InstallationService;
use App\Services\LaravelPressBootstrapService;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TaxonomyRedirectsAndWpWriteTest extends TestCase
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
    public function editor_can_manage_taxonomies_and_admin_can_manage_redirects(): void
    {
        $editor = User::factory()->create(['username' => 'editorx']);
        $editor->assignRole('Editor');

        $this->actingAs($editor)->get('/admin/taxonomies')->assertOk();

        $taxonomy = Taxonomy::query()->where('slug', 'category')->firstOrFail();
        $this->actingAs($editor)->post('/admin/taxonomies/'.$taxonomy->id.'/terms', [
            'name' => 'News',
            'slug' => 'news',
        ])->assertRedirect();

        $this->assertDatabaseHas('terms', ['slug' => 'news', 'taxonomy_id' => $taxonomy->id]);

        $admin = User::factory()->create(['username' => 'adminx']);
        $admin->assignRole('Administrator');

        $this->actingAs($admin)->get('/admin/redirects')->assertOk();
        $this->actingAs($admin)->post('/admin/redirects', [
            'from_path' => '/legacy',
            'to_path' => '/new',
            'status_code' => 301,
            'is_active' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('redirects', ['from_path' => '/legacy', 'to_path' => '/new']);
    }

    #[Test]
    public function wp_api_can_create_post_when_authenticated(): void
    {
        $user = User::factory()->create(['username' => 'wpwriter']);
        $user->assignRole('Author');
        Sanctum::actingAs($user);

        $this->postJson('/api/wp/v2/posts', [
            'title' => 'WP Post',
            'content' => 'Hello WP',
            'status' => 'draft',
        ])->assertCreated()->assertJsonPath('data.title', 'WP Post');
    }
}
