<?php

namespace App\Http\Middleware;

use App\Models\CmsSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! CmsSetting::getValue('maintenance_mode', false)) {
            return $next($request);
        }

        if ($request->is('admin*') || $request->is('login') || $request->is('logout') || $request->is('setup*') || $request->is('up')) {
            return $next($request);
        }

        if ($request->user()?->can('manage_settings') || $request->user()?->isAdministrator()) {
            return $next($request);
        }

        $message = (string) CmsSetting::getValue(
            'maintenance_message',
            'Briefly unavailable for scheduled maintenance. Check back in a minute.'
        );

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 503);
        }

        return response()->view('site.maintenance', [
            'message' => $message,
            'siteName' => CmsSetting::getValue('site_name') ?: config('app.name'),
        ], 503);
    }
}
