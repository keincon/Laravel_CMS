<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Enums\ContentStatus;
use App\Models\Category;
use App\Models\Content;
use App\Models\ContentType;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;
use App\Models\Taxonomy;
use App\Models\Term;
use App\Support\Hooks\Hooks;
use Illuminate\Support\Facades\App;

/**
 * Bidirectional sync between legacy Post/Page tables and LaravelPress contents.
 * No-ops when legacy is retired / dual-write disabled (unless $force).
 */
final class DualWriteContentSync
{
    public function __construct(
        private readonly ?LegacyRetirementService $legacy = null,
    ) {}

    private function legacy(): LegacyRetirementService
    {
        return $this->legacy ?? App::make(LegacyRetirementService::class);
    }

    public function syncPost(Post $post, bool $force = false): ?Content
    {
        if (! $force && ! $this->legacy()->dualWriteEnabled()) {
            return null;
        }

        $type = ContentType::query()->where('slug', 'post')->firstOrFail();

        $content = Content::query()->updateOrCreate(
            [
                'content_type_id' => $type->id,
                'slug' => $post->slug,
            ],
            [
                'title' => $post->title,
                'body' => $post->content,
                'excerpt' => $post->excerpt,
                'status' => ContentStatus::fromLegacy((string) $post->status),
                'author_id' => $post->author_id,
                'featured_media_id' => $post->featured_image_id,
                'comment_status' => $post->comment_status ?: 'open',
                'template' => $post->template,
                'published_at' => $post->published_at,
            ]
        );

        $this->syncPostTerms($content, $post);

        Hooks::action('content.dual_written', $content, $post);

        return $content->fresh(['terms']);
    }

    public function syncPage(Page $page, bool $force = false): ?Content
    {
        if (! $force && ! $this->legacy()->dualWriteEnabled()) {
            return null;
        }

        $type = ContentType::query()->where('slug', 'page')->firstOrFail();

        $parentId = null;
        if ($page->parent_id) {
            $parentPage = Page::query()->find($page->parent_id);
            if ($parentPage) {
                $parentId = Content::query()
                    ->where('content_type_id', $type->id)
                    ->where('slug', $parentPage->slug)
                    ->value('id');
            }
        }

        $content = Content::query()->updateOrCreate(
            [
                'content_type_id' => $type->id,
                'slug' => $page->slug,
            ],
            [
                'title' => $page->title,
                'body' => $page->content,
                'status' => ContentStatus::fromLegacy((string) $page->status),
                'author_id' => $page->author_id,
                'featured_media_id' => $page->featured_image_id,
                'parent_id' => $parentId,
                'template' => $page->template,
                'published_at' => $page->published_at,
                'deleted_at' => $page->deleted_at,
            ]
        );

        Hooks::action('content.dual_written', $content, $page);

        return $content;
    }

    /**
     * Mirror Content mutations back into legacy Post/Page rows (cutover bridge).
     */
    public function syncFromContent(Content $content, bool $force = false): Post|Page|null
    {
        if (! $force && ! $this->legacy()->dualWriteEnabled()) {
            return null;
        }

        $content->loadMissing(['type', 'terms.taxonomy']);
        $slug = $content->type?->slug;

        return match ($slug) {
            'post' => $this->syncContentToPost($content),
            'page' => $this->syncContentToPage($content),
            default => null,
        };
    }

    private function syncContentToPost(Content $content): Post
    {
        $status = $content->status instanceof ContentStatus
            ? $content->status
            : ContentStatus::fromLegacy((string) $content->status);

        $post = Post::query()->updateOrCreate(
            ['slug' => $content->slug],
            [
                'title' => $content->title,
                'content' => $content->body,
                'excerpt' => $content->excerpt,
                'status' => $status->toLegacy(),
                'author_id' => $content->author_id,
                'featured_image_id' => $content->featured_media_id,
                'comment_status' => $content->comment_status ?: 'open',
                'template' => $content->template ?: 'default',
                'published_at' => $content->published_at,
                'header_mode' => 'master',
                'footer_mode' => 'master',
            ]
        );

        $categoryIds = [];
        $tagIds = [];
        foreach ($content->terms as $term) {
            $tax = $term->taxonomy?->slug;
            if ($tax === 'category') {
                $category = Category::query()->updateOrCreate(
                    ['slug' => $term->slug],
                    ['name' => $term->name, 'description' => $term->description]
                );
                $categoryIds[] = $category->id;
            } elseif (in_array($tax, ['post_tag', 'tag'], true)) {
                $tag = Tag::query()->updateOrCreate(
                    ['slug' => $term->slug],
                    ['name' => $term->name, 'description' => $term->description]
                );
                $tagIds[] = $tag->id;
            }
        }
        $post->categories()->sync($categoryIds);
        $post->tags()->sync($tagIds);

        Hooks::action('content.synced_to_legacy', $content, $post);

        return $post;
    }

    private function syncContentToPage(Content $content): Page
    {
        $status = $content->status instanceof ContentStatus
            ? $content->status
            : ContentStatus::fromLegacy((string) $content->status);

        $parentPageId = null;
        if ($content->parent_id) {
            $parentContent = Content::query()->find($content->parent_id);
            if ($parentContent) {
                $parentPageId = Page::query()->where('slug', $parentContent->slug)->value('id');
            }
        }

        $page = Page::query()->updateOrCreate(
            ['slug' => $content->slug],
            [
                'title' => $content->title,
                'content' => $content->body,
                'status' => $status->toLegacy(),
                'author_id' => $content->author_id,
                'featured_image_id' => $content->featured_media_id,
                'parent_id' => $parentPageId,
                'template' => $content->template ?: 'default',
                'published_at' => $content->published_at,
                'header_mode' => 'master',
                'footer_mode' => 'master',
            ]
        );

        Hooks::action('content.synced_to_legacy', $content, $page);

        return $page;
    }

    private function syncPostTerms(Content $content, Post $post): void
    {
        $post->loadMissing(['categories', 'tags']);
        $termIds = [];

        $categoryTaxonomy = Taxonomy::query()->where('slug', 'category')->first();
        if ($categoryTaxonomy) {
            foreach ($post->categories as $category) {
                $term = Term::query()->updateOrCreate(
                    [
                        'taxonomy_id' => $categoryTaxonomy->id,
                        'slug' => $category->slug,
                    ],
                    [
                        'name' => $category->name,
                        'description' => $category->description,
                    ]
                );
                $termIds[] = $term->id;
            }
        }

        $tagTaxonomy = Taxonomy::query()->whereIn('slug', ['post_tag', 'tag'])->first();
        if ($tagTaxonomy) {
            foreach ($post->tags as $tag) {
                $term = Term::query()->updateOrCreate(
                    [
                        'taxonomy_id' => $tagTaxonomy->id,
                        'slug' => $tag->slug,
                    ],
                    [
                        'name' => $tag->name,
                        'description' => $tag->description,
                    ]
                );
                $termIds[] = $term->id;
            }
        }

        $content->terms()->sync(array_values(array_unique($termIds)));
    }
}
