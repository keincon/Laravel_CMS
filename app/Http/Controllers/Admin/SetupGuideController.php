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
        return view('admin.help.setup-guide', [
            'steps' => __('admin.setup_guide.steps'),
            'dynamicItems' => $this->linkedItems(__('admin.setup_guide.dynamic_steps')),
            'themeItems' => $this->linkedItems(__('admin.setup_guide.theme_steps')),
            'howtoItems' => $this->linkedItems(__('admin.setup_guide.howto')),
            'canSeed' => auth()->user()?->can('manage_settings') ?? false,
            'markdownPath' => 'docs/guides/setup-japanese.md',
        ]);
    }

    /**
     * @param  mixed  $items
     * @return list<array{title: string, body: string, url: ?string, button: string}>
     */
    private function linkedItems(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $out = [];
        foreach ($items as $item) {
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

            $out[] = [
                'title' => (string) ($item['title'] ?? ''),
                'body' => (string) ($item['body'] ?? ''),
                'url' => $url,
                'button' => (string) ($item['button'] ?? __('admin.setup_guide.open')),
            ];
        }

        return $out;
    }
}
