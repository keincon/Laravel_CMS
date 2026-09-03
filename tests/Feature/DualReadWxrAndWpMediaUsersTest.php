<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Media;
use App\Models\User;
use App\Services\ImportExport\WxrImportService;
use App\Services\InstallationService;
use App\Services\LaravelPressBootstrapService;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DualReadWxrAndWpMediaUsersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(InstallationService::class)->markInstalled('test@example.com');
        app(LaravelPressBootstrapService::class)->seedBuiltins();
        app(RolePermissionService::class)->syncDefaults();

        \App\Models\SeoSetting::forgetCache();
        \App\Models\LayoutSetting::forgetCache();
        \App\Models\DynamicPageSetting::forgetCache();
        app(\App\Services\DynamicPageService::class)->ensureDefaults();
    }

    #[Test]
    public function public_site_renders_content_posts_preferentially(): void
    {
        $user = User::factory()->create(['username' => 'dualauthor']);
        app(\App\Services\Content\ContentService::class)->create('post', [
            'title' => 'Dual Read Post',
            'slug' => 'dual-read-post',
            'body' => 'From contents table',
            'status' => 'published',
            'published_at' => now(),
        ], $user);

        $this->get('/blog/dual-read-post')
            ->assertOk()
            ->assertSee('Dual Read Post')
            ->assertSee('From contents table');
    }

    #[Test]
    public function wp_v2_exposes_media_and_users(): void
    {
        $user = User::factory()->create([
            'username' => 'publicuser',
            'display_name' => 'Public User',
        ]);

        Media::query()->create([
            'disk' => 'public',
            'path' => 'uploads/demo.jpg',
            'filename' => 'demo.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1234,
            'alt' => 'Demo',
            'uploaded_by' => $user->id,
        ]);

        $this->getJson('/api/wp/v2/media')
            ->assertOk()
            ->assertJsonPath('data.0.mime_type', 'image/jpeg')
            ->assertJsonPath('data.0.title.rendered', 'demo.jpg');

        $this->getJson('/api/wp/v2/users')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'publicuser', 'name' => 'Public User']);

        $this->getJson('/api/wp/v2/comments')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);

        $this->getJson('/api/wp/v2/settings')
            ->assertOk()
            ->assertJsonStructure(['title', 'url', 'timezone']);

        $this->getJson('/api/wp/v2/search?search=nothing-xyz')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function wxr_import_creates_posts_authors_terms_and_handles_attachments(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        \Illuminate\Support\Facades\Http::fake([
            'https://example.com/photo.jpg' => \Illuminate\Support\Facades\Http::response('fakepixels', 200, [
                'Content-Type' => 'image/jpeg',
            ]),
        ]);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0"
  xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"
  xmlns:content="http://purl.org/rss/1.0/modules/content/"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:wp="http://wordpress.org/export/1.2/">
  <channel>
    <title>Demo</title>
    <wp:author>
      <wp:author_id>1</wp:author_id>
      <wp:author_login>wxrauthor</wp:author_login>
      <wp:author_email>wxr@example.com</wp:author_email>
      <wp:author_display_name>WXR Author</wp:author_display_name>
    </wp:author>
    <wp:category>
      <wp:category_nicename>news</wp:category_nicename>
      <wp:cat_name>News</wp:cat_name>
    </wp:category>
    <item>
      <title>Photo</title>
      <wp:post_id>9</wp:post_id>
      <wp:post_type>attachment</wp:post_type>
      <wp:status>inherit</wp:status>
      <wp:attachment_url>https://example.com/photo.jpg</wp:attachment_url>
    </item>
    <item>
      <title>WXR Post</title>
      <dc:creator>wxrauthor</dc:creator>
      <content:encoded><![CDATA[<p>Hello WXR</p>]]></content:encoded>
      <excerpt:encoded><![CDATA[]]></excerpt:encoded>
      <category domain="category" nicename="news"><![CDATA[News]]></category>
      <wp:post_id>1</wp:post_id>
      <wp:post_type>post</wp:post_type>
      <wp:status>publish</wp:status>
      <wp:post_name>wxr-post</wp:post_name>
      <wp:post_date_gmt>2024-01-01 12:00:00</wp:post_date_gmt>
      <wp:postmeta>
        <wp:meta_key>_thumbnail_id</wp:meta_key>
        <wp:meta_value>9</wp:meta_value>
      </wp:postmeta>
    </item>
    <item>
      <title>Skip nav</title>
      <wp:post_type>nav_menu_item</wp:post_type>
      <wp:status>publish</wp:status>
    </item>
  </channel>
</rss>
XML;
        Storage::disk('local')->put('imports/demo.xml', $xml);

        $dry = app(WxrImportService::class)->importFromDisk('imports/demo.xml', 'local', true);
        $this->assertSame(1, $dry['imported']);
        $this->assertSame(1, $dry['authors']);
        $this->assertSame(1, $dry['media']);
        $this->assertSame(0, Content::query()->count());

        $live = app(WxrImportService::class)->importFromDisk('imports/demo.xml', 'local', false);
        $this->assertSame(1, $live['imported']);
        $this->assertSame(1, $live['authors']);
        $this->assertSame(1, $live['media']);
        $this->assertSame(1, $live['skipped']); // nav_menu_item
        $this->assertDatabaseHas('contents', ['slug' => 'wxr-post', 'title' => 'WXR Post']);
        $this->assertDatabaseHas('users', ['username' => 'wxrauthor', 'email' => 'wxr@example.com']);
        $this->assertDatabaseHas('terms', ['slug' => 'news']);
        $this->assertTrue(
            Content::query()->where('slug', 'wxr-post')->firstOrFail()->terms()->where('slug', 'news')->exists()
        );
        $this->assertNotNull(
            Content::query()->where('slug', 'wxr-post')->value('featured_media_id')
        );
    }

    #[Test]
    public function force_https_redirects_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->get('http://localhost/')
            ->assertRedirect();
    }
}
