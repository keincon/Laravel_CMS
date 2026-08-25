<?php

namespace App\Http\Controllers;

use App\Services\PageRendererService;
use App\Services\SitemapService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SiteController extends Controller
{
    public function __construct(
        protected PageRendererService $renderer,
    ) {}

    public function home(Request $request): View
    {
        return $this->renderer->renderHome($request);
    }

    public function page(string $slug): View
    {
        return $this->renderer->resolveSlug($slug);
    }

    public function blog(Request $request): View
    {
        return $this->renderer->renderBlog($request);
    }

    public function post(string $slug): View
    {
        return $this->renderer->renderPost($slug);
    }

    public function category(string $slug): View
    {
        return $this->renderer->renderCategory($slug);
    }

    public function tag(string $slug): View
    {
        return $this->renderer->renderTag($slug);
    }

    public function author(string $username): View
    {
        return $this->renderer->renderAuthor($username);
    }

    public function search(Request $request): View
    {
        return $this->renderer->renderSearch($request);
    }

    public function archive(?int $year = null, ?int $month = null, ?int $day = null): View
    {
        return $this->renderer->renderArchive($year, $month, $day);
    }

    public function notFound(): View
    {
        return $this->renderer->renderNotFound();
    }

    public function sitemap(SitemapService $sitemap): Response
    {
        return $sitemap->xml();
    }

    public function robots(SitemapService $sitemap): Response
    {
        return $sitemap->robotsTxt();
    }
}
