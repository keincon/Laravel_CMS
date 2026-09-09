<?php

use App\Http\Controllers\Admin\ContentAdminController;
use App\Http\Controllers\Admin\RedirectAdminController;
use App\Http\Controllers\Admin\TaxonomyAdminController;
use App\Http\Controllers\Admin\ApiDocsController;
use App\Http\Controllers\Admin\ApiTokenController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ModuleAdminController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CommentController as AdminCommentController;
use App\Http\Controllers\Admin\DiscussionSettingsController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\Admin\CorsSettingsController;
use App\Http\Controllers\Admin\DemoDataController;
use App\Http\Controllers\Admin\GeneralSettingsController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\DynamicPageController;
use App\Http\Controllers\Admin\FooterController;
use App\Http\Controllers\Admin\HeaderController;
use App\Http\Controllers\Admin\LayoutSettingsController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\SetupGuideController;
use App\Http\Controllers\Admin\SeoSettingsController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\ThemeAdminController;
use App\Http\Controllers\Admin\ThemeSettingsController;
use App\Http\Controllers\Admin\WidgetController;
use App\Http\Controllers\Admin\CustomCodeController;
use App\Http\Controllers\Admin\PluginAdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Setup\SetupController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\ThemeAssetController;
use Illuminate\Support\Facades\Route;

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::get('/sitemap.xml', [SiteController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SiteController::class, 'robots'])->name('robots');
Route::get('/themes/{theme}/assets/{path}', [ThemeAssetController::class, 'show'])
    ->where('path', '.*')
    ->name('theme.asset');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/login/two-factor', [AuthController::class, 'showTwoFactor'])->name('login.two-factor');
Route::post('/login/two-factor', [AuthController::class, 'verifyTwoFactor'])->name('login.two-factor.verify');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::post('/posts/{post}/comments', [CommentController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('comments.store');
Route::post('/contents/{content}/comments', [CommentController::class, 'storeForContent'])
    ->middleware('throttle:10,1')
    ->name('comments.store.content');

Route::prefix('setup')->name('setup.')->group(function () {
    Route::get('/', [SetupController::class, 'welcome'])->name('welcome');
    Route::get('/requirements', [SetupController::class, 'requirements'])->name('requirements');
    Route::get('/database', [SetupController::class, 'database'])->name('database');
    Route::post('/database/test', [SetupController::class, 'testDatabase'])->name('database.test');
    Route::post('/database', [SetupController::class, 'storeDatabase'])->name('database.store');
    Route::get('/website', [SetupController::class, 'website'])->name('website');
    Route::post('/website', [SetupController::class, 'storeWebsite'])->name('website.store');
    Route::get('/administrator', [SetupController::class, 'administrator'])->name('administrator');
    Route::post('/administrator', [SetupController::class, 'storeAdministrator'])->name('administrator.store');
    Route::get('/appearance', [SetupController::class, 'appearance'])->name('appearance');
    Route::post('/appearance', [SetupController::class, 'storeAppearance'])->name('appearance.store');
    Route::get('/install', [SetupController::class, 'showInstall'])->name('install.show');
    Route::post('/install', [SetupController::class, 'install'])->name('install');
    Route::get('/complete', [SetupController::class, 'complete'])->name('complete');
    Route::get('/failed', [SetupController::class, 'failed'])->name('failed');
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    // Any authenticated user
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/help/setup-guide', [SetupGuideController::class, 'show'])->name('help.setup-guide');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/two-factor/enable', [ProfileController::class, 'enableTwoFactor'])->name('profile.two-factor.enable');
    Route::post('/profile/two-factor/confirm', [ProfileController::class, 'confirmTwoFactor'])->name('profile.two-factor.confirm');
    Route::delete('/profile/two-factor', [ProfileController::class, 'disableTwoFactor'])->name('profile.two-factor.disable');
    Route::get('/users/tokens', [ApiTokenController::class, 'index'])->name('users.tokens');
    Route::post('/users/tokens', [ApiTokenController::class, 'store'])->name('users.tokens.store');
    Route::delete('/users/tokens/{tokenId}', [ApiTokenController::class, 'destroy'])->name('users.tokens.destroy');

    Route::middleware('permission:manage_posts')->group(function () {
        Route::post('/dashboard/quick-draft', [DashboardController::class, 'quickDraft'])->name('dashboard.quick-draft');
        Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
        Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
        Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
        Route::post('/posts/bulk', [PostController::class, 'bulk'])->name('posts.bulk');
        Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
        Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
        Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
        Route::post('/posts/{post}/duplicate', [PostController::class, 'duplicate'])->name('posts.duplicate');
        Route::get('/posts/{post}/preview', [PostController::class, 'preview'])->name('posts.preview');

        // LaravelPress generic content admin (posts + custom types)
        Route::middleware('role_or_permission:manage_posts|manage_pages')->group(function () {
            Route::get('/contents', [ContentAdminController::class, 'index'])->name('contents.index');
            Route::get('/contents/create', [ContentAdminController::class, 'create'])->name('contents.create');
            Route::post('/contents', [ContentAdminController::class, 'store'])->name('contents.store');
            Route::get('/contents/{content}/edit', [ContentAdminController::class, 'edit'])->name('contents.edit');
            Route::put('/contents/{content}', [ContentAdminController::class, 'update'])->name('contents.update');
            Route::post('/contents/{content}/add-to-menu', [ContentAdminController::class, 'addToMenu'])->name('contents.add-to-menu');
            Route::post('/contents/{content}/autosave', [ContentAdminController::class, 'autosave'])->name('contents.autosave');
            Route::post('/contents/{content}/revisions/{revision}/restore', [ContentAdminController::class, 'restoreRevision'])->name('contents.revisions.restore');
            Route::get('/contents/{content}/revisions/compare', [ContentAdminController::class, 'compareRevisions'])->name('contents.revisions.compare');
            Route::delete('/contents/{content}', [ContentAdminController::class, 'destroy'])->name('contents.destroy');

            Route::get('/content-types', [\App\Http\Controllers\Admin\ContentTypeAdminController::class, 'index'])->name('content-types.index');
            Route::post('/content-types', [\App\Http\Controllers\Admin\ContentTypeAdminController::class, 'store'])->name('content-types.store');
            Route::put('/content-types/{contentType}', [\App\Http\Controllers\Admin\ContentTypeAdminController::class, 'update'])->name('content-types.update');
            Route::delete('/content-types/{contentType}', [\App\Http\Controllers\Admin\ContentTypeAdminController::class, 'destroy'])->name('content-types.destroy');
        });
    });

    Route::middleware('permission:manage_pages')->group(function () {
        Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
        Route::get('/pages/create', [PageController::class, 'create'])->name('pages.create');
        Route::post('/pages', [PageController::class, 'store'])->name('pages.store');
        Route::post('/pages/bulk', [PageController::class, 'bulk'])->name('pages.bulk');
        Route::post('/pages/{page}/restore', [PageController::class, 'restore'])->name('pages.restore');
        Route::get('/pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
        Route::put('/pages/{page}', [PageController::class, 'update'])->name('pages.update');
        Route::delete('/pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy');
        Route::post('/pages/{page}/duplicate', [PageController::class, 'duplicate'])->name('pages.duplicate');
        Route::get('/pages/{page}/preview', [PageController::class, 'preview'])->name('pages.preview');
    });

    Route::middleware('permission:manage_categories')->group(function () {
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
        Route::get('/tags/create', [TagController::class, 'create'])->name('tags.create');
        Route::post('/tags', [TagController::class, 'store'])->name('tags.store');
        Route::get('/tags/{tag}/edit', [TagController::class, 'edit'])->name('tags.edit');
        Route::put('/tags/{tag}', [TagController::class, 'update'])->name('tags.update');
        Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');

        Route::get('/taxonomies', [TaxonomyAdminController::class, 'index'])->name('taxonomies.index');
        Route::get('/taxonomies/{taxonomy}', [TaxonomyAdminController::class, 'show'])->name('taxonomies.show');
        Route::post('/taxonomies/{taxonomy}/terms', [TaxonomyAdminController::class, 'storeTerm'])->name('taxonomies.terms.store');
        Route::put('/taxonomies/{taxonomy}/terms/{term}', [TaxonomyAdminController::class, 'updateTerm'])->name('taxonomies.terms.update');
        Route::delete('/taxonomies/{taxonomy}/terms/{term}', [TaxonomyAdminController::class, 'destroyTerm'])->name('taxonomies.terms.destroy');
    });

    Route::middleware('permission:manage_media')->group(function () {
        Route::get('/media', [MediaController::class, 'index'])->name('media.index');
        Route::get('/media/json', [MediaController::class, 'json'])->name('media.json');
        Route::post('/media', [MediaController::class, 'store'])->name('media.store');
        Route::post('/media/json', [MediaController::class, 'storeJson'])->name('media.store.json');
        Route::put('/media/{medium}', [MediaController::class, 'update'])->name('media.update');
        Route::delete('/media/{medium}', [MediaController::class, 'destroy'])->name('media.destroy');
    });

    Route::middleware('permission:manage_menus')->group(function () {
        Route::get('/menus', [MenuController::class, 'index'])->name('menus.index');
        Route::post('/menus', [MenuController::class, 'store'])->name('menus.store');
        Route::get('/menus/{menu}/edit', [MenuController::class, 'edit'])->name('menus.edit');
        Route::put('/menus/{menu}', [MenuController::class, 'update'])->name('menus.update');
        Route::delete('/menus/{menu}', [MenuController::class, 'destroy'])->name('menus.destroy');
        Route::post('/menus/{menu}/items', [MenuController::class, 'storeItem'])->name('menus.items.store');
        Route::put('/menus/{menu}/items/{item}', [MenuController::class, 'updateItem'])->name('menus.items.update');
        Route::delete('/menus/{menu}/items/{item}', [MenuController::class, 'destroyItem'])->name('menus.items.destroy');
    });

    Route::middleware('permission:manage_comments')->group(function () {
        Route::get('/comments', [AdminCommentController::class, 'index'])->name('comments.index');
        Route::post('/comments/bulk', [AdminCommentController::class, 'bulk'])->name('comments.bulk');
        Route::put('/comments/{comment}', [AdminCommentController::class, 'update'])->name('comments.update');
        Route::delete('/comments/{comment}', [AdminCommentController::class, 'destroy'])->name('comments.destroy');
    });

    Route::middleware('permission:manage_settings')->group(function () {
        Route::get('/settings/discussion', [DiscussionSettingsController::class, 'edit'])->name('settings.discussion');
        Route::put('/settings/discussion', [DiscussionSettingsController::class, 'update'])->name('settings.discussion.update');
        Route::get('/settings/general', [GeneralSettingsController::class, 'edit'])->name('settings.general');
        Route::put('/settings/general', [GeneralSettingsController::class, 'update'])->name('settings.general.update');
        Route::get('/settings/seo', [SeoSettingsController::class, 'edit'])->name('settings.seo');
        Route::put('/settings/seo', [SeoSettingsController::class, 'update'])->name('settings.seo.update');
        Route::get('/settings/seo/templates', [SeoSettingsController::class, 'editTemplates'])->name('settings.seo.templates');
        Route::put('/settings/seo/templates', [SeoSettingsController::class, 'updateTemplates'])->name('settings.seo.templates.update');
        Route::get('/settings/ogp', [SeoSettingsController::class, 'editOgp'])->name('settings.ogp');
        Route::put('/settings/ogp', [SeoSettingsController::class, 'updateOgp'])->name('settings.ogp.update');
        Route::get('/settings/permalinks', [SeoSettingsController::class, 'editPermalinks'])->name('settings.permalinks');
        Route::put('/settings/permalinks', [SeoSettingsController::class, 'updatePermalinks'])->name('settings.permalinks.update');
        Route::get('/settings/reading', [LayoutSettingsController::class, 'reading'])->name('settings.reading');
        Route::put('/settings/reading', [LayoutSettingsController::class, 'updateReading'])->name('settings.reading.update');
        Route::get('/settings/api', [ApiDocsController::class, 'show'])->name('settings.api');
        Route::get('/settings/cors', [CorsSettingsController::class, 'edit'])->name('settings.cors');
        Route::put('/settings/cors', [CorsSettingsController::class, 'update'])->name('settings.cors.update');
        Route::post('/settings/demo-data', [DemoDataController::class, 'install'])->name('settings.demo-data');
        Route::post('/settings/aoyama-data', [DemoDataController::class, 'installAoyama'])->name('settings.aoyama-data');

        Route::get('/redirects', [RedirectAdminController::class, 'index'])->name('redirects.index');
        Route::post('/redirects', [RedirectAdminController::class, 'store'])->name('redirects.store');
        Route::put('/redirects/{redirect}', [RedirectAdminController::class, 'update'])->name('redirects.update');
        Route::delete('/redirects/{redirect}', [RedirectAdminController::class, 'destroy'])->name('redirects.destroy');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        Route::get('/modules', [ModuleAdminController::class, 'index'])->name('modules.index');
        Route::put('/modules/{module}', [ModuleAdminController::class, 'update'])->name('modules.update');
    });

    Route::middleware('permission:manage_plugins')->group(function () {
        Route::get('/plugins', [PluginAdminController::class, 'index'])->name('plugins.index');
        Route::post('/plugins/scaffold', [PluginAdminController::class, 'scaffold'])->name('plugins.scaffold');
        Route::post('/plugins/import', [PluginAdminController::class, 'import'])->name('plugins.import');
        Route::post('/plugins/rebuild-packs', [PluginAdminController::class, 'rebuildPacks'])->name('plugins.rebuild');
        Route::post('/plugins/wordpress/activate', [PluginAdminController::class, 'wpActivate'])->name('plugins.wp.activate');
        Route::post('/plugins/wordpress/deactivate', [PluginAdminController::class, 'wpDeactivate'])->name('plugins.wp.deactivate');
        Route::post('/plugins/wordpress/upload', [PluginAdminController::class, 'wpUpload'])->name('plugins.wp.upload');
        Route::post('/plugins/{plugin}/activate', [PluginAdminController::class, 'activate'])->name('plugins.activate');
        Route::post('/plugins/{plugin}/deactivate', [PluginAdminController::class, 'deactivate'])->name('plugins.deactivate');
        Route::get('/plugins/{plugin}/export', [PluginAdminController::class, 'export'])->name('plugins.export');
    });

    Route::middleware('permission:manage_themes')->group(function () {
        Route::get('/headers', [HeaderController::class, 'index'])->name('headers.index');
        Route::post('/headers', [HeaderController::class, 'store'])->name('headers.store');
        Route::get('/headers/{header}/edit', [HeaderController::class, 'edit'])->name('headers.edit');
        Route::put('/headers/{header}', [HeaderController::class, 'update'])->name('headers.update');
        Route::delete('/headers/{header}', [HeaderController::class, 'destroy'])->name('headers.destroy');

        Route::get('/footers', [FooterController::class, 'index'])->name('footers.index');
        Route::post('/footers', [FooterController::class, 'store'])->name('footers.store');
        Route::get('/footers/{footer}/edit', [FooterController::class, 'edit'])->name('footers.edit');
        Route::put('/footers/{footer}', [FooterController::class, 'update'])->name('footers.update');
        Route::delete('/footers/{footer}', [FooterController::class, 'destroy'])->name('footers.destroy');

        Route::get('/appearance/themes', [ThemeAdminController::class, 'index'])->name('appearance.themes');
        Route::post('/appearance/themes/scaffold', [ThemeAdminController::class, 'scaffold'])->name('appearance.themes.scaffold');
        Route::post('/appearance/themes/{theme}/activate', [ThemeAdminController::class, 'activate'])->name('appearance.themes.activate');
        Route::get('/appearance/themes/{theme}/export', [ThemeAdminController::class, 'export'])->name('appearance.themes.export');
        Route::get('/appearance/themes/{theme}/pack', [ThemeAdminController::class, 'downloadPack'])->name('appearance.themes.pack');
        Route::post('/appearance/themes/import', [ThemeAdminController::class, 'import'])->name('appearance.themes.import');
        Route::post('/appearance/themes/rebuild-packs', [ThemeAdminController::class, 'rebuildPacks'])->name('appearance.themes.rebuild');

        Route::get('/appearance/layout', [LayoutSettingsController::class, 'edit'])->name('appearance.layout');
        Route::put('/appearance/layout', [LayoutSettingsController::class, 'update'])->name('appearance.layout.update');
        Route::get('/appearance/colors', [ThemeSettingsController::class, 'colors'])->name('appearance.colors');
        Route::put('/appearance/colors', [ThemeSettingsController::class, 'updateColors'])->name('appearance.colors.update');
        Route::post('/appearance/colors/reset', [ThemeSettingsController::class, 'reset'])->name('appearance.colors.reset');
        Route::get('/appearance/mode', [ThemeSettingsController::class, 'mode'])->name('appearance.mode');
        Route::put('/appearance/mode', [ThemeSettingsController::class, 'updateMode'])->name('appearance.mode.update');
        Route::get('/appearance/custom-code', [CustomCodeController::class, 'edit'])->name('appearance.custom-code');
        Route::put('/appearance/custom-code', [CustomCodeController::class, 'update'])->name('appearance.custom-code.update');
        Route::get('/appearance/widgets', [WidgetController::class, 'index'])->name('appearance.widgets');
        Route::post('/appearance/widgets', [WidgetController::class, 'store'])->name('appearance.widgets.store');
        Route::post('/appearance/widgets/reorder', [WidgetController::class, 'reorder'])->name('appearance.widgets.reorder');
        Route::put('/appearance/widgets/{widget}', [WidgetController::class, 'update'])->name('appearance.widgets.update');
        Route::delete('/appearance/widgets/{widget}', [WidgetController::class, 'destroy'])->name('appearance.widgets.destroy');
        Route::get('/appearance/dynamic-pages', [DynamicPageController::class, 'index'])->name('appearance.dynamic-pages.index');
        Route::get('/appearance/dynamic-pages/{type}/edit', [DynamicPageController::class, 'edit'])->name('appearance.dynamic-pages.edit');
        Route::put('/appearance/dynamic-pages/{type}', [DynamicPageController::class, 'update'])->name('appearance.dynamic-pages.update');
    });

    Route::middleware('permission:manage_roles')->group(function () {
        Route::get('/users/roles', [RoleController::class, 'index'])->name('users.roles');
        Route::put('/users/roles/{role}', [RoleController::class, 'update'])->name('users.roles.update');
    });

    Route::middleware('permission:manage_users')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::post('/users/bulk', [UserController::class, 'bulk'])->name('users.bulk');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});

Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('/blog', [SiteController::class, 'blog'])->name('blog');
Route::get('/news', [SiteController::class, 'blog'])->name('news');
Route::get('/campaign', [SiteController::class, 'campaign'])->name('campaign');
Route::get('/search', [SiteController::class, 'search'])->name('search');
Route::get('/archive/{year?}/{month?}/{day?}', [SiteController::class, 'archive'])
    ->whereNumber('year')
    ->whereNumber('month')
    ->whereNumber('day')
    ->name('archive');
Route::get('/category/{slug}', [SiteController::class, 'category'])->name('category.show');
Route::get('/tag/{slug}', [SiteController::class, 'tag'])->name('tag.show');
Route::get('/author/{username}', [SiteController::class, 'author'])->name('author.show');

Route::get('/posts/{slug}', [SiteController::class, 'post'])->name('posts.show.posts');
Route::get('/blog/{slug}', [SiteController::class, 'post'])->name('posts.show.blog');
Route::get('/news/{slug}', [SiteController::class, 'post'])->name('posts.show.news');
Route::get('/campaign/{slug}', [SiteController::class, 'campaignShow'])->name('campaign.show');

$reserved = collect(config('cms.reserved_slugs', [
    'admin', 'api', 'setup', 'login', 'logout', 'register', 'blog', 'news', 'campaign', 'posts',
    'category', 'tag', 'author', 'search', 'archive',
]))->map(fn ($slug) => preg_quote((string) $slug, '/'))->implode('|');

Route::get('/{slug}', [SiteController::class, 'page'])->name('pages.show')
    ->where('slug', $reserved !== '' ? "^(?!{$reserved}).*$" : '.*');
