<?php

use App\Http\Controllers\Api\V1\Admin\AdminContentController;
use App\Http\Controllers\Api\V1\PublicApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
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

    Route::middleware('auth:sanctum')->prefix('admin')->name('admin.')->group(function () {
        Route::post('/posts', [AdminContentController::class, 'storePost'])->name('posts.store');
        Route::put('/posts/{post}', [AdminContentController::class, 'updatePost'])->name('posts.update');
        Route::delete('/posts/{post}', [AdminContentController::class, 'destroyPost'])->name('posts.destroy');
        Route::post('/pages', [AdminContentController::class, 'storePage'])->name('pages.store');
        Route::put('/pages/{page}', [AdminContentController::class, 'updatePage'])->name('pages.update');
        Route::delete('/pages/{page}', [AdminContentController::class, 'destroyPage'])->name('pages.destroy');
    });
});
