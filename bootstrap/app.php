<?php

use App\Http\Middleware\CheckInstallation;
use App\Http\Middleware\MaintenanceMode;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust reverse proxies (nginx/ALB) so ForceHttps + secure cookies work.
        $middleware->trustProxies(
            at: env('TRUSTED_PROXIES', '*'),
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        $middleware->appendToGroup('web', [
            CheckInstallation::class,
            MaintenanceMode::class,
            \App\Http\Middleware\HandleCmsRedirects::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\ForceHttps::class,
        ]);

        $middleware->appendToGroup('api', [
            CheckInstallation::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\ForceHttps::class,
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*') || $request->is('admin/*') || $request->is('setup/*')) {
                return null;
            }

            try {
                $view = app(\App\Services\PageRendererService::class)->renderNotFound();

                return response($view, 404);
            } catch (\Throwable) {
                return null;
            }
        });
    })->create();
