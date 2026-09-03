<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Themes\ThemeManager;
use App\Services\Themes\ThemePackageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ThemeAdminController extends Controller
{
    public function index(ThemeManager $themes, ThemePackageService $packs): View
    {
        $themes->syncDiskThemesToDatabase();

        return view('admin.appearance.themes', [
            'themes' => $themes->all(),
            'active' => $themes->activeSlug(),
            'packs' => $packs->bundledPacks(),
        ]);
    }

    public function activate(Request $request, string $theme, ThemeManager $themes): RedirectResponse
    {
        try {
            $themes->activate($theme, applyColors: ! $request->boolean('keep_colors'));
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Theme [{$theme}] activated.");
    }

    public function export(string $theme, ThemePackageService $packs): BinaryFileResponse|RedirectResponse
    {
        try {
            $path = $packs->exportToZip($theme);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return response()->download($path, $theme.'.zip', [
            'Content-Type' => 'application/zip',
        ]);
    }

    public function import(Request $request, ThemePackageService $packs): RedirectResponse
    {
        $data = $request->validate([
            'package' => ['required', 'file', 'mimes:zip', 'max:10240'],
            'activate' => ['sometimes', 'boolean'],
        ]);

        $uploaded = $request->file('package');
        $tmp = storage_path('app/tmp/upload-'.uniqid('theme_', true).'.zip');
        $uploaded->move(dirname($tmp), basename($tmp));

        try {
            $result = $packs->importFromZip($tmp, (bool) ($data['activate'] ?? false));
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        } finally {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
        }

        return back()->with('success', "Imported theme [{$result['slug']}].");
    }

    public function downloadPack(string $slug, ThemePackageService $packs): BinaryFileResponse|RedirectResponse
    {
        $path = storage_path('app/theme-packs/'.$slug.'.zip');
        if (! is_file($path)) {
            try {
                $path = $packs->exportToZip($slug);
            } catch (Throwable $e) {
                return back()->with('error', $e->getMessage());
            }
        }

        return response()->download($path, $slug.'.zip', [
            'Content-Type' => 'application/zip',
        ]);
    }

    public function rebuildPacks(ThemePackageService $packs): RedirectResponse
    {
        $count = $packs->buildBundledPacksFromDisk();

        return back()->with('success', "Built {$count} downloadable theme packs.");
    }

    public function scaffold(Request $request, ThemeManager $themes): RedirectResponse
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'name' => ['nullable', 'string', 'max:120'],
            'activate' => ['sometimes', 'boolean'],
        ]);

        try {
            $result = $themes->scaffold($data['slug'], $data['name'] ?? null);
            if ($request->boolean('activate')) {
                $themes->activate($result['slug']);
            }
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return back()->with(
            'success',
            "Theme [{$result['slug']}] scaffolded with screens, CSS, and JS stubs under resources/views/themes/{$result['slug']}."
        );
    }
}
