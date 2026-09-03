<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CmsSetting;
use App\Models\Comment;
use App\Models\Content;
use App\Models\User;
use App\Services\Content\ContentService;
use App\Services\DynamicPageService;
use App\Services\InstallationService;
use App\Services\LaravelPressBootstrapService;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PublishPublicAndCommentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(InstallationService::class)->markInstalled('test@example.com');
        app(LaravelPressBootstrapService::class)->seedBuiltins();
        app(RolePermissionService::class)->syncDefaults();
        Cache::flush();
        app(DynamicPageService::class)->ensureDefaults();
        CmsSetting::setValue('comments_enabled', '1', 'boolean');
        CmsSetting::setValue('comment_moderation', '0', 'boolean');
    }

    #[Test]
    public function published_content_is_public_commentable_and_in_wp_api(): void
    {
        $author = User::factory()->create(['username' => 'flowauthor']);
        $author->assignRole('Author');

        $content = app(ContentService::class)->create('post', [
            'title' => 'Flow Publish Post',
            'slug' => 'flow-publish-post',
            'body' => '<p>Public body</p>',
            'status' => 'published',
            'published_at' => now(),
            'comment_status' => 'open',
        ], $author);

        $this->assertInstanceOf(Content::class, $content);
        $this->assertDatabaseHas('contents', ['slug' => 'flow-publish-post']);
        $this->assertDatabaseMissing('posts', ['slug' => 'flow-publish-post']);

        $this->get('/blog/flow-publish-post')
            ->assertOk()
            ->assertSee('Flow Publish Post')
            ->assertSee('Public body', false)
            ->assertSee('Leave a Reply');

        $this->post(route('comments.store.content', $content), [
            'author_name' => 'Guest Reader',
            'author_email' => 'guest@example.com',
            'content' => 'Great article!',
        ])->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'content_id' => $content->id,
            'author_name' => 'Guest Reader',
            'content' => 'Great article!',
            'status' => 'approved',
        ]);

        $this->assertTrue(Comment::query()->where('content_id', $content->id)->exists());

        $this->getJson('/api/wp/v2/posts')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'flow-publish-post']);

        $this->getJson('/api/v1/contents?type=post')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Flow Publish Post']);
    }
}
