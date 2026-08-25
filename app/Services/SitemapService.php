<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\SeoSetting;
use App\Models\Tag;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SitemapService
{
    public function __construct(
        protected SeoService $seo,
        protected PermalinkService $permalinks,
    ) {}

    public function enabled(): bool
    {
        return (bool) SeoSetting::current()->sitemap_enabled;
    }

    public function xml(): SymfonyResponse
    {
        if (! $this->enabled()) {
            abort(404);
        }

        $urls = [];

        $urls[] = [
            'loc' => $this->seo->siteUrl().'/',
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '1.0',
        ];

        Page::query()->published()->orderBy('slug')->each(function (Page $page) use (&$urls) {
            if ($page->slug === 'home') {
                return;
            }

            $urls[] = [
                'loc' => $this->seo->siteUrl().'/'.$page->slug,
                'lastmod' => optional($page->updated_at)?->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        });

        Post::query()->published()->orderByDesc('published_at')->each(function (Post $post) use (&$urls) {
            $urls[] = [
                'loc' => $this->permalinks->postUrl($post),
                'lastmod' => optional($post->updated_at)?->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        });

        Category::query()->orderBy('slug')->each(function (Category $category) use (&$urls) {
            $urls[] = [
                'loc' => $this->seo->siteUrl().'/'.$this->permalinks->categoryPath($category->slug),
                'lastmod' => optional($category->updated_at)?->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.5',
            ];
        });

        Tag::query()->orderBy('slug')->each(function (Tag $tag) use (&$urls) {
            $urls[] = [
                'loc' => $this->seo->siteUrl().'/'.$this->permalinks->tagPath($tag->slug),
                'lastmod' => optional($tag->updated_at)?->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.4',
            ];
        });

        $xml = view('seo.sitemap', ['urls' => $urls])->render();

        return Response::make($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robotsTxt(): SymfonyResponse
    {
        $site = config('app.url', 'http://localhost');

        try {
            if (app(\App\Services\InstallationService::class)->isInstalled()) {
                $site = $this->seo->siteUrl();
            }
        } catch (\Throwable) {
            // Fall back to APP_URL before installation completes.
        }

        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /login',
            'Disallow: /setup',
            '',
        ];

        try {
            if ($this->enabled()) {
                $lines[] = 'Sitemap: '.rtrim($site, '/').'/sitemap.xml';
            }
        } catch (\Throwable) {
            // Sitemap setting unavailable until DB is ready.
        }

        return Response::make(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
