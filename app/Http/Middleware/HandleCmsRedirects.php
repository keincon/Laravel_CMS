<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class HandleCmsRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            $path = '/'.ltrim($request->getPathInfo(), '/');
            if ($path !== '/') {
                $path = rtrim($path, '/') ?: '/';
            }

            try {
                $redirect = Redirect::query()
                    ->where('is_active', true)
                    ->where(function ($q) use ($path, $request) {
                        $q->where('from_path', $path)
                            ->orWhere('from_path', ltrim($path, '/'))
                            ->orWhere('from_path', $request->getPathInfo());
                    })
                    ->first();
            } catch (\Throwable) {
                $redirect = null;
            }

            if ($redirect) {
                $target = $redirect->to_path;
                if (! str_starts_with($target, 'http://') && ! str_starts_with($target, 'https://')) {
                    $target = url($target);
                }

                return redirect()->to($target, (int) $redirect->status_code);
            }
        }

        return $next($request);
    }
}
