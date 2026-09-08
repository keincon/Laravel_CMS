<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

final class SetupGuideController extends Controller
{
    public function show(): View
    {
        $howto = __('admin.setup_guide.howto');
        if (! is_array($howto)) {
            $howto = [];
        }

        $howtoItems = [];
        foreach ($howto as $item) {
            if (! is_array($item)) {
                continue;
            }
            $routeName = (string) ($item['route'] ?? '');
            $params = is_array($item['params'] ?? null) ? $item['params'] : [];
            $url = null;
            if ($routeName !== '' && Route::has($routeName)) {
                try {
                    $url = route($routeName, $params);
                } catch (\Throwable) {
                    $url = null;
                }
            }
            $howtoItems[] = [
                'title' => (string) ($item['title'] ?? ''),
                'body' => (string) ($item['body'] ?? ''),
                'url' => $url,
            ];
        }

        return view('admin.help.setup-guide', [
            'steps' => __('admin.setup_guide.steps'),
            'howtoItems' => $howtoItems,
            'canSeed' => auth()->user()?->can('manage_settings') ?? false,
            'markdownPath' => 'docs/guides/setup-japanese.md',
        ]);
    }
}
