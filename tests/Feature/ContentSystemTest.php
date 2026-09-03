<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Models\Content;
use App\Models\ContentType;
use App\Models\User;
use App\Services\Content\ContentService;
use App\Services\LaravelPressBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContentSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Marker so installation middleware does not redirect tests that hit HTTP.
        $dir = storage_path('app/cms');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($dir.'/installed.json', json_encode([
            'installed_at' => now()->toIso8601String(),
            'version' => '1.0.0',
        ]));

        app(LaravelPressBootstrapService::class)->seedBuiltins();
    }

    #[Test]
    public function it_creates_post_and_page_content_types(): void
    {
        $this->assertDatabaseHas('content_types', ['slug' => 'post']);
        $this->assertDatabaseHas('content_types', ['slug' => 'page']);
        $this->assertDatabaseHas('taxonomies', ['slug' => 'category']);
        $this->assertDatabaseHas('taxonomies', ['slug' => 'post_tag']);
    }

    #[Test]
    public function content_service_creates_and_publishes_content(): void
    {
        $user = User::factory()->create([
            'username' => 'author1',
        ]);

        $service = app(ContentService::class);
        $content = $service->create('post', [
            'title' => 'Hello LaravelPress',
            'body' => '<p>Body</p>',
            'status' => 'draft',
        ], $user);

        $this->assertSame(ContentStatus::Draft, $content->status);
        $this->assertSame('hello-laravelpress', $content->slug);

        $published = $service->publish($content);
        $this->assertSame(ContentStatus::Published, $published->status);
        $this->assertNotNull($published->published_at);
    }

    #[Test]
    public function hierarchical_pages_reject_circular_parents(): void
    {
        $service = app(ContentService::class);
        $pageType = ContentType::query()->where('slug', 'page')->firstOrFail();

        $parent = $service->create($pageType, ['title' => 'Parent', 'status' => 'draft']);
        $child = $service->create($pageType, [
            'title' => 'Child',
            'status' => 'draft',
            'parent_id' => $parent->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $service->update($parent, ['parent_id' => $child->id]);
    }

    #[Test]
    public function content_policy_denies_guest_mutations_via_gate(): void
    {
        $user = User::factory()->create(['username' => 'sub1']);
        $content = Content::query()->create([
            'content_type_id' => ContentType::query()->where('slug', 'post')->value('id'),
            'title' => 'Private draft',
            'slug' => 'private-draft',
            'status' => ContentStatus::Draft,
            'author_id' => $user->id,
        ]);

        $this->assertFalse($user->can('publish', $content));
    }
}
