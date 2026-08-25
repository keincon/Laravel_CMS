<?php

namespace App\Services;

use App\Models\CmsSetting;
use App\Models\Media;
use App\Models\Page;
use App\Models\Post;
use App\Models\SeoSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SeoService
{
    public function settings(): SeoSetting
    {
        return SeoSetting::current();
    }

    public function siteUrl(): string
    {
        $settings = $this->settings();

        return rtrim(
            $settings->canonical_url
                ?: CmsSetting::getValue('site_url', config('app.url')),
            '/'
        );
    }

    /**
     * Resolve SEO metadata for a content model or global defaults.
     *
     * @return array{
     *     title: string,
     *     description: string,
     *     keywords: ?string,
     *     canonical: string,
     *     robots: string,
     *     image: ?string,
     *     og_title: string,
     *     og_description: string,
     *     og_type: string,
     *     og_image: ?string,
     *     og_url: string,
     *     og_site_name: string,
     *     og_locale: string,
     *     twitter_card: string,
     *     twitter_title: string,
     *     twitter_description: string,
     *     twitter_image: ?string
     * }
     */
    public function resolve(?Model $content = null, ?string $path = null): array
    {
        $global = $this->settings();
        $siteName = CmsSetting::getValue('site_name', config('cms.name', config('app.name')));
        $siteUrl = $this->siteUrl();

        $contentTitle = $content?->title ?? null;
        $contentUrl = $this->contentUrl($content, $path);

        $title = $this->firstFilled(
            $content?->seo_title ?? null,
            $contentTitle,
            $global->seo_title,
            $siteName
        );

        $description = $this->firstFilled(
            $content?->seo_description ?? null,
            $content?->excerpt ?? null,
            $this->excerptFromContent($content?->content ?? null),
            $global->meta_description,
            CmsSetting::getValue('site_description', '')
        );

        $canonical = $this->firstFilled(
            $content?->seo_canonical ?? null,
            $contentUrl,
            $global->canonical_url,
            $siteUrl
        );

        $robots = $this->firstFilled(
            $content?->seo_robots ?? null,
            $global->robots,
            'index, follow'
        );

        $seoImage = $this->mediaUrl($content?->seoImage ?? null)
            ?: $this->mediaUrl($content?->featuredImage ?? null)
            ?: $this->mediaUrl($global->defaultImage)
            ?: $global->organization_logo_url;

        $ogTitle = $this->firstFilled(
            $content?->og_title ?? null,
            $content?->seo_title ?? null,
            $contentTitle,
            $global->og_title,
            $title
        );

        $ogDescription = $this->firstFilled(
            $content?->og_description ?? null,
            $content?->seo_description ?? null,
            $description,
            $global->og_description
        );

        $ogImage = $this->mediaUrl($content?->ogImage ?? null)
            ?: $this->mediaUrl($content?->featuredImage ?? null)
            ?: $this->mediaUrl($global->ogImage)
            ?: CmsSetting::getValue('og_image_url')
            ?: $seoImage;

        $ogType = $this->firstFilled(
            $content?->og_type ?? null,
            $content instanceof Post ? 'article' : null,
            $global->og_type,
            'website'
        );

        $twitterCard = $global->twitter_card ?: 'summary_large_image';

        return [
            'title' => $title,
            'description' => Str::limit(strip_tags((string) $description), 300, ''),
            'keywords' => $global->keywords,
            'canonical' => $canonical,
            'robots' => $robots,
            'image' => $seoImage,
            'og_title' => $ogTitle,
            'og_description' => Str::limit(strip_tags((string) $ogDescription), 300, ''),
            'og_type' => $ogType,
            'og_image' => $ogImage,
            'og_url' => $canonical,
            'og_site_name' => $global->og_site_name ?: $siteName,
            'og_locale' => $global->og_locale ?: 'en_US',
            'twitter_card' => $twitterCard,
            'twitter_title' => $ogTitle,
            'twitter_description' => Str::limit(strip_tags((string) $ogDescription), 300, ''),
            'twitter_image' => $ogImage,
        ];
    }

    public function contentUrl(?Model $content, ?string $path = null): ?string
    {
        if ($path) {
            return $this->siteUrl().'/'.ltrim($path, '/');
        }

        if ($content instanceof Page) {
            if ($content->slug === 'home') {
                return $this->siteUrl().'/';
            }

            return $this->siteUrl().'/'.$content->slug;
        }

        if ($content instanceof Post) {
            return app(PermalinkService::class)->postUrl($content);
        }

        return null;
    }

    /**
     * @return array{website: array<string, mixed>, organization: array<string, mixed>, content?: array<string, mixed>}
     */
    public function jsonLd(?Model $content = null): array
    {
        $meta = $this->resolve($content);
        $siteName = CmsSetting::getValue('site_name', config('cms.name'));
        $global = $this->settings();

        $payload = [
            'website' => [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => $siteName,
                'url' => $this->siteUrl(),
                'description' => $meta['description'],
            ],
            'organization' => [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => $global->organization_name ?: $siteName,
                'url' => $this->siteUrl(),
            ],
        ];

        if ($global->organization_logo_url) {
            $payload['organization']['logo'] = $global->organization_logo_url;
        }

        if ($content instanceof Post) {
            $payload['content'] = [
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $meta['title'],
                'description' => $meta['description'],
                'url' => $meta['canonical'],
                'datePublished' => optional($content->published_at)?->toIso8601String(),
                'dateModified' => optional($content->updated_at)?->toIso8601String(),
                'author' => [
                    '@type' => 'Person',
                    'name' => $content->author?->name ?? $siteName,
                ],
                'image' => array_values(array_filter([$meta['og_image']])),
                'mainEntityOfPage' => $meta['canonical'],
            ];
        } elseif ($content instanceof Page) {
            $payload['content'] = [
                '@context' => 'https://schema.org',
                '@type' => 'WebPage',
                'name' => $meta['title'],
                'description' => $meta['description'],
                'url' => $meta['canonical'],
            ];
        }

        return $payload;
    }

    protected function mediaUrl(?Media $media): ?string
    {
        if (! $media) {
            return null;
        }

        $url = $media->url();

        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        return $this->siteUrl().'/'.ltrim($url, '/');
    }

    protected function excerptFromContent(?string $content): ?string
    {
        if (! $content) {
            return null;
        }

        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($content)) ?? ''), 160, '');
    }

    protected function firstFilled(mixed ...$values): ?string
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}
