<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Content;
use App\Models\Page;
use App\Models\Post;
use App\Models\SeoSetting;
use App\Models\Tag;
use App\Models\Term;
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
        $seen = [];

        $push = function (string $loc, ?string $lastmod, string $changefreq, string $priority) use (&$urls, &$seen): void {
            if (isset($seen[$loc])) {
                return;
            }
            $seen[$loc] = true;
            $urls[] = compact('loc', 'lastmod', 'changefreq', 'priority');
        };

        $push($this->seo->siteUrl().'/', now()->toAtomString(), 'daily', '1.0');

        Content::query()->ofType('page')->published()->orderBy('slug')->each(function (Content $page) use ($push) {
            if ($page->slug === 'home') {
                return;
            }

            $push(
                $this->seo->siteUrl().'/'.$page->slug,
                optional($page->updated_at)?->toAtomString(),
                'weekly',
                '0.8'
            );
        });

        Page::query()->published()->orderBy('slug')->each(function (Page $page) use ($push) {
            if ($page->slug === 'home') {
                return;
            }

            $push(
                $this->seo->siteUrl().'/'.$page->slug,
                optional($page->updated_at)?->toAtomString(),
                'weekly',
                '0.8'
            );
        });

        Content::query()->ofType('post')->published()->orderByDesc('published_at')->each(function (Content $post) use ($push) {
            $push(
                $this->permalinks->contentUrl($post),
                optional($post->updated_at)?->toAtomString(),
                'weekly',
                '0.7'
            );
        });

        Post::query()->published()->orderByDesc('published_at')->each(function (Post $post) use ($push) {
            $push(
                $this->permalinks->postUrl($post),
                optional($post->updated_at)?->toAtomString(),
                'weekly',
                '0.7'
            );
        });

        Term::query()
            ->whereHas('taxonomy', fn ($q) => $q->where('slug', 'category'))
            ->orderBy('slug')
            ->each(function (Term $term) use ($push) {
                $push(
                    $this->seo->siteUrl().'/'.$this->permalinks->categoryPath($term->slug),
                    optional($term->updated_at)?->toAtomString(),
                    'weekly',
                    '0.5'
                );
            });

        Category::query()->orderBy('slug')->each(function (Category $category) use ($push) {
            $push(
                $this->seo->siteUrl().'/'.$this->permalinks->categoryPath($category->slug),
                optional($category->updated_at)?->toAtomString(),
                'weekly',
                '0.5'
            );
        });

        Term::query()
            ->whereHas('taxonomy', fn ($q) => $q->whereIn('slug', ['post_tag', 'tag']))
            ->orderBy('slug')
            ->each(function (Term $term) use ($push) {
                $push(
                    $this->seo->siteUrl().'/'.$this->permalinks->tagPath($term->slug),
                    optional($term->updated_at)?->toAtomString(),
                    'weekly',
                    '0.4'
                );
            });

        Tag::query()->orderBy('slug')->each(function (Tag $tag) use ($push) {
            $push(
                $this->seo->siteUrl().'/'.$this->permalinks->tagPath($tag->slug),
                optional($tag->updated_at)?->toAtomString(),
                'weekly',
                '0.4'
            );
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
