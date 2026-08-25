<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CmsSetting;
use App\Models\DynamicPageSetting;
use App\Models\LayoutSetting;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageRendererService
{
    public function __construct(
        protected DynamicPageService $dynamicPages,
        protected SeoService $seo,
        protected PermalinkService $permalinks,
        protected LayoutResolverService $layouts,
    ) {}

    public function renderHome(Request $request): View
    {
        $layout = LayoutSetting::current();

        if ($layout->homepage_type === 'posts') {
            return $this->renderBlog($request, isHomepage: true);
        }

        $page = $layout->homepagePage
            ?? Page::query()->published()->where('slug', 'home')->first();

        if (! $page) {
            return $this->renderBlog($request, isHomepage: true);
        }

        return $this->renderStatic($page, context: 'home');
    }

    public function renderStatic(Page $page, string $context = 'page'): View
    {
        $view = $this->dynamicPages->staticPageView($page->template);
        $config = null;

        return view($view, [
            'page' => $page,
            'pageKind' => 'static',
            'dynamicConfig' => $config,
            'seoMeta' => $this->seo->resolve($page),
            'seoPath' => $page->slug === 'home' ? '/' : $page->slug,
            'context' => $context,
        ]);
    }

    public function renderBlog(Request $request, bool $isHomepage = false): View
    {
        $config = $this->requireEnabled('blog');
        $perPage = $this->dynamicPages->postsPerPage('blog');

        $query = Post::query()->published()->with(['author', 'categories', 'tags'])->latest('published_at');

        $posts = $query->paginate($perPage)->withQueryString();

        $seoVars = [
            'page_title' => $config->title ?: 'Blog',
            'page_excerpt' => $config->description ?: '',
        ];

        return $this->renderDynamic('blog', [
            'posts' => $posts,
            'page' => null,
            'dynamicConfig' => $config,
            'isHomepage' => $isHomepage,
            'layoutStyle' => $config->layout ?: 'list',
            'seoMeta' => $this->seo->resolveDynamic('blog', $seoVars, $isHomepage ? '/' : ltrim($config->url_path ?: '/blog', '/')),
            'seoPath' => $isHomepage ? '/' : ltrim($config->url_path ?: '/blog', '/'),
            'context' => 'blog',
            'breadcrumbs' => $this->breadcrumbs([
                ['label' => 'Home', 'url' => url('/')],
                ['label' => $config->title ?: 'Blog', 'url' => null],
            ]),
        ]);
    }

    public function renderPost(string $slug): View
    {
        $config = $this->requireEnabled('post');
        $post = Post::query()->published()->with(['author', 'categories', 'tags'])->where('slug', $slug)->firstOrFail();
        $seoPath = $this->permalinks->postPath($post);

        $related = Post::query()
            ->published()
            ->where('id', '!=', $post->id)
            ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $post->categories->pluck('id')))
            ->latest('published_at')
            ->limit(3)
            ->get();

        $seoVars = [
            'post_title' => $post->title,
            'post_excerpt' => $post->excerpt ?: '',
            'author_name' => $post->author?->name ?? '',
        ];

        return $this->renderDynamic('post', [
            'post' => $post,
            'relatedPosts' => $related,
            'dynamicConfig' => $config,
            'seoMeta' => $this->seo->resolveDynamic('post', $seoVars, $seoPath, $post),
            'seoPath' => $seoPath,
            'context' => 'post',
            'breadcrumbs' => $this->breadcrumbs([
                ['label' => 'Home', 'url' => url('/')],
                ['label' => 'Blog', 'url' => url('/blog')],
                ['label' => $post->title, 'url' => null],
            ]),
        ]);
    }

    public function renderCategory(string $slug): View
    {
        $config = $this->requireEnabled('category');
        $category = Category::query()->where('slug', $slug)->firstOrFail();
        $posts = $category->posts()->published()->with('author')->latest('published_at')
            ->paginate($this->dynamicPages->postsPerPage('category'))
            ->withQueryString();

        $seoVars = [
            'category_name' => $category->name,
            'page_excerpt' => $category->description ?: '',
        ];

        return $this->renderDynamic('category', [
            'category' => $category,
            'posts' => $posts,
            'dynamicConfig' => $config,
            'layoutStyle' => $config->layout ?: 'list',
            'seoMeta' => $this->seo->resolveDynamic('category', $seoVars, 'category/'.$category->slug),
            'seoPath' => 'category/'.$category->slug,
            'context' => 'category',
            'breadcrumbs' => $this->breadcrumbs([
                ['label' => 'Home', 'url' => url('/')],
                ['label' => $category->name, 'url' => null],
            ]),
        ]);
    }

    public function renderTag(string $slug): View
    {
        $config = $this->requireEnabled('tag');
        $tag = Tag::query()->where('slug', $slug)->firstOrFail();
        $posts = $tag->posts()->published()->with('author')->latest('published_at')
            ->paginate($this->dynamicPages->postsPerPage('tag'))
            ->withQueryString();

        $seoVars = [
            'tag_name' => $tag->name,
            'page_excerpt' => $tag->description ?: '',
        ];

        return $this->renderDynamic('tag', [
            'tag' => $tag,
            'posts' => $posts,
            'dynamicConfig' => $config,
            'layoutStyle' => $config->layout ?: 'list',
            'seoMeta' => $this->seo->resolveDynamic('tag', $seoVars, 'tag/'.$tag->slug),
            'seoPath' => 'tag/'.$tag->slug,
            'context' => 'tag',
            'breadcrumbs' => $this->breadcrumbs([
                ['label' => 'Home', 'url' => url('/')],
                ['label' => '#'.$tag->name, 'url' => null],
            ]),
        ]);
    }

    public function renderAuthor(string $username): View
    {
        $config = $this->requireEnabled('author');
        $author = User::query()->where('username', $username)->firstOrFail();
        $posts = Post::query()->published()->where('author_id', $author->id)->latest('published_at')
            ->paginate($this->dynamicPages->postsPerPage('author'))
            ->withQueryString();

        $seoVars = [
            'author_name' => $author->name,
        ];

        return $this->renderDynamic('author', [
            'author' => $author,
            'posts' => $posts,
            'dynamicConfig' => $config,
            'layoutStyle' => $config->layout ?: 'list',
            'seoMeta' => $this->seo->resolveDynamic('author', $seoVars, 'author/'.$author->username),
            'seoPath' => 'author/'.$author->username,
            'context' => 'author',
            'breadcrumbs' => $this->breadcrumbs([
                ['label' => 'Home', 'url' => url('/')],
                ['label' => $author->name, 'url' => null],
            ]),
        ]);
    }

    public function renderSearch(Request $request): View
    {
        $config = $this->requireEnabled('search');
        $q = trim((string) $request->query('q', ''));
        $perPage = $this->dynamicPages->postsPerPage('search');

        $results = [
            'posts' => collect(),
            'pages' => collect(),
            'categories' => collect(),
            'tags' => collect(),
        ];
        $total = 0;

        if ($q !== '') {
            $term = '%'.$q.'%';
            $like = $this->likeOperator();

            $results['posts'] = Post::query()->published()
                ->where(fn ($builder) => $builder->where('title', $like, $term)->orWhere('content', $like, $term)->orWhere('excerpt', $like, $term))
                ->latest('published_at')
                ->limit($perPage)
                ->get();

            $results['pages'] = Page::query()->published()
                ->where(fn ($builder) => $builder->where('title', $like, $term)->orWhere('content', $like, $term)->orWhere('excerpt', $like, $term))
                ->latest('published_at')
                ->limit($perPage)
                ->get();

            $results['categories'] = Category::query()
                ->where(fn ($builder) => $builder->where('name', $like, $term)->orWhere('description', $like, $term))
                ->orderBy('name')
                ->limit(20)
                ->get();

            $results['tags'] = Tag::query()
                ->where(fn ($builder) => $builder->where('name', $like, $term)->orWhere('description', $like, $term))
                ->orderBy('name')
                ->limit(20)
                ->get();

            $total = $results['posts']->count()
                + $results['pages']->count()
                + $results['categories']->count()
                + $results['tags']->count();
        }

        $seoVars = [
            'search_query' => $q !== '' ? $q : '…',
            'page_title' => $config->title ?: 'Search',
        ];

        return $this->renderDynamic('search', [
            'query' => $q,
            'results' => $results,
            'total' => $total,
            'dynamicConfig' => $config,
            'seoMeta' => $this->seo->resolveDynamic('search', $seoVars, 'search'.($q !== '' ? '?q='.urlencode($q) : '')),
            'seoPath' => 'search',
            'context' => 'search',
            'breadcrumbs' => $this->breadcrumbs([
                ['label' => 'Home', 'url' => url('/')],
                ['label' => 'Search', 'url' => null],
            ]),
        ]);
    }

    public function renderArchive(?int $year = null, ?int $month = null, ?int $day = null): View
    {
        $config = $this->requireEnabled('archive');

        if (! ($config->settings['enabled'] ?? true)) {
            throw new NotFoundHttpException('Date archives are disabled.');
        }

        $query = Post::query()->published()->with('author')->latest('published_at');

        if ($year) {
            $query->whereYear('published_at', $year);
        }
        if ($month) {
            $query->whereMonth('published_at', $month);
        }
        if ($day) {
            $query->whereDay('published_at', $day);
        }

        $posts = $query->paginate($this->dynamicPages->postsPerPage('archive'))->withQueryString();

        $label = $this->archiveLabel($year, $month, $day);
        $path = 'archive'.($year ? '/'.$year : '').($month ? '/'.sprintf('%02d', $month) : '').($day ? '/'.sprintf('%02d', $day) : '');

        $seoVars = [
            'archive_label' => $label,
            'page_title' => $config->title ?: 'Archives',
        ];

        return $this->renderDynamic('archive', [
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'archiveLabel' => $label,
            'posts' => $posts,
            'dynamicConfig' => $config,
            'layoutStyle' => $config->layout ?: 'list',
            'seoMeta' => $this->seo->resolveDynamic('archive', $seoVars, $path),
            'seoPath' => $path,
            'context' => 'archive',
            'breadcrumbs' => $this->breadcrumbs([
                ['label' => 'Home', 'url' => url('/')],
                ['label' => $label, 'url' => null],
            ]),
        ]);
    }

    public function renderNotFound(): View
    {
        $config = $this->dynamicPages->get('404');

        $seoVars = [
            'page_title' => $config->title ?: 'Page Not Found',
        ];

        return $this->renderDynamic('404', [
            'dynamicConfig' => $config,
            'seoMeta' => $this->seo->resolveDynamic('404', $seoVars, request()->path()),
            'seoPath' => request()->path(),
            'context' => '404',
            'breadcrumbs' => $this->breadcrumbs([
                ['label' => 'Home', 'url' => url('/')],
                ['label' => '404', 'url' => null],
            ]),
        ]);
    }

    public function resolveSlug(string $slug): View
    {
        if ($this->permalinks->structure() === PermalinkService::STRUCTURE_ROOT) {
            $post = Post::query()->published()->where('slug', $slug)->first();
            if ($post) {
                return $this->renderPost($post->slug);
            }
        }

        if ($this->dynamicPages->isReservedSlug($slug)) {
            throw new NotFoundHttpException;
        }

        $page = Page::query()->published()->where('slug', $slug)->firstOrFail();

        return $this->renderStatic($page);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function renderDynamic(string $type, array $data): View
    {
        /** @var DynamicPageSetting $config */
        $config = $data['dynamicConfig'] ?? $this->dynamicPages->get($type);
        $view = $this->dynamicPages->themeView($type, $config->template);

        return view($view, array_merge([
            'pageKind' => 'dynamic',
            'dynamicType' => $type,
            'siteName' => CmsSetting::getValue('site_name', config('cms.name')),
        ], $data));
    }

    protected function requireEnabled(string $type): DynamicPageSetting
    {
        $config = $this->dynamicPages->get($type);

        if (! $config->is_enabled && $type !== '404') {
            throw new NotFoundHttpException("Dynamic page [{$type}] is disabled.");
        }

        return $config;
    }

    /**
     * @param  list<array{label: string, url: ?string}>  $items
     * @return list<array{label: string, url: ?string}>
     */
    protected function breadcrumbs(array $items): array
    {
        return $items;
    }

    protected function archiveLabel(?int $year, ?int $month, ?int $day): string
    {
        if ($year && $month && $day) {
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
        if ($year && $month) {
            return sprintf('%04d-%02d', $year, $month);
        }
        if ($year) {
            return (string) $year;
        }

        return 'Archives';
    }

    protected function likeOperator(): string
    {
        return \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }
}
