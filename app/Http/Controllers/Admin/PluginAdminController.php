<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WordPress\WordPressPluginManager;
use App\Support\Plugins\PluginManager;
use App\Support\Plugins\PluginPackageService;
use App\Support\Plugins\PluginScaffoldService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class PluginAdminController extends Controller
{
    public function index(
        Request $request,
        PluginManager $plugins,
        PluginPackageService $packs,
        WordPressPluginManager $wordpress,
    ): View {
        $plugins->syncDiskToDatabase();
        $tab = $request->query('tab', 'native');
        if (! in_array($tab, ['native', 'wordpress'], true)) {
            $tab = 'native';
        }

        return view('admin.plugins.index', [
            'tab' => $tab,
            'plugins' => $plugins->all(),
            'packs' => $packs->bundledPacks(),
            'wordpress' => $wordpress->status(),
            'wpPlugins' => $tab === 'wordpress' ? $wordpress->listPlugins() : [],
        ]);
    }

    public function activate(string $plugin, PluginManager $plugins): RedirectResponse
    {
        try {
            $plugins->activate($plugin);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Plugin [{$plugin}] activated.");
    }

    public function deactivate(string $plugin, PluginManager $plugins): RedirectResponse
    {
        try {
            $plugins->deactivate($plugin);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Plugin [{$plugin}] deactivated.");
    }

    public function scaffold(Request $request, PluginScaffoldService $scaffold, PluginManager $plugins): RedirectResponse
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'name' => ['nullable', 'string', 'max:120'],
            'activate' => ['sometimes', 'boolean'],
        ]);

        try {
            $result = $scaffold->scaffold($data['slug'], $data['name'] ?? null);
            if ($request->boolean('activate')) {
                $plugins->activate($result['slug']);
            }
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return back()->with('success', "Plugin [{$result['slug']}] scaffolded.");
    }

    public function export(string $plugin, PluginPackageService $packs): BinaryFileResponse|RedirectResponse
    {
        try {
            $path = $packs->exportToZip($plugin);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return response()->download($path, $plugin.'.zip', ['Content-Type' => 'application/zip']);
    }

    public function import(Request $request, PluginPackageService $packs): RedirectResponse
    {
        $data = $request->validate([
            'package' => ['required', 'file', 'mimes:zip', 'max:20480'],
            'activate' => ['sometimes', 'boolean'],
        ]);

        $uploaded = $request->file('package');
        $tmp = storage_path('app/tmp/upload-'.uniqid('plugin_', true).'.zip');
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

        return back()->with('success', "Imported plugin [{$result['slug']}].");
    }

    public function rebuildPacks(PluginPackageService $packs): RedirectResponse
    {
        $count = $packs->buildBundledPacksFromDisk();

        return back()->with('success', "Built {$count} plugin packs.");
    }

    public function wpActivate(Request $request, WordPressPluginManager $wordpress): RedirectResponse
    {
        $data = $request->validate(['plugin' => ['required', 'string', 'max:255']]);
        try {
            $wordpress->activate($data['plugin']);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "WordPress plugin [{$data['plugin']}] activated.");
    }

    public function wpDeactivate(Request $request, WordPressPluginManager $wordpress): RedirectResponse
    {
        $data = $request->validate(['plugin' => ['required', 'string', 'max:255']]);
        try {
            $wordpress->deactivate($data['plugin']);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "WordPress plugin [{$data['plugin']}] deactivated.");
    }

    public function wpUpload(Request $request, WordPressPluginManager $wordpress): RedirectResponse
    {
        $request->validate([
            'package' => ['required', 'file', 'mimes:zip', 'max:51200'],
            'activate' => ['sometimes', 'boolean'],
        ]);

        $uploaded = $request->file('package');
        $tmp = storage_path('app/tmp/upload-'.uniqid('wpplugin_', true).'.zip');
        $uploaded->move(dirname($tmp), basename($tmp));

        try {
            $result = $wordpress->installFromZip($tmp, $request->boolean('activate'));
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        } finally {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
        }

        return back()->with('success', 'WordPress plugin installed: '.($result['plugin'] ?? 'ok'));
    }
}
