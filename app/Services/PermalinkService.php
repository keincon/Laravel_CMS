<?php

namespace App\Services;

use App\Models\Post;
use App\Models\SeoSetting;

class PermalinkService
{
    public const STRUCTURE_POSTS = 'posts_slug';

    public const STRUCTURE_BLOG = 'blog_slug';

    public const STRUCTURE_ROOT = 'root_slug';

    /**
     * @return array<string, string>
     */
    public function options(): array
    {
        return [
            self::STRUCTURE_POSTS => '/posts/{slug}',
            self::STRUCTURE_BLOG => '/blog/{slug}',
            self::STRUCTURE_ROOT => '/{slug}',
        ];
    }

    public function structure(): string
    {
        $value = SeoSetting::current()->permalink_structure ?: self::STRUCTURE_BLOG;

        return array_key_exists($value, $this->options()) ? $value : self::STRUCTURE_BLOG;
    }

    public function postPath(Post $post): string
    {
        return match ($this->structure()) {
            self::STRUCTURE_POSTS => 'posts/'.$post->slug,
            self::STRUCTURE_ROOT => $post->slug,
            default => 'blog/'.$post->slug,
        };
    }

    public function postUrl(Post $post): string
    {
        return app(SeoService::class)->siteUrl().'/'.$this->postPath($post);
    }

    public function categoryPath(string $slug): string
    {
        return 'category/'.$slug;
    }

    public function tagPath(string $slug): string
    {
        return 'tag/'.$slug;
    }
}
