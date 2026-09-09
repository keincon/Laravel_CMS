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
        $base = $this->blogBase();

        return [
            self::STRUCTURE_POSTS => '/posts/{slug}',
            self::STRUCTURE_BLOG => '/'.$base.'/{slug}',
            self::STRUCTURE_ROOT => '/{slug}',
            self::STRUCTURE_DATED => '/'.$base.'/{year}/{month}/{slug}',
        ];
    }

    public function structure(): string
    {
        $value = SeoSetting::current()->permalink_structure ?: self::STRUCTURE_BLOG;

        return array_key_exists($value, $this->options()) ? $value : self::STRUCTURE_BLOG;
    }

    public function blogIndexPath(): string
    {
        return $this->blogBase();
    }

    public function blogIndexUrl(): string
    {
        return url('/'.$this->blogIndexPath());
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

        if ($type === 'campaign') {
            return 'campaign/'.$content->slug;
        }

        return $this->resolvePath($content->slug, $content->published_at);
    }

    public function postUrl(Post|Content $post): string
    {
        if ($post instanceof Content) {
            return $this->contentUrl($post);
        }

        return url('/'.$this->postPath($post));
    }

    public function entryUrl(Post|Content $entry): string
    {
        return $this->postUrl($entry);
    }

    public function contentUrl(Content $content): string
    {
        return url('/'.$this->contentPath($content));
    }

    public function categoryPath(string $slug): string
    {
        return 'category/'.$slug;
    }

    public function tagPath(string $slug): string
    {
        return 'tag/'.$slug;
    }

    private function blogBase(): string
    {
        try {
            $path = (string) (app(DynamicPageService::class)->get('blog')->url_path ?: '/blog');
        } catch (\Throwable) {
            $path = '/blog';
        }

        $base = trim($path, '/');

        return $base !== '' ? $base : 'blog';
    }

    private function resolvePath(string $slug, mixed $publishedAt): string
    {
        $base = $this->blogBase();

        return match ($this->structure()) {
            self::STRUCTURE_POSTS => 'posts/'.$slug,
            self::STRUCTURE_ROOT => $slug,
            self::STRUCTURE_DATED => sprintf(
                '%s/%s/%s/%s',
                $base,
                optional($publishedAt)?->format('Y') ?? now()->format('Y'),
                optional($publishedAt)?->format('m') ?? now()->format('m'),
                $slug,
            ),
            default => $base.'/'.$slug,
        };
    }
}
