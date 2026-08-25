<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DynamicPageSetting;
use App\Models\Footer;
use App\Models\Header;
use App\Services\DynamicPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DynamicPageController extends Controller
{
    public function __construct(
        protected DynamicPageService $dynamicPages,
    ) {}

    public function index(): View
    {
        return view('admin.appearance.dynamic-pages.index', [
            'pages' => $this->dynamicPages->all(),
            'definitions' => $this->dynamicPages->typeDefinitions(),
        ]);
    }

    public function edit(string $type): View
    {
        abort_unless(isset($this->dynamicPages->typeDefinitions()[$type]), 404);

        return view('admin.appearance.dynamic-pages.edit', [
            'setting' => $this->dynamicPages->get($type),
            'definition' => $this->dynamicPages->typeDefinitions()[$type],
            'headers' => Header::query()->orderBy('name')->get(),
            'footers' => Footer::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, string $type): RedirectResponse
    {
        abort_unless(isset($this->dynamicPages->typeDefinitions()[$type]), 404);

        $setting = $this->dynamicPages->get($type);

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'url_path' => ['nullable', 'string', 'max:255'],
            'posts_per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'layout' => ['nullable', Rule::in(['list', 'grid', 'standard'])],
            'sidebar_position' => ['nullable', Rule::in(['none', 'left', 'right'])],
            'template' => ['nullable', 'string', 'max:50'],
            'header_mode' => ['required', Rule::in(['master', 'custom', 'disable'])],
            'header_id' => ['nullable', 'exists:headers,id'],
            'footer_mode' => ['required', Rule::in(['master', 'custom', 'disable'])],
            'footer_id' => ['nullable', 'exists:footers,id'],
            'is_enabled' => ['sometimes', 'boolean'],
            'seo_title_template' => ['nullable', 'string', 'max:255'],
            'seo_description_template' => ['nullable', 'string'],
            'seo_robots' => ['nullable', 'string', 'max:100'],
            'message' => ['nullable', 'string', 'max:500'],
            'button_label' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'archive_enabled' => ['sometimes', 'boolean'],
        ]);

        $data['is_enabled'] = $request->boolean('is_enabled');
        $settings = $setting->settings ?? [];
        if ($type === 'archive') {
            $settings['enabled'] = $request->boolean('archive_enabled', true);
        }
        $data['settings'] = $settings;

        $setting->fill($data)->save();
        DynamicPageSetting::forgetCache();

        return back()->with('success', 'Dynamic page settings saved.');
    }
}
