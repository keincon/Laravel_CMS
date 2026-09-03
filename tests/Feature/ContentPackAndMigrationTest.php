<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Post;
use App\Models\User;
use App\Services\Content\LegacyContentMigrator;
use App\Services\ImportExport\ContentPackService;
use App\Services\InstallationService;
use App\Services\LaravelPressBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContentPackAndMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(InstallationService::class)->markInstalled('test@example.com');
        app(LaravelPressBootstrapService::class)->seedBuiltins();
        Storage::fake('local');
    }

    #[Test]
    public function it_exports_and_imports_content_pack(): void
    {
        $user = User::factory()->create(['username' => 'packuser']);
        app(\App\Services\Content\ContentService::class)->create('post', [
            'title' => 'Pack Post',
            'body' => 'Body',
            'status' => 'published',
        ], $user);

        $packs = app(ContentPackService::class);
        $export = $packs->exportToDisk('local', 'exports/test-pack.json');
        $this->assertSame(1, $export['count']);
        Storage::disk('local')->assertExists('exports/test-pack.json');

        Content::query()->forceDelete();

        $dry = $packs->importFromDisk('exports/test-pack.json', 'local', true);
        $this->assertSame(1, $dry['imported']);
        $this->assertSame(0, Content::query()->count());

        $live = $packs->importFromDisk('exports/test-pack.json', 'local', false);
        $this->assertSame(1, $live['imported']);
        $this->assertDatabaseHas('contents', ['title' => 'Pack Post']);
    }

    #[Test]
    public function it_migrates_legacy_posts_into_contents(): void
    {
        $user = User::factory()->create(['username' => 'legacy']);
        Post::query()->create([
            'title' => 'Legacy Post',
            'slug' => 'legacy-post',
            'content' => 'Hello',
            'status' => 'publish',
            'author_id' => $user->id,
            'published_at' => now(),
        ]);

        $result = app(LegacyContentMigrator::class)->migrateAll();
        $this->assertSame(1, $result['posts']);
        $this->assertDatabaseHas('contents', ['slug' => 'legacy-post', 'title' => 'Legacy Post']);
    }

    #[Test]
    public function dual_write_syncs_categories_to_terms(): void
    {
        $user = User::factory()->create(['username' => 'catsync']);
        $post = Post::query()->create([
            'title' => 'Cat Sync',
            'slug' => 'cat-sync',
            'content' => 'Body',
            'status' => 'publish',
            'author_id' => $user->id,
            'published_at' => now(),
        ]);

        $category = \App\Models\Category::query()->create([
            'name' => 'News',
            'slug' => 'news',
        ]);
        $post->categories()->sync([$category->id]);

        $content = app(\App\Services\Content\DualWriteContentSync::class)->syncPost($post->fresh('categories'), force: true);

        $this->assertNotNull($content);
        $this->assertTrue(
            $content->terms()->where('slug', 'news')->exists()
        );
    }
}
