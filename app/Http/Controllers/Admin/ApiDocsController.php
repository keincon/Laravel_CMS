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
                ['method' => 'GET', 'path' => '/posts', 'auth' => 'public', 'desc' => 'posts_list'],
                ['method' => 'GET', 'path' => '/posts/{slug}', 'auth' => 'public', 'desc' => 'posts_show'],
                ['method' => 'GET', 'path' => '/pages', 'auth' => 'public', 'desc' => 'pages_list'],
                ['method' => 'GET', 'path' => '/pages/{slug}', 'auth' => 'public', 'desc' => 'pages_show'],
                ['method' => 'GET', 'path' => '/categories', 'auth' => 'public', 'desc' => 'categories_list'],
                ['method' => 'GET', 'path' => '/tags', 'auth' => 'public', 'desc' => 'tags_list'],
                ['method' => 'GET', 'path' => '/menus', 'auth' => 'public', 'desc' => 'menus_list'],
                ['method' => 'GET', 'path' => '/theme', 'auth' => 'public', 'desc' => 'theme_show'],
                ['method' => 'GET', 'path' => '/settings', 'auth' => 'public', 'desc' => 'settings_show'],
                ['method' => 'POST', 'path' => '/admin/posts', 'auth' => 'bearer', 'desc' => 'posts_create'],
                ['method' => 'PUT', 'path' => '/admin/posts/{id}', 'auth' => 'bearer', 'desc' => 'posts_update'],
                ['method' => 'DELETE', 'path' => '/admin/posts/{id}', 'auth' => 'bearer', 'desc' => 'posts_delete'],
                ['method' => 'POST', 'path' => '/admin/pages', 'auth' => 'bearer', 'desc' => 'pages_create'],
                ['method' => 'PUT', 'path' => '/admin/pages/{id}', 'auth' => 'bearer', 'desc' => 'pages_update'],
                ['method' => 'DELETE', 'path' => '/admin/pages/{id}', 'auth' => 'bearer', 'desc' => 'pages_delete'],
            ],
        ]);
    }
}
