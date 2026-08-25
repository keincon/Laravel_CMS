<?php

namespace App\Services;

use App\Models\CmsSetting;
use App\Models\DynamicPageSetting;
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

        $templateType = $content instanceof Post ? 'post' : ($content instanceof Page ? 'page' : null);
        $templated = $templateType
            ? $this->applyContentTemplates($templateType, $content, $siteName)
            : ['title' => null, 'description' => null];

        $title = $this->firstFilled(
            $content?->seo_title ?? null,
            $templated['title'],
            $contentTitle,
            $global->seo_title,
            $siteName
        );

        $description = $this->firstFilled(
            $content?->seo_description ?? null,
            $templated['description'],
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

        return $this->packMeta(
            title: $title,
            description: $description,
            canonical: $canonical,
            robots: $robots,
            seoImage: $seoImage,
            ogTitle: $ogTitle,
            ogDescription: $ogDescription,
            ogType: $ogType,
            ogImage: $ogImage,
            siteName: $siteName,
            twitterCard: $twitterCard,
            keywords: $global->keywords,
            ogSiteName: $global->og_site_name ?: $siteName,
            ogLocale: $global->og_locale ?: 'en_US',
        );
    }

    /**
     * Resolve SEO/OGP for a dynamic page type using templates + optional content model.
     *
     * @param  array<string, string>  $variables
     * @return array<string, mixed>
     */
    public function resolveDynamic(string $type, array $variables = [], ?string $path = null, ?Model $content = null): array
    {
        if ($content instanceof Post || $content instanceof Page) {
            $base = $this->resolve($content, $path);
            // Prefer explicit content SEO when present; otherwise fill from templates.
            if (! empty($content->seo_title) || ! empty($content->seo_description)) {
                return $base;
            }
        }

        $global = $this->settings();
        $siteName = CmsSetting::getValue('site_name', config('cms.name', config('app.name')));
        $variables = array_merge([
            'site_name' => $siteName,
            'post_title' => '',
            'post_excerpt' => '',
            'page_title' => '',
            'page_excerpt' => '',
            'category_name' => '',
            'tag_name' => '',
            'author_name' => '',
            'search_query' => '',
            'archive_label' => '',
        ], $variables);

        $dynamic = DynamicPageSetting::forType($type);
        $templates = $this->templatesFor($type);

        $titleTemplate = $this->firstFilled(
            $dynamic?->seo_title_template,
            $templates['title'] ?? null,
            '{page_title} — {site_name}'
        );
        $descTemplate = $this->firstFilled(
            $dynamic?->seo_description_template,
            $templates['description'] ?? null,
            ''
        );

        $title = $this->renderTemplate((string) $titleTemplate, $variables) ?: $siteName;
        $description = $this->renderTemplate((string) $descTemplate, $variables)
            ?: ($global->meta_description ?: CmsSetting::getValue('site_description', ''));

        $canonical = $this->contentUrl(null, $path) ?: $this->siteUrl();
        $robots = $this->firstFilled(
            $dynamic?->seo_robots,
            $type === 'search' ? 'noindex, follow' : null,
            $type === '404' ? 'noindex, follow' : null,
            $global->robots,
            'index, follow'
        );

        $seoImage = $this->mediaUrl($content?->seoImage ?? null)
            ?: $this->mediaUrl($content?->featuredImage ?? null)
            ?: $this->mediaUrl($global->defaultImage)
            ?: $global->organization_logo_url;

        $ogTitle = $title;
        $ogDescription = $description;
        $ogImage = $this->mediaUrl($content?->ogImage ?? null)
            ?: $this->mediaUrl($content?->featuredImage ?? null)
            ?: $this->mediaUrl($global->ogImage)
            ?: CmsSetting::getValue('og_image_url')
            ?: $seoImage;

        return $this->packMeta(
            title: $title,
            description: $description,
            canonical: $canonical,
            robots: $robots,
            seoImage: $seoImage,
            ogTitle: $ogTitle,
            ogDescription: $ogDescription,
            ogType: $content instanceof Post ? 'article' : ($global->og_type ?: 'website'),
            ogImage: $ogImage,
            siteName: $siteName,
            twitterCard: $global->twitter_card ?: 'summary_large_image',
            keywords: $global->keywords,
            ogSiteName: $global->og_site_name ?: $siteName,
            ogLocale: $global->og_locale ?: 'en_US',
        );
    }

    /**
     * @return array<string, array{title?: string, description?: string}>
     */
    public function allTemplates(): array
    {
        $defaults = config('cms.seo_templates', []);
        $stored = $this->settings()->seo_templates ?? [];

        $merged = [];
        foreach ($defaults as $key => $def) {
            $merged[$key] = [
                'title' => $stored[$key]['title'] ?? $def['title'] ?? '',
                'description' => $stored[$key]['description'] ?? $def['description'] ?? '',
            ];
        }

        return $merged;
    }

    /**
     * @return array{title?: string, description?: string}
     */
    public function templatesFor(string $type): array
    {
        return $this->allTemplates()[$type] ?? [];
    }

    /**
     * @param  array<string, string>  $variables
     */
    public function renderTemplate(string $template, array $variables): string
    {
        $replacements = [];
        foreach ($variables as $key => $value) {
            $replacements['{'.$key.'}'] = (string) $value;
        }

        return trim(strtr($template, $replacements));
    }

    /**
     * @return array{title: ?string, description: ?string}
     */
    protected function applyContentTemplates(string $type, Model $content, string $siteName): array
    {
        $templates = $this->templatesFor($type);
        $vars = [
            'site_name' => $siteName,
            'post_title' => $content->title ?? '',
            'page_title' => $content->title ?? '',
            'post_excerpt' => $content->excerpt ?? $this->excerptFromContent($content->content ?? null) ?? '',
            'page_excerpt' => $content->excerpt ?? $this->excerptFromContent($content->content ?? null) ?? '',
            'author_name' => $content->author?->name ?? '',
        ];

        return [
            'title' => isset($templates['title']) ? $this->renderTemplate($templates['title'], $vars) : null,
            'description' => isset($templates['description']) ? $this->renderTemplate($templates['description'], $vars) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function packMeta(
        ?string $title,
        ?string $description,
        ?string $canonical,
        ?string $robots,
        ?string $seoImage,
        ?string $ogTitle,
        ?string $ogDescription,
        ?string $ogType,
        ?string $ogImage,
        string $siteName,
        string $twitterCard,
        ?string $keywords,
        string $ogSiteName,
        string $ogLocale,
    ): array {
        return [
            'title' => $title ?: $siteName,
            'description' => Str::limit(strip_tags((string) $description), 300, ''),
            'keywords' => $keywords,
            'canonical' => $canonical ?: $this->siteUrl(),
            'robots' => $robots ?: 'index, follow',
            'image' => $seoImage,
            'og_title' => $ogTitle ?: ($title ?: $siteName),
            'og_description' => Str::limit(strip_tags((string) $ogDescription), 300, ''),
            'og_type' => $ogType ?: 'website',
            'og_image' => $ogImage,
            'og_url' => $canonical ?: $this->siteUrl(),
            'og_site_name' => $ogSiteName,
            'og_locale' => $ogLocale,
            'twitter_card' => $twitterCard,
            'twitter_title' => $ogTitle ?: ($title ?: $siteName),
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
