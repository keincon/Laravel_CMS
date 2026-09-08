<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $supported = array_keys(config('cms.ui_locales', ['en' => 'English', 'ja' => '日本語']));

        $data = $request->validate([
            'locale' => ['required', 'string', Rule::in($supported)],
        ]);

        $locale = $data['locale'];
        $request->session()->put('locale', $locale);

        if ($request->user()) {
            $request->user()->forceFill(['locale' => $locale])->save();
        }

        return back();
    }
}
