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

            return back()->with('error', 'Could not install dummy data. Check logs for details.');
        }

        return back()->with(
            'success',
            sprintf(
                'Dummy data installed: %d posts, %d pages, %d terms, %d comments, %d media.',
                $result['posts'],
                $result['pages'],
                $result['terms'],
                $result['comments'],
                $result['media'],
            )
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

            return back()->with('error', 'Could not install Aoyama Card site data. Check logs for details.');
        }

        return back()->with(
            'success',
            sprintf(
                '青山キャピタル site seeded: %d pages, %d posts, %d terms, %d menu items, %d media. Theme: aoyama.',
                $result['pages'],
                $result['posts'],
                $result['terms'],
                $result['menu_items'],
                $result['media'] ?? 0,
            )
        );
    }
}
