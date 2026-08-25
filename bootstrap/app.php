<?php

use App\Http\Middleware\CheckInstallation;
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
        $middleware->appendToGroup('web', [
            CheckInstallation::class,
        ]);

        $middleware->appendToGroup('api', [
            CheckInstallation::class,
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
