<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Footer;
use App\Models\Header;
use App\Models\LayoutSetting;
use App\Models\Page;
use App\Services\LayoutResolverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LayoutSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.appearance.layout', [
            'settings' => LayoutSetting::current(),
            'headers' => Header::query()->orderBy('name')->get(),
            'footers' => Footer::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, LayoutResolverService $layouts): RedirectResponse
    {
        $data = $request->validate([
            'default_header_id' => ['nullable', 'exists:headers,id'],
            'default_footer_id' => ['nullable', 'exists:footers,id'],
            'container_width' => ['required', 'integer', 'min:640', 'max:1920'],
            'content_width' => ['required', 'integer', 'min:480', 'max:1600'],
            'sidebar_width' => ['required', 'integer', 'min:180', 'max:480'],
            'page_layout' => ['required', 'in:standard,full_width'],
            'post_layout' => ['required', 'in:standard,full_width'],
            'sidebar_position' => ['required', 'in:none,left,right'],
        ]);

        $settings = LayoutSetting::current();
        $settings->fill($data)->save();
        $layouts->clearCaches();

        return back()->with('success', 'Master layout settings saved.');
    }

    public function reading(): View
    {
        return view('admin.settings.reading', [
            'settings' => LayoutSetting::current(),
            'pages' => Page::query()->orderBy('title')->get(),
        ]);
    }

    public function updateReading(Request $request, LayoutResolverService $layouts): RedirectResponse
    {
        $data = $request->validate([
            'homepage_type' => ['required', 'in:static,posts'],
            'homepage_page_id' => ['nullable', 'exists:pages,id'],
            'posts_page_id' => ['nullable', 'exists:pages,id'],
        ]);

        $settings = LayoutSetting::current();
        $settings->fill($data)->save();
        $layouts->clearCaches();

        return back()->with('success', __('admin.settings.reading_saved'));
    }
}
