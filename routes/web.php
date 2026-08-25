<?php

use App\Http\Controllers\Admin\ApiDocsController;
use App\Http\Controllers\Admin\ApiTokenController;
use App\Http\Controllers\Admin\FooterController;
use App\Http\Controllers\Admin\HeaderController;
use App\Http\Controllers\Admin\LayoutSettingsController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\SeoSettingsController;
use App\Http\Controllers\Admin\ThemeSettingsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Setup\SetupController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', [SiteController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SiteController::class, 'robots'])->name('robots');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

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
    Route::get('/', fn () => view('admin.dashboard'))->name('dashboard');

    Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
    Route::get('/pages/create', [PageController::class, 'create'])->name('pages.create');
    Route::post('/pages', [PageController::class, 'store'])->name('pages.store');
    Route::get('/pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
    Route::put('/pages/{page}', [PageController::class, 'update'])->name('pages.update');
    Route::delete('/pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy');
    Route::get('/pages/{page}/preview', [PageController::class, 'preview'])->name('pages.preview');

    Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
    Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
    Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
    Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    Route::get('/posts/{post}/preview', [PostController::class, 'preview'])->name('posts.preview');

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

    Route::get('/settings/seo', [SeoSettingsController::class, 'edit'])->name('settings.seo');
    Route::put('/settings/seo', [SeoSettingsController::class, 'update'])->name('settings.seo.update');
    Route::get('/settings/ogp', [SeoSettingsController::class, 'editOgp'])->name('settings.ogp');
    Route::put('/settings/ogp', [SeoSettingsController::class, 'updateOgp'])->name('settings.ogp.update');
    Route::get('/settings/permalinks', [SeoSettingsController::class, 'editPermalinks'])->name('settings.permalinks');
    Route::put('/settings/permalinks', [SeoSettingsController::class, 'updatePermalinks'])->name('settings.permalinks.update');
    Route::get('/settings/reading', [LayoutSettingsController::class, 'reading'])->name('settings.reading');
    Route::put('/settings/reading', [LayoutSettingsController::class, 'updateReading'])->name('settings.reading.update');
    Route::get('/settings/api', [ApiDocsController::class, 'show'])->name('settings.api');

    Route::get('/appearance/layout', [LayoutSettingsController::class, 'edit'])->name('appearance.layout');
    Route::put('/appearance/layout', [LayoutSettingsController::class, 'update'])->name('appearance.layout.update');
    Route::get('/appearance/colors', [ThemeSettingsController::class, 'colors'])->name('appearance.colors');
    Route::put('/appearance/colors', [ThemeSettingsController::class, 'updateColors'])->name('appearance.colors.update');
    Route::post('/appearance/colors/reset', [ThemeSettingsController::class, 'reset'])->name('appearance.colors.reset');
    Route::get('/appearance/mode', [ThemeSettingsController::class, 'mode'])->name('appearance.mode');
    Route::put('/appearance/mode', [ThemeSettingsController::class, 'updateMode'])->name('appearance.mode.update');

    Route::get('/users/tokens', [ApiTokenController::class, 'index'])->name('users.tokens');
    Route::post('/users/tokens', [ApiTokenController::class, 'store'])->name('users.tokens.store');
    Route::delete('/users/tokens/{tokenId}', [ApiTokenController::class, 'destroy'])->name('users.tokens.destroy');
});

Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('/blog', [SiteController::class, 'blog'])->name('blog');
Route::get('/category/{slug}', [SiteController::class, 'category'])->name('category.show');
Route::get('/tag/{slug}', [SiteController::class, 'tag'])->name('tag.show');
Route::get('/author/{username}', [SiteController::class, 'author'])->name('author.show');

Route::get('/posts/{slug}', [SiteController::class, 'post'])->name('posts.show.posts');
Route::get('/blog/{slug}', [SiteController::class, 'post'])->name('posts.show.blog');

Route::get('/{slug}', [SiteController::class, 'page'])->name('pages.show')
    ->where('slug', '^(?!admin|setup|api|login|blog|posts|category|tag|author).*$');
