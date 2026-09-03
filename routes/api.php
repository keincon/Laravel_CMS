<?php

use App\Http\Controllers\Api\V1\Admin\AdminContentController;
use App\Http\Controllers\Api\V1\ContentController;
use App\Http\Controllers\Api\V1\PublicApiController;
use App\Http\Controllers\Api\Wp\V2\WpRestController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->middleware('throttle:api')->group(function () {
    Route::get('/posts', [PublicApiController::class, 'posts'])->name('posts.index');
    Route::get('/posts/{slug}', [PublicApiController::class, 'showPost'])->name('posts.show');
    Route::get('/pages', [PublicApiController::class, 'pages'])->name('pages.index');
    Route::get('/pages/{slug}', [PublicApiController::class, 'showPage'])->name('pages.show');
    Route::get('/categories', [PublicApiController::class, 'categories'])->name('categories.index');
    Route::get('/categories/{slug}', [PublicApiController::class, 'showCategory'])->name('categories.show');
    Route::get('/tags', [PublicApiController::class, 'tags'])->name('tags.index');
    Route::get('/tags/{slug}', [PublicApiController::class, 'showTag'])->name('tags.show');
    Route::get('/authors', [PublicApiController::class, 'authors'])->name('authors.index');
    Route::get('/authors/{username}', [PublicApiController::class, 'showAuthor'])->name('authors.show');
    Route::get('/menus', [PublicApiController::class, 'menus'])->name('menus.index');
    Route::get('/settings', [PublicApiController::class, 'settings'])->name('settings');
    Route::get('/theme', [PublicApiController::class, 'theme'])->name('theme');

    Route::get('/types', [ContentController::class, 'types'])->name('types.index');
    Route::get('/contents', [ContentController::class, 'index'])->name('contents.index');
    Route::get('/contents/{uuidOrSlug}', [ContentController::class, 'show'])->name('contents.show');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/contents', [ContentController::class, 'store'])->name('contents.store');
        Route::put('/contents/{content}', [ContentController::class, 'update'])->name('contents.update');
        Route::patch('/contents/{content}', [ContentController::class, 'update'])->name('contents.patch');
        Route::delete('/contents/{content}', [ContentController::class, 'destroy'])->name('contents.destroy');

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::middleware('permission:manage_posts')->group(function () {
                Route::post('/posts', [AdminContentController::class, 'storePost'])->name('posts.store');
                Route::put('/posts/{post}', [AdminContentController::class, 'updatePost'])->name('posts.update');
                Route::delete('/posts/{post}', [AdminContentController::class, 'destroyPost'])->name('posts.destroy');
            });

            Route::middleware('permission:manage_pages')->group(function () {
                Route::post('/pages', [AdminContentController::class, 'storePage'])->name('pages.store');
                Route::put('/pages/{page}', [AdminContentController::class, 'updatePage'])->name('pages.update');
                Route::delete('/pages/{page}', [AdminContentController::class, 'destroyPage'])->name('pages.destroy');
            });
        });
    });
});

// WordPress-compatible REST API (subset)
Route::prefix('wp/v2')->name('api.wp.v2.')->middleware('throttle:api')->group(function () {
    Route::get('/types', [WpRestController::class, 'types'])->name('types');
    Route::get('/statuses', [WpRestController::class, 'statuses'])->name('statuses');
    Route::get('/posts', [WpRestController::class, 'posts'])->name('posts');
    Route::get('/pages', [WpRestController::class, 'pages'])->name('pages');
    Route::get('/categories', [WpRestController::class, 'categories'])->name('categories');
    Route::get('/tags', [WpRestController::class, 'tags'])->name('tags');
    Route::get('/taxonomies', [WpRestController::class, 'taxonomies'])->name('taxonomies');
    Route::get('/media', [WpRestController::class, 'media'])->name('media');
    Route::get('/users', [WpRestController::class, 'users'])->name('users');
    Route::get('/comments', [WpRestController::class, 'comments'])->name('comments');
    Route::get('/settings', [WpRestController::class, 'settings'])->name('settings');
    Route::get('/search', [WpRestController::class, 'search'])->name('search');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::post('/posts', [WpRestController::class, 'storePost'])->name('posts.store');
        Route::put('/posts/{content}', [WpRestController::class, 'updatePost'])->name('posts.update');
        Route::delete('/posts/{content}', [WpRestController::class, 'destroyPost'])->name('posts.destroy');
        Route::post('/pages', [WpRestController::class, 'storePage'])->name('pages.store');
        Route::put('/pages/{content}', [WpRestController::class, 'updatePage'])->name('pages.update');
        Route::delete('/pages/{content}', [WpRestController::class, 'destroyPage'])->name('pages.destroy');
    });
});
