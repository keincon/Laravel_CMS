<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CmsSetting;
use App\Models\Content;
use App\Models\DynamicPageSetting;
use App\Models\LayoutSetting;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;
use App\Models\Term;
use App\Models\User;
use App\Services\Search\SearchService;
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

        // Prefer LaravelPress page content when dual-written.
        $homepageSlug = $layout->homepagePage?->slug ?? 'home';
        $contentHome = Content::query()
            ->ofType('page')
            ->published()
            ->with(['author', 'featuredMedia'])
            ->where('slug', $homepageSlug)
            ->first();

        if ($contentHome) {
            $view = $this->dynamicPages->staticPageView($contentHome->template ?: 'default');

            return view($view, [
                'page' => $contentHome,
                'content' => $contentHome,
                'pageKind' => 'static',
                'dynamicConfig' => null,
                'seoMeta' => $this->seo->resolveDynamic('page', [
                    'page_title' => $contentHome->title,
                    'page_excerpt' => $contentHome->excerpt ?: '',
                ], '/'),
                'seoPath' => '/',
                'context' => 'home',
            ]);
        }

        $page = null;
        if ($this->legacyPublicFallback()) {
            $page = $layout->homepagePage
                ?? Page::query()->published()->where('slug', 'home')->first();
        }

        if (! $page) {
            return $this->renderBlog($request, isHomepage: true);
        }

        return $this->renderStatic($page, context: 'home');
    }

    protected function legacyPublicFallback(): bool
    {
        return app(\App\Services\Content\LegacyRetirementService::class)->publicFallbackEnabled();
    }

    public function renderStatic(Page $page, string $context = 'page'): View
    {
        $page->loadMissing(['featuredImage', 'author']);
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

        // Prefer LaravelPress contents; legacy Post archive only when fallback enabled.
        if (Content::query()->ofType('post')->published()->exists() || ! $this->legacyPublicFallback()) {
            $posts = Content::query()
                ->ofType('post')
                ->published()
                ->with(['author', 'featuredMedia', 'terms'])
                ->latest('published_at')
                ->paginate($perPage)
                ->withQueryString();
        } else {
            $posts = Post::query()
                ->published()
                ->with(['author', 'categories', 'tags', 'featuredImage'])
                ->stickyFirst()
                ->latest('published_at')
                ->paginate($perPage)
                ->withQueryString();
        }

        $seoVars = [
            'page_title' => $config->title ?: 'Blog',
            'page_excerpt' => $config->description ?: '',
        ];

        $indexPath = ltrim($config->url_path ?: '/blog', '/');

        return $this->renderDynamic('blog', [
            'posts' => $posts,
            'page' => null,
            'dynamicConfig' => $config,
            'isHomepage' => $isHomepage,
            'layoutStyle' => $config->layout ?: 'list',
            'archiveMonths' => $this->newsArchiveMonths(),
            'seoMeta' => $this->seo->resolveDynamic('blog', $seoVars, $isHomepage ? '/' : $indexPath),
            'seoPath' => $isHomepage ? '/' : $indexPath,
            'context' => 'blog',
            'breadcrumbs' => $this->breadcrumbs([
                ['label' => 'Home', 'url' => url('/')],
                ['label' => $config->title ?: 'Blog', 'url' => null],
            ]),
        ]);
    }

    public function renderCampaign(Request $request): View
    {
        $config = $this->requireEnabled('campaign');
        $perPage = $this->dynamicPages->postsPerPage('campaign');

        $campaigns = Content::query()
            ->ofType('campaign')
            ->published()
            ->with(['author', 'featuredMedia', 'meta'])
            ->orderBy('menu_order')
            ->latest('published_at')
            ->paginate($perPage)
            ->withQueryString();

        $indexPath = ltrim($config->url_path ?: '/campaign', '/');
        $seoVars = [
            'page_title' => $config->title ?: 'キャンペーン',
            'page_excerpt' => $config->description ?: '',
        ];

        return $this->renderDynamic('campaign', [
            'campaigns' => $campaigns,
            'posts' => $campaigns,
            'page' => null,
            'dynamicConfig' => $config,
            'layoutStyle' => $config->layout ?: 'list',
            'seoMeta' => $this->seo->resolveDynamic('campaign', $seoVars, $indexPath),
            'seoPath' => $indexPath,
            'context' => 'campaign',
            'breadcrumbs' => $this->breadcrumbs([
                ['label' => 'Home', 'url' => url('/')],
                ['label' => $config->title ?: 'キャンペーン', 'url' => null],
            ]),
        ]);
    }

    public function renderCampaignItem(string $slug): View
    {
        $config = $this->requireEnabled('campaign_item');

        $campaign = Content::query()
            ->ofType('campaign')
            ->published()
            ->with(['author', 'featuredMedia', 'meta'])
            ->where('slug', $slug)
            ->firstOrFail();

        $seoPath = $this->permalinks->contentPath($campaign);
        $seoVars = [
            'post_title' => $campaign->title,
            'post_excerpt' => $campaign->excerpt ?: '',
            'page_title' => $campaign->title,
        ];

        $related = Content::query()
            ->ofType('campaign')
            ->published()
            ->where('id', '!=', $campaign->id)
            ->orderBy('menu_order')
            ->latest('published_at')
            ->limit(4)
            ->get();

        return $this->renderDynamic('campaign_item', [
            'campaign' => $campaign,
            'post' => $campaign,
            'relatedCampaigns' => $related,
            'dynamicConfig' => $config,
            'seoMeta' => $this->seo->resolveDynamic('campaign_item', $seoVars, $seoPath, $campaign),
            'seoPath' => $seoPath,
            'context' => 'campaign',
            'breadcrumbs' => $this->breadcrumbs([
                ['label' => 'Home', 'url' => url('/')],
                ['label' => $this->campaignIndexLabel(), 'url' => url('/campaign')],
                ['label' => $campaign->title, 'url' => null],
            ]),
        ]);
    }

    public function renderPost(string $slug): View
    {
        $config = $this->requireEnabled('post');

        $content = Content::query()
            ->ofType('post')
            ->published()
            ->with(['author', 'featuredMedia', 'terms'])
            ->where('slug', $slug)
            ->first();

        if ($content) {
            $seoPath = $this->permalinks->contentPath($content);
            $seoVars = [
                'post_title' => $content->title,
                'post_excerpt' => $content->excerpt ?: '',
                'author_name' => $content->author?->publicName() ?? '',
            ];

            // Prefer Content-native comments; optional legacy Post comments bridge.
            $comments = $content->approvedComments()->get();
            if ($comments->isEmpty() && $this->legacyPublicFallback()) {
                $legacyPost = Post::query()->where('slug', $content->slug)->first();
                $comments = $legacyPost
                    ? $legacyPost->approvedComments()->get()
                    : collect();
            }

            return $this->renderDynamic('post', [
                'post' => $content, // Content exposes content/featuredImage accessors for themes
                'content' => $content,
                'comments' => $comments,
                'relatedPosts' => Content::query()
                    ->ofType('post')
                    ->published()
                    ->where('id', '!=', $content->id)
                    ->latest('published_at')
                    ->limit(3)
                    ->get(),
                'dynamicConfig' => $config,
                'seoMeta' => $this->seo->resolveDynamic('post', $seoVars, $seoPath),
                'seoPath' => $seoPath,
                'context' => 'post',
                'breadcrumbs' => $this->breadcrumbs([
                    ['label' => 'Home', 'url' => url('/')],
                    ['label' => $this->blogIndexLabel(), 'url' => $this->permalinks->blogIndexUrl()],
                    ['label' => $content->title, 'url' => null],
                ]),
            ]);
        }

        if (! $this->legacyPublicFallback()) {
            throw new NotFoundHttpException;
        }

        $post = Post::query()->published()->with(['author', 'categories', 'tags', 'featuredImage', 'approvedComments.user', 'approvedComments.children.user'])->where('slug', $slug)->firstOrFail();
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
            'comments' => $post->approvedComments,
            'relatedPosts' => $related,
            'dynamicConfig' => $config,
            'seoMeta' => $this->seo->resolveDynamic('post', $seoVars, $seoPath, $post),
            'seoPath' => $seoPath,
            'context' => 'post',
            'breadcrumbs' => $this->breadcrumbs([
                ['label' => 'Home', 'url' => url('/')],
                ['label' => $this->blogIndexLabel(), 'url' => $this->permalinks->blogIndexUrl()],
                ['label' => $post->title, 'url' => null],
            ]),
        ]);
    }

    public function renderCategory(string $slug): View
    {
        $config = $this->requireEnabled('category');

        $term = Term::query()
            ->whereHas('taxonomy', fn ($q) => $q->where('slug', 'category'))
            ->where('slug', $slug)
            ->first();

        if ($term && $term->contents()->ofType('post')->published()->exists()) {
            $posts = $term->contents()
                ->ofType('post')
                ->published()
                ->with('author')
                ->latest('published_at')
                ->paginate($this->dynamicPages->postsPerPage('category'))
                ->withQueryString();

            $seoVars = [
                'category_name' => $term->name,
                'page_excerpt' => $term->description ?: '',
            ];

            return $this->renderDynamic('category', [
                'category' => $term,
                'posts' => $posts,
                'dynamicConfig' => $config,
                'layoutStyle' => $config->layout ?: 'list',
                'seoMeta' => $this->seo->resolveDynamic('category', $seoVars, 'category/'.$term->slug),
                'seoPath' => 'category/'.$term->slug,
                'context' => 'category',
                'breadcrumbs' => $this->breadcrumbs([
                    ['label' => 'Home', 'url' => url('/')],
                    ['label' => $term->name, 'url' => null],
                ]),
            ]);
        }

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

        $term = Term::query()
            ->whereHas('taxonomy', fn ($q) => $q->whereIn('slug', ['post_tag', 'tag']))
            ->where('slug', $slug)
            ->first();

        if ($term && $term->contents()->ofType('post')->published()->exists()) {
            $posts = $term->contents()
                ->ofType('post')
                ->published()
                ->with('author')
                ->latest('published_at')
                ->paginate($this->dynamicPages->postsPerPage('tag'))
                ->withQueryString();

            $seoVars = [
                'tag_name' => $term->name,
                'page_excerpt' => $term->description ?: '',
            ];

            return $this->renderDynamic('tag', [
                'tag' => $term,
                'posts' => $posts,
                'dynamicConfig' => $config,
                'layoutStyle' => $config->layout ?: 'list',
                'seoMeta' => $this->seo->resolveDynamic('tag', $seoVars, 'tag/'.$term->slug),
                'seoPath' => 'tag/'.$term->slug,
                'context' => 'tag',
                'breadcrumbs' => $this->breadcrumbs([
                    ['label' => 'Home', 'url' => url('/')],
                    ['label' => '#'.$term->name, 'url' => null],
                ]),
            ]);
        }

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

        if (Content::query()->ofType('post')->published()->where('author_id', $author->id)->exists()) {
            $posts = Content::query()
                ->ofType('post')
                ->published()
                ->where('author_id', $author->id)
                ->latest('published_at')
                ->paginate($this->dynamicPages->postsPerPage('author'))
                ->withQueryString();
        } else {
            $posts = Post::query()->published()->where('author_id', $author->id)->latest('published_at')
                ->paginate($this->dynamicPages->postsPerPage('author'))
                ->withQueryString();
        }

        $seoVars = [
            'author_name' => $author->publicName(),
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
                ['label' => $author->publicName(), 'url' => null],
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
            'cms' => collect(),
        ];
        $total = 0;

        if ($q !== '') {
            $term = '%'.$q.'%';
            $like = $this->likeOperator();

            if (Content::query()->ofType('post')->published()->exists()) {
                $results['posts'] = Content::query()->ofType('post')->published()
                    ->where(fn ($builder) => $builder->where('title', $like, $term)->orWhere('body', $like, $term)->orWhere('excerpt', $like, $term))
                    ->latest('published_at')
                    ->limit($perPage)
                    ->get();
            } else {
                $results['posts'] = Post::query()->published()
                    ->where(fn ($builder) => $builder->where('title', $like, $term)->orWhere('content', $like, $term)->orWhere('excerpt', $like, $term))
                    ->latest('published_at')
                    ->limit($perPage)
                    ->get();
            }

            if (Content::query()->ofType('page')->published()->exists()) {
                $results['pages'] = Content::query()->ofType('page')->published()
                    ->where(fn ($builder) => $builder->where('title', $like, $term)->orWhere('body', $like, $term)->orWhere('excerpt', $like, $term))
                    ->latest('published_at')
                    ->limit($perPage)
                    ->get();
            } else {
                $results['pages'] = Page::query()->published()
                    ->where(fn ($builder) => $builder->where('title', $like, $term)->orWhere('content', $like, $term)->orWhere('excerpt', $like, $term))
                    ->latest('published_at')
                    ->limit($perPage)
                    ->get();
            }

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

            // Also surface generic search hits (media/users/terms) via SearchService.
            $results['cms'] = app(SearchService::class)->search($q, ['media', 'user', 'term'], 20);

            $total = $results['posts']->count()
                + $results['pages']->count()
                + $results['categories']->count()
                + $results['tags']->count()
                + $results['cms']->count();
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

        $perPage = $this->dynamicPages->postsPerPage('archive');

        if (Content::query()->ofType('post')->published()->exists() || ! $this->legacyPublicFallback()) {
            $query = Content::query()
                ->ofType('post')
                ->published()
                ->with(['author', 'featuredMedia', 'terms'])
                ->latest('published_at');
        } else {
            $query = Post::query()->published()->with('author')->latest('published_at');
        }

        if ($year) {
            $query->whereYear('published_at', $year);
        }
        if ($month) {
            $query->whereMonth('published_at', $month);
        }
        if ($day) {
            $query->whereDay('published_at', $day);
        }

        $posts = $query->paginate($perPage)->withQueryString();

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
                ['label' => $this->blogIndexLabel(), 'url' => $this->permalinks->blogIndexUrl()],
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
            $contentExists = Content::query()->ofType('post')->published()->where('slug', $slug)->exists();
            $legacyExists = $this->legacyPublicFallback()
                && Post::query()->published()->where('slug', $slug)->exists();
            if ($contentExists || $legacyExists) {
                return $this->renderPost($slug);
            }
        }

        if ($this->dynamicPages->isReservedSlug($slug)) {
            throw new NotFoundHttpException;
        }

        $contentPage = Content::query()
            ->ofType('page')
            ->published()
            ->with(['author', 'featuredMedia'])
            ->where('slug', $slug)
            ->first();

        if ($contentPage) {
            $view = $this->dynamicPages->staticPageView($contentPage->template ?: 'default');

            return view($view, [
                'page' => $contentPage,
                'content' => $contentPage,
                'pageKind' => 'static',
                'dynamicConfig' => null,
                'seoMeta' => $this->seo->resolveDynamic('page', [
                    'page_title' => $contentPage->title,
                    'page_excerpt' => $contentPage->excerpt ?: '',
                ], $contentPage->slug === 'home' ? '/' : $contentPage->slug),
                'seoPath' => $contentPage->slug === 'home' ? '/' : $contentPage->slug,
                'context' => 'page',
            ]);
        }

        if (! $this->legacyPublicFallback()) {
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
            return sprintf('%04d年%02d月%02d日', $year, $month, $day);
        }
        if ($year && $month) {
            return sprintf('%04d年%d月', $year, $month);
        }
        if ($year) {
            return sprintf('%04d年', $year);
        }

        return 'Archives';
    }

    protected function blogIndexLabel(): string
    {
        try {
            return $this->dynamicPages->get('blog')->title ?: 'お知らせ';
        } catch (\Throwable) {
            return 'お知らせ';
        }
    }

    protected function campaignIndexLabel(): string
    {
        try {
            return $this->dynamicPages->get('campaign')->title ?: 'キャンペーン';
        } catch (\Throwable) {
            return 'キャンペーン';
        }
    }

    /**
     * Distinct year-month buckets for the バックナンバー sidebar (newest first).
     *
     * @return list<array{year: int, month: int, label: string, url: string}>
     */
    protected function newsArchiveMonths(int $limit = 12): array
    {
        $dates = Content::query()
            ->ofType('post')
            ->published()
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->limit(500)
            ->pluck('published_at');

        if ($dates->isEmpty() && $this->legacyPublicFallback()) {
            $dates = Post::query()
                ->published()
                ->whereNotNull('published_at')
                ->orderByDesc('published_at')
                ->limit(500)
                ->pluck('published_at');
        }

        $seen = [];
        $months = [];

        foreach ($dates as $publishedAt) {
            $year = (int) $publishedAt->format('Y');
            $month = (int) $publishedAt->format('n');
            $key = sprintf('%04d-%02d', $year, $month);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $months[] = [
                'year' => $year,
                'month' => $month,
                'label' => sprintf('%04d年%d月', $year, $month),
                'url' => url(sprintf('/archive/%04d/%02d', $year, $month)),
            ];
            if (count($months) >= $limit) {
                break;
            }
        }

        return $months;
    }

    protected function likeOperator(): string
    {
        return \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }
}
