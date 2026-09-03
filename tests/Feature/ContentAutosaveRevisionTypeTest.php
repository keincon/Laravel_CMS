<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ContentType;
use App\Models\User;
use App\Services\Content\ContentService;
use App\Services\Content\RevisionService;
use App\Services\InstallationService;
use App\Services\LaravelPressBootstrapService;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ContentAutosaveRevisionTypeTest extends TestCase
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
    public function it_autosaves_and_restores_revisions(): void
    {
        $user = User::factory()->create(['username' => 'autosave']);
        $user->assignRole('Author');

        $content = app(ContentService::class)->create('post', [
            'title' => 'Draft A',
            'body' => 'v1',
            'status' => 'draft',
        ], $user);

        $snapshot = app(RevisionService::class)->snapshot($content, $user, 'manual');

        $this->actingAs($user)
            ->postJson(route('admin.contents.autosave', $content), [
                'title' => 'Draft A edited',
                'body' => 'v2',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Autosaved.');

        $this->actingAs($user)
            ->post(route('admin.contents.revisions.restore', [$content, $snapshot]))
            ->assertRedirect();

        $this->assertSame('Draft A', $content->fresh()->title);
    }

    #[Test]
    public function admin_can_create_custom_content_type(): void
    {
        $user = User::factory()->create(['username' => 'typesadmin']);
        $user->assignRole('Administrator');

        $this->actingAs($user)->post('/admin/content-types', [
            'name' => 'news',
            'slug' => 'news',
            'singular_label' => 'News',
            'plural_label' => 'News',
            'supports' => ['title', 'editor'],
            'public' => 1,
            'show_in_rest' => 1,
            'has_archive' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('content_types', ['slug' => 'news']);
        $this->assertNotNull(ContentType::query()->where('slug', 'news')->first());
    }
}
