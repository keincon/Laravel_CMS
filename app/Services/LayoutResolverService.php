<?php

namespace App\Services;

use App\Models\CmsSetting;
use App\Models\Footer;
use App\Models\Header;
use App\Models\LayoutSetting;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class LayoutResolverService
{
    public function layoutSettings(): LayoutSetting
    {
        return LayoutSetting::current();
    }

    /**
     * Resolve which header to render for the given content context.
     *
     * Priority:
     * 1. Content override (disable / custom)
     * 2. Template override (no_header / blank)
     * 3. Assignment / visibility rules
     * 4. Global master header
     *
     * @return array{show: bool, header: ?Header, structure: ?array}
     */
    public function resolveHeader(?Model $content = null, string $context = 'page'): array
    {
        if ($content && method_exists($content, 'getAttribute')) {
            $mode = $content->getAttribute('header_mode') ?: 'master';
            $template = $content->getAttribute('template') ?: 'default';

            if ($mode === 'disable' || in_array($template, ['no_header', 'blank'], true)) {
                return ['show' => false, 'header' => null, 'structure' => null];
            }

            if ($mode === 'custom' && $content->getAttribute('header_id')) {
                $header = Header::query()->find($content->getAttribute('header_id'));
                if ($header && $header->status === 'published') {
                    return ['show' => true, 'header' => $header, 'structure' => $header->publishedStructure()];
                }
            }
        }

        $header = $this->defaultHeader();
        if (! $header) {
            return ['show' => false, 'header' => null, 'structure' => null];
        }

        if (! $this->passesVisibility($header->publishedStructure()['visibility'] ?? [], $content, $context)) {
            return ['show' => false, 'header' => $header, 'structure' => null];
        }

        return ['show' => true, 'header' => $header, 'structure' => $header->publishedStructure()];
    }

    /**
     * @return array{show: bool, footer: ?Footer, structure: ?array}
     */
    public function resolveFooter(?Model $content = null, string $context = 'page'): array
    {
        if ($content && method_exists($content, 'getAttribute')) {
            $mode = $content->getAttribute('footer_mode') ?: 'master';
            $template = $content->getAttribute('template') ?: 'default';

            if ($mode === 'disable' || in_array($template, ['no_footer', 'blank'], true)) {
                return ['show' => false, 'footer' => null, 'structure' => null];
            }

            if ($mode === 'custom' && $content->getAttribute('footer_id')) {
                $footer = Footer::query()->find($content->getAttribute('footer_id'));
                if ($footer && $footer->status === 'published') {
                    return ['show' => true, 'footer' => $footer, 'structure' => $footer->publishedStructure()];
                }
            }
        }

        $footer = $this->defaultFooter();
        if (! $footer) {
            return ['show' => false, 'footer' => null, 'structure' => null];
        }

        if (! $this->passesVisibility($footer->publishedStructure()['visibility'] ?? [], $content, $context)) {
            return ['show' => false, 'footer' => $footer, 'structure' => null];
        }

        return ['show' => true, 'footer' => $footer, 'structure' => $footer->publishedStructure()];
    }

    public function defaultHeader(): ?Header
    {
        return Cache::remember('cms.default_header', 3600, function () {
            $layout = $this->layoutSettings();
            if ($layout->default_header_id) {
                return Header::query()->where('id', $layout->default_header_id)->where('status', 'published')->first();
            }

            return Header::query()->where('is_default', true)->where('status', 'published')->first()
                ?? Header::query()->where('status', 'published')->orderBy('id')->first();
        });
    }

    public function defaultFooter(): ?Footer
    {
        return Cache::remember('cms.default_footer', 3600, function () {
            $layout = $this->layoutSettings();
            if ($layout->default_footer_id) {
                return Footer::query()->where('id', $layout->default_footer_id)->where('status', 'published')->first();
            }

            return Footer::query()->where('is_default', true)->where('status', 'published')->first()
                ?? Footer::query()->where('status', 'published')->orderBy('id')->first();
        });
    }

    public function primaryMenu(): ?Menu
    {
        return Cache::remember('cms.menu.primary', 3600, function () {
            return Menu::query()->with(['items.page'])->where('slug', 'primary')->first();
        });
    }

    /**
     * @return array{
     *   container_width: int,
     *   content_width: int,
     *   sidebar_width: int,
     *   sidebar_position: string,
     *   page_layout: string,
     *   post_layout: string,
     *   css: string
     * }
     */
    public function dimensionCss(): array
    {
        $layout = $this->layoutSettings();

        $vars = [
            '--site-container-width' => $layout->container_width.'px',
            '--site-content-width' => $layout->content_width.'px',
            '--site-sidebar-width' => $layout->sidebar_width.'px',
        ];

        $css = ":root {\n";
        foreach ($vars as $k => $v) {
            $css .= "    {$k}: {$v};\n";
        }
        $css .= "}\n";

        return [
            'container_width' => (int) $layout->container_width,
            'content_width' => (int) $layout->content_width,
            'sidebar_width' => (int) $layout->sidebar_width,
            'sidebar_position' => $layout->sidebar_position,
            'page_layout' => $layout->page_layout,
            'post_layout' => $layout->post_layout,
            'css' => $css,
        ];
    }

    public function resolveSidebar(?Model $content = null, string $context = 'page'): string
    {
        if ($content && $content->getAttribute('sidebar_position')) {
            return (string) $content->getAttribute('sidebar_position');
        }

        $layout = $this->layoutSettings();
        $template = $content?->getAttribute('template');

        if (in_array($template, ['full_width', 'landing', 'blank'], true)) {
            return 'none';
        }

        if ($context === 'post' && $layout->post_layout === 'full_width') {
            return 'none';
        }

        if ($context === 'page' && $layout->page_layout === 'full_width') {
            return 'none';
        }

        return $layout->sidebar_position ?: 'none';
    }

    public function clearCaches(): void
    {
        Header::forgetCache();
        Footer::forgetCache();
        LayoutSetting::forgetCache();
        Cache::forget('cms.menu.primary');
        Cache::forget('cms.menu.all');
    }

    /**
     * @param  array{show_on?: list<string>, hide_on?: list<string>}  $visibility
     */
    protected function passesVisibility(array $visibility, ?Model $content, string $context): bool
    {
        $hideOn = $visibility['hide_on'] ?? [];
        $route = request()->route()?->getName() ?? '';

        if (in_array('admin', $hideOn, true) && str_starts_with((string) $route, 'admin.')) {
            return false;
        }
        if (in_array('login', $hideOn, true) && in_array($route, ['login'], true)) {
            return false;
        }
        if (in_array('setup', $hideOn, true) && str_starts_with((string) $route, 'setup.')) {
            return false;
        }

        if ($content instanceof Page && in_array('landing', $hideOn, true) && ($content->template ?? '') === 'landing') {
            return false;
        }

        $showOn = $visibility['show_on'] ?? ['entire_website'];
        if (in_array('entire_website', $showOn, true)) {
            return true;
        }

        if ($content instanceof Page && in_array('pages', $showOn, true)) {
            return true;
        }
        if ($content instanceof Post && in_array('posts', $showOn, true)) {
            return true;
        }
        if ($context === 'home' && in_array('homepage', $showOn, true)) {
            return true;
        }
        if ($context === 'blog' && in_array('blog', $showOn, true)) {
            return true;
        }

        return false;
    }

    public function siteName(): string
    {
        return (string) CmsSetting::getValue('site_name', config('cms.name', 'Laravel CMS'));
    }
}
