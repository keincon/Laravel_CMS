<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CorsSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CorsSettingsController extends Controller
{
    public function edit(CorsSettingsService $cors): View
    {
        $settings = $cors->current();

        return view('admin.settings.cors', [
            'settings' => $settings,
            'pathsText' => implode("\n", $settings['paths']),
            'originsText' => implode("\n", $settings['allowed_origins']),
            'patternsText' => implode("\n", $settings['allowed_origins_patterns']),
            'headersText' => implode("\n", $settings['allowed_headers']),
            'exposedText' => implode("\n", $settings['exposed_headers']),
            'methodChoices' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'],
        ]);
    }

    public function update(Request $request, CorsSettingsService $cors): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'paths' => ['nullable', 'string', 'max:2000'],
            'allowed_origins' => ['nullable', 'string', 'max:4000'],
            'allowed_origins_patterns' => ['nullable', 'string', 'max:2000'],
            'allowed_methods' => ['nullable', 'array'],
            'allowed_methods.*' => ['string', 'max:16'],
            'allowed_headers' => ['nullable', 'string', 'max:2000'],
            'exposed_headers' => ['nullable', 'string', 'max:2000'],
            'max_age' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'supports_credentials' => ['nullable', 'boolean'],
            'allow_all_methods' => ['nullable', 'boolean'],
        ]);

        $methods = $request->boolean('allow_all_methods')
            ? ['*']
            : array_values($data['allowed_methods'] ?? []);

        $cors->update([
            'enabled' => $request->boolean('enabled'),
            'paths' => $data['paths'] ?? '',
            'allowed_origins' => $data['allowed_origins'] ?? '',
            'allowed_origins_patterns' => $data['allowed_origins_patterns'] ?? '',
            'allowed_methods' => $methods,
            'allowed_headers' => $data['allowed_headers'] ?? '*',
            'exposed_headers' => $data['exposed_headers'] ?? '',
            'max_age' => $data['max_age'] ?? 0,
            'supports_credentials' => $request->boolean('supports_credentials'),
        ]);

        return back()->with('success', __('admin.settings.cors_saved'));
    }
}
