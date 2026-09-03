<?php

declare(strict_types=1);

namespace App\Services\Themes;

use App\Enums\ContentStatus;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Media;
use App\Models\Tag;
use App\Models\Taxonomy;
use App\Models\Term;
use App\Models\User;
use App\Services\Content\ContentService;
use App\Services\LaravelPressBootstrapService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Seeds demo posts, pages, terms, comments, and a placeholder media item.
 */
final class DemoContentSeeder
{
    public function __construct(
        private readonly ContentService $contents,
        private readonly LaravelPressBootstrapService $bootstrap,
    ) {}

    /**
     * @return array{posts: int, pages: int, terms: int, comments: int, media: int}
     */
    public function seed(?User $author = null, bool $fresh = false): array
    {
        $this->bootstrap->seedBuiltins();

        $author ??= User::query()->orderBy('id')->first()
            ?? User::factory()->create(['username' => 'demo-author', 'name' => 'Demo Author']);

        if ($fresh) {
            $this->purgeDemoContent();
        }

        return DB::transaction(function () use ($author): array {
            $termIds = $this->seedTerms();
            $mediaId = $this->seedPlaceholderMedia($author);
            $posts = $this->seedPosts($author, $termIds, $mediaId);
            $pages = $this->seedPages($author);
            $comments = $this->seedComments($posts, $author);

            // Keep legacy Category/Tag tables usable for older admin screens.
            foreach (['News', 'Guides', 'Announcements'] as $name) {
                Category::query()->firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name, 'description' => "Demo category: {$name}"]
                );
            }
            foreach (['laravel', 'cms', 'design', 'tutorial'] as $name) {
                Tag::query()->firstOrCreate(
                    ['slug' => $name],
                    ['name' => $name]
                );
            }

            return [
                'posts' => count($posts),
                'pages' => $pages,
                'terms' => count($termIds),
                'comments' => $comments,
                'media' => $mediaId ? 1 : 0,
            ];
        });
    }

    /**
     * @return list<int>
     */
    private function seedTerms(): array
    {
        $ids = [];
        $categoryTax = Taxonomy::query()->where('slug', 'category')->first();
        $tagTax = Taxonomy::query()->where('slug', 'post_tag')->first();

        if ($categoryTax) {
            foreach ([
                ['name' => 'News', 'description' => 'Company and product news'],
                ['name' => 'Guides', 'description' => 'How-to articles'],
                ['name' => 'Announcements', 'description' => 'Site announcements'],
            ] as $row) {
                $term = Term::query()->updateOrCreate(
                    ['taxonomy_id' => $categoryTax->id, 'slug' => Str::slug($row['name'])],
                    ['name' => $row['name'], 'description' => $row['description']]
                );
                $ids[] = $term->id;
            }
        }

        if ($tagTax) {
            foreach (['laravel', 'cms', 'design', 'tutorial', 'themes'] as $name) {
                $term = Term::query()->updateOrCreate(
                    ['taxonomy_id' => $tagTax->id, 'slug' => $name],
                    ['name' => $name, 'description' => "Tag: {$name}"]
                );
                $ids[] = $term->id;
            }
        }

        return $ids;
    }

    private function seedPlaceholderMedia(User $author): ?int
    {
        $disk = 'public';
        $path = 'demo/laravelpress-placeholder.svg';
        $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#0D9488"/>
      <stop offset="100%" stop-color="#0284C7"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="630" fill="url(#g)"/>
  <text x="60" y="320" fill="#fff" font-family="Georgia, serif" font-size="64">LaravelPress Demo</text>
</svg>
SVG;

        Storage::disk($disk)->put($path, $svg);

        $media = Media::query()->updateOrCreate(
            ['path' => $path, 'disk' => $disk],
            [
                'filename' => 'laravelpress-placeholder.svg',
                'mime_type' => 'image/svg+xml',
                'size' => strlen($svg),
                'alt' => 'LaravelPress demo placeholder',
                'uploaded_by' => $author->id,
            ]
        );

        return $media->id;
    }

    /**
     * @param  list<int>  $termIds
     * @return list<\App\Models\Content>
     */
    private function seedPosts(User $author, array $termIds, ?int $mediaId): array
    {
        $titles = [
            'Welcome to LaravelPress',
            'Choosing a theme for your site',
            'Working with blocks and revisions',
            'Organizing content with taxonomies',
            'A short guide to media uploads',
            'Publishing schedules and drafts',
            'Comments moderation basics',
            'Importing and exporting theme packs',
        ];

        $posts = [];
        foreach ($titles as $i => $title) {
            $slug = Str::slug($title);
            $existing = \App\Models\Content::query()
                ->whereHas('type', fn ($q) => $q->where('slug', 'post'))
                ->where('slug', $slug)
                ->first();

            if ($existing) {
                $posts[] = $existing;
                continue;
            }

            $picked = array_values(array_unique([
                $termIds[$i % max(count($termIds), 1)] ?? null,
                $termIds[($i + 3) % max(count($termIds), 1)] ?? null,
            ]));
            $picked = array_values(array_filter($picked));

            $posts[] = $this->contents->create('post', [
                'title' => $title,
                'slug' => $slug,
                'excerpt' => 'Demo excerpt for '.$title.'.',
                'body' => $this->demoBody($title),
                'status' => ContentStatus::Published->value,
                'published_at' => now()->subDays(count($titles) - $i),
                'featured_media_id' => $mediaId,
                'term_ids' => $picked,
                'comment_status' => 'open',
            ], $author);
        }

        return $posts;
    }

    private function seedPages(User $author): int
    {
        $pages = [
            ['title' => 'Demo Landing', 'slug' => 'demo-landing', 'body' => '<p>A sample landing page seeded for theme previews.</p>'],
            ['title' => 'Team', 'slug' => 'team', 'body' => '<p>Meet the demo team behind this sample site.</p>'],
            ['title' => 'FAQ', 'slug' => 'faq', 'body' => '<h2>Frequently asked</h2><p>How do I switch themes? Use Appearance → Themes.</p>'],
        ];

        $count = 0;
        foreach ($pages as $page) {
            $exists = \App\Models\Content::query()
                ->whereHas('type', fn ($q) => $q->where('slug', 'page'))
                ->where('slug', $page['slug'])
                ->exists();
            if ($exists) {
                continue;
            }
            $this->contents->create('page', [
                'title' => $page['title'],
                'slug' => $page['slug'],
                'body' => $page['body'],
                'status' => ContentStatus::Published->value,
                'published_at' => now(),
            ], $author);
            $count++;
        }

        return $count;
    }

    /**
     * @param  list<\App\Models\Content>  $posts
     */
    private function seedComments(array $posts, User $author): int
    {
        $count = 0;
        foreach (array_slice($posts, 0, 4) as $i => $post) {
            $exists = Comment::query()
                ->where('content_id', $post->id)
                ->where('author_email', 'visitor@example.com')
                ->exists();
            if ($exists) {
                continue;
            }

            Comment::query()->create([
                'content_id' => $post->id,
                'user_id' => $i === 0 ? $author->id : null,
                'author_name' => $i === 0 ? $author->name : 'Site Visitor',
                'author_email' => $i === 0 ? $author->email : 'visitor@example.com',
                'author_url' => null,
                'content' => 'Great article — helpful demo content for theme testing.',
                'status' => 'approved',
            ]);
            $count++;
        }

        return $count;
    }

    private function demoBody(string $title): string
    {
        return <<<HTML
<p><strong>{$title}</strong> is sample content for previewing LaravelPress themes and layouts.</p>
<p>You can edit or delete these posts anytime. Theme packages under <code>resources/views/themes</code> can be exported as ZIP files and imported on another install.</p>
<blockquote><p>Dummy data helps you judge typography, spacing, and color tokens before going live.</p></blockquote>
<p>Try switching between Aurora, Meadow, Ink, Paper, Harbor, and Default from Appearance → Themes.</p>
HTML;
    }

    private function purgeDemoContent(): void
    {
        // Soft-delete content with demo slugs only — least destructive.
        $slugs = [
            'welcome-to-laravelpress',
            'choosing-a-theme-for-your-site',
            'working-with-blocks-and-revisions',
            'organizing-content-with-taxonomies',
            'a-short-guide-to-media-uploads',
            'publishing-schedules-and-drafts',
            'comments-moderation-basics',
            'importing-and-exporting-theme-packs',
            'demo-landing',
            'team',
            'faq',
        ];

        \App\Models\Content::query()->whereIn('slug', $slugs)->delete();
    }
}
