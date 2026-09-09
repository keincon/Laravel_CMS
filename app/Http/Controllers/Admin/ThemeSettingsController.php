<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ThemeSetting;
use App\Services\ThemeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThemeSettingsController extends Controller
{
    public function colors(ThemeService $theme): View
    {
        return view('admin.appearance.colors', [
            'settings' => $theme->settings(),
            'colors' => $theme->colors(),
        ]);
    }

    public function updateColors(Request $request, ThemeService $theme): RedirectResponse|JsonResponse
    {
        $rules = [];
        foreach (array_keys(ThemeSetting::defaults()) as $key) {
            if (str_ends_with($key, '_color')) {
                $rules[$key] = ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'];
            }
        }

        $data = $request->validate($rules);
        $settings = $theme->update($data);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'colors' => $theme->colors(),
                'css' => $theme->cssBlock(),
            ]);
        }

        return back()->with('success', __('admin.appearance.colors_saved'));
    }

    public function reset(ThemeService $theme): RedirectResponse
    {
        $theme->reset();

        return back()->with('success', __('admin.appearance.colors_reset_done'));
    }

    public function mode(ThemeService $theme): View
    {
        return view('admin.appearance.mode', [
            'mode' => $theme->mode(),
        ]);
    }

    public function updateMode(Request $request, ThemeService $theme): RedirectResponse
    {
        $data = $request->validate([
            'color_mode' => ['required', 'in:light,dark,system'],
        ]);

        $theme->update($data);

        return back()->with('success', 'Color mode saved.');
    }
}
