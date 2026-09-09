<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsSetting;
use App\Models\SeoSetting;
use App\Services\PermalinkService;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeoSettingsController extends Controller
{
    public function edit(SeoService $seo): View
    {
        return view('admin.settings.seo', [
            'settings' => $seo->settings(),
            'siteUrl' => $seo->siteUrl(),
        ]);
    }

    public function editTemplates(SeoService $seo): View
    {
        return view('admin.settings.seo-templates', [
            'templates' => $seo->allTemplates(),
        ]);
    }

    public function updateTemplates(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'templates' => ['required', 'array'],
            'templates.*.title' => ['nullable', 'string', 'max:255'],
            'templates.*.description' => ['nullable', 'string', 'max:1000'],
        ]);

        $settings = SeoSetting::current();
        $settings->seo_templates = $data['templates'];
        $settings->save();
        SeoSetting::forgetCache();

        return back()->with('success', 'SEO templates saved.');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'seo_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'robots' => ['nullable', 'string', 'max:100'],
            'sitemap_enabled' => ['nullable', 'boolean'],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'organization_logo_url' => ['nullable', 'url', 'max:255'],
        ]);

        $data['sitemap_enabled'] = $request->boolean('sitemap_enabled');

        $settings = SeoSetting::current();
        $settings->fill($data)->save();
        SeoSetting::forgetCache();

        return back()->with('success', 'SEO settings saved.');
    }

    public function editOgp(SeoService $seo): View
    {
        return view('admin.settings.ogp', [
            'settings' => $seo->settings(),
            'siteUrl' => $seo->siteUrl(),
            'siteName' => CmsSetting::getValue('site_name', config('cms.name')),
        ]);
    }

    public function updateOgp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:1000'],
            'og_type' => ['nullable', 'string', 'max:50'],
            'og_site_name' => ['nullable', 'string', 'max:255'],
            'og_locale' => ['nullable', 'string', 'max:20'],
            'twitter_card' => ['required', 'in:summary,summary_large_image'],
            'og_image_url' => ['nullable', 'string', 'max:255'],
        ]);

        if (! empty($data['og_image_url']) && ! filter_var($data['og_image_url'], FILTER_VALIDATE_URL)) {
            return back()
                ->withInput()
                ->withErrors(['og_image_url' => __('admin.ogp.invalid_image_url')]);
        }

        $settings = SeoSetting::current();
        $settings->fill(collect($data)->except('og_image_url')->all());
        $settings->save();

        if (array_key_exists('og_image_url', $data)) {
            CmsSetting::setValue('og_image_url', (string) ($data['og_image_url'] ?? ''));
        }

        SeoSetting::forgetCache();

        return back()->with('success', __('admin.ogp.saved'));
    }

    public function editPermalinks(PermalinkService $permalinks): View
    {
        return view('admin.settings.permalinks', [
            'structure' => $permalinks->structure(),
            'options' => $permalinks->options(),
        ]);
    }

    public function updatePermalinks(Request $request, PermalinkService $permalinks): RedirectResponse
    {
        $data = $request->validate([
            'permalink_structure' => ['required', 'in:'.implode(',', array_keys($permalinks->options()))],
        ]);

        $settings = SeoSetting::current();
        $settings->permalink_structure = $data['permalink_structure'];
        $settings->save();
        SeoSetting::forgetCache();

        return back()->with('success', 'Permalink structure saved.');
    }
}
