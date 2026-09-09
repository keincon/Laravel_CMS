<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Content;
use App\Models\DynamicPageSetting;
use App\Models\Page;
use App\Models\Term;

class MenuLinkTargetService
{
    public function __construct(
        protected PermalinkService $permalinks,
    ) {}

    /**
     * Grouped link targets for the menu editor quick-link picker.
     *
     * @return list<array{label: string, options: list<array{value: string, label: string, title: string, url: ?string, page_id: ?int}>}>
     */
    public function groups(): array
    {
        return array_values(array_filter([
            $this->siteGroup(),
            $this->pagesGroup(),
            $this->contentGroup('post', __('admin.menus.link_group_posts'), 40),
            $this->contentGroup('campaign', __('admin.menus.link_group_campaigns'), 40),
            $this->termsGroup(),
        ], fn (array $group): bool => $group['options'] !== []));
    }

    /**
     * @return array{label: string, options: list<array{value: string, label: string, title: string, url: ?string, page_id: ?int}>}
     */
    private function siteGroup(): array
    {
        $options = [
            $this->urlOption('/', __('admin.menus.link_home'), __('admin.menus.link_home')),
        ];

        $archiveTypes = ['blog', 'campaign', 'search', 'archive'];
        foreach (DynamicPageSetting::allCached() as $setting) {
            if (! $setting->is_enabled || ! in_array($setting->type, $archiveTypes, true)) {
                continue;
            }

            $path = $this->normalizePath((string) ($setting->url_path ?: ''));
            if ($path === '' || $path === '/') {
                continue;
            }

            $title = (string) ($setting->title ?: $setting->label ?: $setting->type);
            $options[] = $this->urlOption($path, $title.' ('.$path.')', $title);
        }

        return [
            'label' => __('admin.menus.link_group_site'),
            'options' => $options,
        ];
    }

    /**
     * @return array{label: string, options: list<array{value: string, label: string, title: string, url: ?string, page_id: ?int}>}
     */
    private function pagesGroup(): array
    {
        $options = [];
        $seenPaths = [];

        Page::query()->published()->orderBy('title')->get(['id', 'title', 'slug'])->each(function (Page $page) use (&$options, &$seenPaths): void {
            $path = '/'.ltrim((string) $page->slug, '/');
            $seenPaths[$path] = true;
            $options[] = [
                'value' => 'page:'.$page->id,
                'label' => $page->title.' ('.$path.')',
                'title' => (string) $page->title,
                'url' => $path,
                'page_id' => (int) $page->id,
            ];
        });

        Content::query()
            ->ofType('page')
            ->published()
            ->with('type')
            ->orderBy('title')
            ->limit(80)
            ->get()
            ->each(function (Content $page) use (&$options, &$seenPaths): void {
                $path = '/'.ltrim($this->permalinks->contentPath($page), '/');
                if (isset($seenPaths[$path])) {
                    return;
                }
                $seenPaths[$path] = true;
                $options[] = $this->urlOption($path, $page->title.' ('.$path.')', (string) $page->title);
            });

        return [
            'label' => __('admin.menus.link_group_pages'),
            'options' => $options,
        ];
    }

    /**
     * @return array{label: string, options: list<array{value: string, label: string, title: string, url: ?string, page_id: ?int}>}
     */
    private function contentGroup(string $type, string $label, int $limit): array
    {
        $options = Content::query()
            ->ofType($type)
            ->published()
            ->with('type')
            ->orderByDesc('published_at')
            ->orderBy('title')
            ->limit($limit)
            ->get()
            ->map(function (Content $content) {
                $path = '/'.ltrim($this->permalinks->contentPath($content), '/');

                return $this->urlOption($path, $content->title.' ('.$path.')', (string) $content->title);
            })
            ->all();

        return [
            'label' => $label,
            'options' => $options,
        ];
    }

    /**
     * @return array{label: string, options: list<array{value: string, label: string, title: string, url: ?string, page_id: ?int}>}
     */
    private function termsGroup(): array
    {
        $options = Term::query()
            ->whereHas('taxonomy', fn ($q) => $q->whereIn('slug', ['category', 'tag']))
            ->with('taxonomy')
            ->orderBy('name')
            ->limit(40)
            ->get()
            ->map(function (Term $term) {
                $tax = $term->taxonomy?->slug === 'tag' ? 'tag' : 'category';
                $path = '/'.$tax.'/'.$term->slug;
                $prefix = $tax === 'tag' ? __('admin.menus.link_tag') : __('admin.menus.link_category');

                return $this->urlOption($path, $prefix.': '.$term->name.' ('.$path.')', (string) $term->name);
            })
            ->all();

        return [
            'label' => __('admin.menus.link_group_terms'),
            'options' => $options,
        ];
    }

    /**
     * @return array{value: string, label: string, title: string, url: ?string, page_id: ?int}
     */
    private function urlOption(string $path, string $label, string $title): array
    {
        $path = $this->normalizePath($path);

        return [
            'value' => 'url:'.$path,
            'label' => $label,
            'title' => $title,
            'url' => $path,
            'page_id' => null,
        ];
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path);
        if ($path === '' || $path === '/') {
            return '/';
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return '/'.ltrim($path, '/');
    }
}
