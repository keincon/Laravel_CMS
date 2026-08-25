<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\LayoutSetting;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\PermalinkService;
use App\Services\SitemapService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SiteController extends Controller
{
    public function home(Request $request): View
    {
        $layout = LayoutSetting::current();

        if ($layout->homepage_type === 'posts') {
            $posts = Post::query()->published()->latest('published_at')->paginate(10);
            $page = $layout->postsPage;

            return view('site.blog', compact('posts', 'page'));
        }

        $page = $layout->homepagePage
            ?? Page::query()->published()->where('slug', 'home')->first();

        return view('site.home', compact('page'));
    }

    public function page(string $slug, PermalinkService $permalinks): View
    {
        if ($permalinks->structure() === PermalinkService::STRUCTURE_ROOT) {
            $post = Post::query()->published()->where('slug', $slug)->first();
            if ($post) {
                $seoPath = $permalinks->postPath($post);

                return view('site.post', compact('post', 'seoPath'));
            }
        }

        $page = Page::query()->published()->where('slug', $slug)->firstOrFail();

        return view('site.page', compact('page'));
    }

    public function blog(Request $request): View
    {
        $query = Post::query()->published()->with('author')->latest('published_at');

        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'ilike', $term)->orWhere('content', 'ilike', $term);
            });
        }

        $posts = $query->paginate(10)->withQueryString();
        $layout = LayoutSetting::current();
        $page = $layout->postsPage
            ?? Page::query()->published()->where('slug', 'blog')->first();

        return view('site.blog', compact('posts', 'page'));
    }

    public function post(string $slug, PermalinkService $permalinks): View
    {
        $post = Post::query()->published()->with('author')->where('slug', $slug)->firstOrFail();
        $seoPath = $permalinks->postPath($post);

        return view('site.post', compact('post', 'seoPath'));
    }

    public function category(string $slug): View
    {
        $category = Category::query()->where('slug', $slug)->firstOrFail();
        $posts = $category->posts()->published()->latest('published_at')->paginate(10);

        return view('site.category', compact('category', 'posts'));
    }

    public function tag(string $slug): View
    {
        $tag = Tag::query()->where('slug', $slug)->firstOrFail();
        $posts = $tag->posts()->published()->latest('published_at')->paginate(10);

        return view('site.tag', compact('tag', 'posts'));
    }

    public function author(string $username): View
    {
        $author = User::query()->where('username', $username)->firstOrFail();
        $posts = Post::query()->published()->where('author_id', $author->id)->latest('published_at')->paginate(10);

        return view('site.author', compact('author', 'posts'));
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
