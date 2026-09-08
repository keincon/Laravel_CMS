<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Seeders\AoyamaCardSiteSeeder;
use App\Services\Themes\DemoContentSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class DemoDataController extends Controller
{
    public function install(Request $request, DemoContentSeeder $seeder): RedirectResponse
    {
        $data = $request->validate([
            'fresh' => ['nullable', 'boolean'],
        ]);

        try {
            $result = $seeder->seed(
                author: $request->user(),
                fresh: (bool) ($data['fresh'] ?? false),
            );
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', __('admin.settings.demo_error'));
        }

        return back()->with(
            'success',
            __('admin.settings.demo_success', [
                'posts' => $result['posts'],
                'pages' => $result['pages'],
                'terms' => $result['terms'],
                'comments' => $result['comments'],
                'media' => $result['media'],
            ])
        );
    }

    public function installAoyama(Request $request, AoyamaCardSiteSeeder $seeder): RedirectResponse
    {
        $data = $request->validate([
            'fresh' => ['nullable', 'boolean'],
        ]);

        try {
            $result = $seeder->seed(
                author: $request->user(),
                fresh: (bool) ($data['fresh'] ?? false),
            );
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', __('admin.settings.aoyama_error'));
        }

        return back()->with(
            'success',
            __('admin.settings.aoyama_success', [
                'pages' => $result['pages'],
                'posts' => $result['posts'],
                'terms' => $result['terms'],
                'menu_items' => $result['menu_items'],
                'media' => $result['media'] ?? 0,
            ])
        );
    }
}
