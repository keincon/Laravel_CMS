<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Post;
use App\Models\Taxonomy;
use App\Models\Term;
use App\Models\User;
use App\Services\Content\ContentService;
use App\Services\InstallationService;
use App\Services\LaravelPressBootstrapService;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ContentReverseSyncAndAdminFieldsTest extends TestCase
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
    public function creating_content_mirrors_to_legacy_post_with_terms(): void
    {
        config([
            'cms.legacy.retired' => false,
            'cms.legacy.dual_write' => true,
        ]);

        $user = User::factory()->create(['username' => 'revsync']);
        $taxonomy = Taxonomy::query()->where('slug', 'category')->firstOrFail();
        $term = Term::query()->create([
            'taxonomy_id' => $taxonomy->id,
            'name' => 'SyncCat',
            'slug' => 'sync-cat',
        ]);

        $content = app(ContentService::class)->create('post', [
            'title' => 'Reverse Sync Post',
            'slug' => 'reverse-sync-post',
            'body' => '<p>Hi</p>',
            'status' => 'published',
            'published_at' => now(),
            'term_ids' => [$term->id],
        ], $user);

        $this->assertDatabaseHas('posts', [
            'slug' => 'reverse-sync-post',
            'title' => 'Reverse Sync Post',
            'status' => 'publish',
        ]);

        $post = Post::query()->where('slug', 'reverse-sync-post')->firstOrFail();
        $this->assertTrue($post->categories()->where('slug', 'sync-cat')->exists());
        $this->assertTrue($content->terms()->where('slug', 'sync-cat')->exists());
    }

    #[Test]
    public function creating_content_skips_legacy_when_dual_write_disabled(): void
    {
        config([
            'cms.legacy.retired' => false,
            'cms.legacy.dual_write' => false,
        ]);

        $user = User::factory()->create(['username' => 'nosync']);
        app(ContentService::class)->create('post', [
            'title' => 'No Legacy Mirror',
            'slug' => 'no-legacy-mirror',
            'body' => '<p>Hi</p>',
            'status' => 'published',
            'published_at' => now(),
        ], $user);

        $this->assertDatabaseHas('contents', ['slug' => 'no-legacy-mirror']);
        $this->assertDatabaseMissing('posts', ['slug' => 'no-legacy-mirror']);
    }

    #[Test]
    public function author_can_save_featured_media_and_terms_via_contents_admin(): void
    {
        $user = User::factory()->create(['username' => 'contentadmin']);
        $user->assignRole('Author');

        $taxonomy = Taxonomy::query()->where('slug', 'category')->firstOrFail();
        $term = Term::query()->create([
            'taxonomy_id' => $taxonomy->id,
            'name' => 'AdminCat',
            'slug' => 'admin-cat',
        ]);

        $media = \App\Models\Media::query()->create([
            'disk' => 'public',
            'path' => 'uploads/x.jpg',
            'filename' => 'x.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 10,
            'alt' => 'x',
            'uploaded_by' => $user->id,
        ]);

        $this->actingAs($user)->post('/admin/contents', [
            'type' => 'post',
            'title' => 'Admin Fields Post',
            'slug' => 'admin-fields-post',
            'body' => 'Body',
            'status' => 'draft',
            'featured_media_id' => $media->id,
            'term_ids' => [$term->id],
        ])->assertRedirect();

        $content = Content::query()->where('slug', 'admin-fields-post')->firstOrFail();
        $this->assertSame($media->id, $content->featured_media_id);
        $this->assertTrue($content->terms()->where('slug', 'admin-cat')->exists());
    }

    #[Test]
    public function quick_draft_creates_content_not_only_legacy_post(): void
    {
        $user = User::factory()->create(['username' => 'qdraft']);
        $user->assignRole('Author');

        $response = $this->actingAs($user)->post('/admin/dashboard/quick-draft', [
            'title' => 'Quick Draft Title',
            'content' => 'Draft body',
        ]);

        $content = Content::query()->where('title', 'Quick Draft Title')->firstOrFail();
        $response->assertRedirect(route('admin.contents.edit', $content));
        $this->assertDatabaseHas('contents', ['title' => 'Quick Draft Title']);
        // Dual-write is off by default after legacy retirement.
        $this->assertDatabaseMissing('posts', ['title' => 'Quick Draft Title']);
    }
}
