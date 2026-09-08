<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\CmsSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('cms.ui_locales', ['en' => 'English', 'ja' => '日本語']));
        $locale = null;

        $sessionLocale = $request->session()->get('locale');
        if (is_string($sessionLocale) && in_array($sessionLocale, $supported, true)) {
            $locale = $sessionLocale;
        }

        if ($locale === null && $request->user()?->locale) {
            $userLocale = (string) $request->user()->locale;
            if (in_array($userLocale, $supported, true)) {
                $locale = $userLocale;
            }
        }

        if ($locale === null) {
            try {
                if (Schema::hasTable('cms_settings')) {
                    $siteLocale = (string) (CmsSetting::getValue('language') ?: '');
                    if (in_array($siteLocale, $supported, true)) {
                        $locale = $siteLocale;
                    }
                }
            } catch (\Throwable) {
                // DB may be unavailable during early setup.
            }
        }

        $locale ??= config('app.locale', 'en');
        if (! in_array($locale, $supported, true)) {
            $locale = $supported[0] ?? 'en';
        }

        App::setLocale($locale);
        $request->attributes->set('locale', $locale);

        return $next($request);
    }
}
