<?php

namespace App\Services;

use App\Models\Content;
use App\Models\Post;
use App\Models\SeoSetting;

class PermalinkService
{
    public const STRUCTURE_POSTS = 'posts_slug';

    public const STRUCTURE_BLOG = 'blog_slug';

    public const STRUCTURE_ROOT = 'root_slug';

    public const STRUCTURE_DATED = 'dated_slug';

    /**
     * @return array<string, string>
     */
    public function options(): array
    {
        return [
            self::STRUCTURE_POSTS => '/posts/{slug}',
            self::STRUCTURE_BLOG => '/blog/{slug}',
            self::STRUCTURE_ROOT => '/{slug}',
            self::STRUCTURE_DATED => '/blog/{year}/{month}/{slug}',
        ];
    }

    public function structure(): string
    {
        $value = SeoSetting::current()->permalink_structure ?: self::STRUCTURE_BLOG;

        return array_key_exists($value, $this->options()) ? $value : self::STRUCTURE_BLOG;
    }

    public function postPath(Post $post): string
    {
        return $this->resolvePath($post->slug, $post->published_at);
    }

    public function contentPath(Content $content): string
    {
        $type = $content->type?->slug ?? 'post';

        if ($type === 'page') {
            return $content->slug;
        }

        return $this->resolvePath($content->slug, $content->published_at);
    }

    public function postUrl(Post|Content $post): string
    {
        if ($post instanceof Content) {
            return $this->contentUrl($post);
        }

        return app(SeoService::class)->siteUrl().'/'.$this->postPath($post);
    }

    public function entryUrl(Post|Content $entry): string
    {
        return $this->postUrl($entry);
    }

    public function contentUrl(Content $content): string
    {
        return app(SeoService::class)->siteUrl().'/'.$this->contentPath($content);
    }

    public function categoryPath(string $slug): string
    {
        return 'category/'.$slug;
    }

    public function tagPath(string $slug): string
    {
        return 'tag/'.$slug;
    }

    private function resolvePath(string $slug, mixed $publishedAt): string
    {
        return match ($this->structure()) {
            self::STRUCTURE_POSTS => 'posts/'.$slug,
            self::STRUCTURE_ROOT => $slug,
            self::STRUCTURE_DATED => sprintf(
                'blog/%s/%s/%s',
                optional($publishedAt)?->format('Y') ?? now()->format('Y'),
                optional($publishedAt)?->format('m') ?? now()->format('m'),
                $slug,
            ),
            default => 'blog/'.$slug,
        };
    }
}
