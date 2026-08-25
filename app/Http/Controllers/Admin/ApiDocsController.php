<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SeoService;
use Illuminate\View\View;

class ApiDocsController extends Controller
{
    public function show(SeoService $seo): View
    {
        return view('admin.settings.api', [
            'baseUrl' => $seo->siteUrl().'/api/v1',
            'endpoints' => [
                ['method' => 'GET', 'path' => '/posts', 'auth' => 'Public', 'desc' => 'List published posts'],
                ['method' => 'GET', 'path' => '/posts/{slug}', 'auth' => 'Public', 'desc' => 'Show a published post'],
                ['method' => 'GET', 'path' => '/pages', 'auth' => 'Public', 'desc' => 'List published pages'],
                ['method' => 'GET', 'path' => '/pages/{slug}', 'auth' => 'Public', 'desc' => 'Show a published page'],
                ['method' => 'GET', 'path' => '/categories', 'auth' => 'Public', 'desc' => 'List categories'],
                ['method' => 'GET', 'path' => '/tags', 'auth' => 'Public', 'desc' => 'List tags'],
                ['method' => 'GET', 'path' => '/menus', 'auth' => 'Public', 'desc' => 'List menus'],
                ['method' => 'GET', 'path' => '/theme', 'auth' => 'Public', 'desc' => 'Public theme colors & mode'],
                ['method' => 'GET', 'path' => '/settings', 'auth' => 'Public', 'desc' => 'Safe public site settings'],
                ['method' => 'POST', 'path' => '/admin/posts', 'auth' => 'Bearer token', 'desc' => 'Create post'],
                ['method' => 'PUT', 'path' => '/admin/posts/{id}', 'auth' => 'Bearer token', 'desc' => 'Update post'],
                ['method' => 'DELETE', 'path' => '/admin/posts/{id}', 'auth' => 'Bearer token', 'desc' => 'Delete post'],
                ['method' => 'POST', 'path' => '/admin/pages', 'auth' => 'Bearer token', 'desc' => 'Create page'],
                ['method' => 'PUT', 'path' => '/admin/pages/{id}', 'auth' => 'Bearer token', 'desc' => 'Update page'],
                ['method' => 'DELETE', 'path' => '/admin/pages/{id}', 'auth' => 'Bearer token', 'desc' => 'Delete page'],
            ],
        ]);
    }
}
