<?php

namespace Tests\Feature;

use App\Models\DynamicPageSetting;
use App\Models\LayoutSetting;
use App\Models\Page;
use App\Models\Post;
use App\Models\SeoSetting;
use App\Models\User;
use App\Services\DynamicPageService;
use App\Services\InstallationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StaticDynamicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(InstallationService::class, function ($mock) {
            $mock->shouldReceive('isInstalled')->andReturn(true);
        });

        Cache::flush();
        SeoSetting::forgetCache();
        LayoutSetting::forgetCache();
        DynamicPageSetting::forgetCache();
        app(DynamicPageService::class)->ensureDefaults();
    }

    public function test_static_page_renders_with_master_layout_marker(): void
    {
        $page = Page::query()->create([
            'title' => 'About Us',
            'slug' => 'about',
            'content' => '<p>Company story</p>',
            'status' => 'publish',
            'published_at' => now(),
        ]);

        $response = $this->get('/about');

        $response->assertOk();
        $response->assertSee('About Us');
        $response->assertSee('Static Page');
        $response->assertSee('Company story', false);
    }

    public function test_blog_is_dynamic_and_lists_posts(): void
    {
        $author = User::factory()->create(['username' => 'writer']);

        Post::query()->create([
            'title' => 'Hello CMS',
            'slug' => 'hello-cms',
            'content' => '<p>Body</p>',
            'status' => 'publish',
            'author_id' => $author->id,
            'published_at' => now(),
        ]);

        $response = $this->get('/blog');

        $response->assertOk();
        $response->assertSee('Dynamic · Blog Archive');
        $response->assertSee('Hello CMS');
    }

    public function test_search_defaults_to_noindex(): void
    {
        $response = $this->get('/search?q=laravel');

        $response->assertOk();
        $response->assertSee('noindex', false);
        $response->assertSee('Dynamic · Search');
    }

    public function test_reserved_slug_cannot_create_static_page(): void
    {
        $service = app(DynamicPageService::class);

        $this->assertTrue($service->isReservedSlug('blog'));

        $validator = \Illuminate\Support\Facades\Validator::make(
            ['slug' => 'blog'],
            ['slug' => [\Illuminate\Validation\Rule::notIn($service->reservedSlugs())]]
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }

    public function test_disabled_dynamic_blog_returns_404(): void
    {
        DynamicPageSetting::query()->where('type', 'blog')->update(['is_enabled' => false]);
        DynamicPageSetting::forgetCache();

        $this->get('/blog')->assertNotFound();
    }

    public function test_install_defaults_do_not_seed_blog_static_page_slug_in_service(): void
    {
        $service = app(DynamicPageService::class);

        $this->assertTrue($service->isReservedSlug('blog'));
        $this->assertTrue($service->isReservedSlug('search'));
        $this->assertFalse($service->isReservedSlug('about'));
    }
}
