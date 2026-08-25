<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Http\Resources\Api\V1\PageResource;
use App\Http\Resources\Api\V1\PostResource;
use App\Http\Resources\Api\V1\TagResource;
use App\Models\Category;
use App\Models\CmsSetting;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;
use App\Services\ThemeService;
use Illuminate\Http\Request;

class PublicApiController extends Controller
{
    protected function perPage(Request $request): int
    {
        return min(max((int) $request->integer('per_page', 20), 1), 100);
    }

    public function posts(Request $request)
    {
        $query = Post::query()->published()->with(['author', 'categories', 'tags']);

        if ($request->filled('category')) {
            $query->whereHas('categories', fn ($q) => $q->where('slug', $request->string('category')));
        }
        if ($request->filled('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $request->string('tag')));
        }
        if ($request->filled('author')) {
            $query->where('author_id', $request->integer('author'));
        }
        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'ilike', $term)->orWhere('content', 'ilike', $term);
            });
        }

        return PostResource::collection(
            $query->latest('published_at')->paginate($this->perPage($request))
        );
    }

    public function showPost(string $slug)
    {
        $post = Post::query()->published()->with(['author', 'categories', 'tags'])->where('slug', $slug)->firstOrFail();

        return new PostResource($post);
    }

    public function pages(Request $request)
    {
        return PageResource::collection(
            Page::query()->published()->latest('published_at')->paginate($this->perPage($request))
        );
    }

    public function showPage(string $slug)
    {
        $page = Page::query()->published()->where('slug', $slug)->firstOrFail();

        return new PageResource($page);
    }

    public function categories(Request $request)
    {
        return CategoryResource::collection(
            Category::query()->orderBy('name')->paginate($this->perPage($request))
        );
    }

    public function showCategory(string $slug)
    {
        return new CategoryResource(Category::query()->where('slug', $slug)->firstOrFail());
    }

    public function tags(Request $request)
    {
        return TagResource::collection(
            Tag::query()->orderBy('name')->paginate($this->perPage($request))
        );
    }

    public function showTag(string $slug)
    {
        return new TagResource(Tag::query()->where('slug', $slug)->firstOrFail());
    }

    public function menus()
    {
        $menus = Menu::query()->with(['items' => fn ($q) => $q->orderBy('sort_order')])->get();

        return response()->json([
            'data' => $menus->map(fn (Menu $menu) => [
                'id' => $menu->id,
                'name' => $menu->name,
                'slug' => $menu->slug,
                'location' => $menu->location,
                'items' => $menu->items->map(fn ($item) => [
                    'id' => $item->id,
                    'title' => $item->title,
                    'url' => $item->url,
                    'page_id' => $item->page_id,
                    'sort_order' => $item->sort_order,
                ]),
            ]),
        ]);
    }

    public function settings()
    {
        return response()->json([
            'data' => [
                'site_name' => CmsSetting::getValue('site_name'),
                'site_description' => CmsSetting::getValue('site_description'),
                'site_url' => CmsSetting::getValue('site_url'),
                'language' => CmsSetting::getValue('language'),
                'timezone' => CmsSetting::getValue('timezone'),
                'date_format' => CmsSetting::getValue('date_format'),
                'ui_framework' => CmsSetting::getValue('ui_framework'),
            ],
        ]);
    }

    public function theme(ThemeService $theme)
    {
        return response()->json(['data' => $theme->publicConfig()]);
    }
}
