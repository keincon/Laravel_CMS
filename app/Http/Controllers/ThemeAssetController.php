<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Themes\ThemeManager;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves theme package assets (CSS/images) without exposing the views tree.
 */
final class ThemeAssetController extends Controller
{
    private const ALLOWED_EXTENSIONS = ['css', 'js', 'map', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'woff', 'woff2', 'ttf', 'ico'];

    public function show(string $theme, string $path, ThemeManager $themes): BinaryFileResponse|Response
    {
        $path = str_replace('\\', '/', $path);
        if ($path === '' || str_contains($path, '..') || str_starts_with($path, '/')) {
            abort(404);
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            abort(404);
        }

        $manifest = $themes->get($theme);
        if ($manifest === null) {
            abort(404);
        }

        $full = rtrim((string) $manifest['path'], DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.'assets'
            .DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);

        if (! File::isFile($full)) {
            abort(404);
        }

        $realBase = realpath((string) $manifest['path'].DIRECTORY_SEPARATOR.'assets');
        $realFile = realpath($full);
        if ($realBase === false || $realFile === false || ! str_starts_with($realFile, $realBase)) {
            abort(404);
        }

        $mime = match ($ext) {
            'css' => 'text/css; charset=UTF-8',
            'js' => 'application/javascript; charset=UTF-8',
            'svg' => 'image/svg+xml',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            default => File::mimeType($full) ?: 'application/octet-stream',
        };

        return response()->file($full, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
