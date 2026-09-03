<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsSetting;
use App\Models\Media;
use App\Services\UIFrameworkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GeneralSettingsController extends Controller
{
    public function edit(UIFrameworkService $ui): View
    {
        $faviconId = CmsSetting::getValue('site_favicon_media_id');

        return view('admin.settings.general', [
            'frameworks' => $ui->available(),
            'current' => $ui->current(),
            'siteName' => CmsSetting::getValue('site_name') ?: config('app.name'),
            'siteDescription' => CmsSetting::getValue('site_description') ?: '',
            'faviconMediaId' => $faviconId ? (int) $faviconId : null,
            'faviconMedia' => $faviconId ? Media::query()->find($faviconId) : null,
            'maintenanceMode' => (bool) CmsSetting::getValue('maintenance_mode', false),
            'maintenanceMessage' => CmsSetting::getValue(
                'maintenance_message',
                'Briefly unavailable for scheduled maintenance. Check back in a minute.'
            ),
        ]);
    }

    public function update(Request $request, UIFrameworkService $ui): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'site_description' => ['nullable', 'string', 'max:500'],
            'site_favicon_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'ui_framework' => ['required', Rule::in(array_keys($ui->available()))],
            'maintenance_mode' => ['nullable', 'boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:1000'],
        ]);

        CmsSetting::setValue('site_name', $data['site_name']);
        CmsSetting::setValue('site_description', $data['site_description'] ?? '');

        if (! empty($data['site_favicon_media_id'])) {
            CmsSetting::setValue('site_favicon_media_id', (string) $data['site_favicon_media_id'], 'integer');
        } else {
            CmsSetting::query()->where('key', 'site_favicon_media_id')->delete();
            \Illuminate\Support\Facades\Cache::forget('cms_setting.site_favicon_media_id');
        }

        CmsSetting::setValue('maintenance_mode', $request->boolean('maintenance_mode'), 'boolean');
        CmsSetting::setValue(
            'maintenance_message',
            $data['maintenance_message'] ?? 'Briefly unavailable for scheduled maintenance. Check back in a minute.'
        );
        $ui->set($data['ui_framework']);

        return back()->with('success', 'General settings saved.');
    }
}
