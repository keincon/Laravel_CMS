<?php

namespace App\Http\Middleware;

use App\Services\InstallationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class CheckInstallation
{
    public function __construct(
        protected InstallationService $installation,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $installed = $this->installation->isInstalled();
        $isSetup = $request->is('setup') || $request->is('setup/*');
        $isCompletePage = $request->routeIs('setup.complete');

        if (! $installed && ! $isSetup) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'CMS is not installed.',
                ], 503);
            }

            return redirect()->route('setup.welcome');
        }

        // Allow the one-time completion screen after install finishes.
        if ($installed && $isSetup) {
            if ($isCompletePage && Session::get('setup.just_completed')) {
                return $next($request);
            }

            abort(404, 'Installation already completed.');
        }

        return $next($request);
    }

    protected function shouldSkip(Request $request): bool
    {
        return $request->is([
            'up',
            'health',
            'storage/*',
            'build/*',
            'css/*',
            'js/*',
            'favicon.ico',
            'robots.txt',
            'sitemap.xml',
        ]);
    }
}
