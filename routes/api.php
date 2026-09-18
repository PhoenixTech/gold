<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\MorphController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StateController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\VisitorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('', function () {
    return 'xshop api:'.config('app.name');
});

Route::get('/clear', function () {

    if (! auth()->check()) {
        return abort(403);
    }
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('config:cache');
    Artisan::call('view:clear');
    Artisan::call('route:clear');

    return 'Cleared!';

});

Route::prefix('v1')->name('v1.')->group(
    function () {
        Route::get('', function () {
            return 'xShop api v1';
        });

        Route::get('states', [StateController::class, 'index'])->name('state.index');
        Route::get('state/{state?}', [StateController::class, 'show'])->name('state.show');
        Route::get('categories', [CategoryController::class, 'index'])->name('category.index');
        Route::get('groups', [GroupController::class, 'index'])->name('group.index');
        Route::get('category/{category:slug}', [CategoryController::class, 'show'])->name('category.show');
        Route::get('group/{group:slug}', [GroupController::class, 'show'])->name('group.show');
        Route::get('products', [ProductController::class, 'index'])->name('product.index');
        Route::get('category/props/{category?}', [CategoryController::class, 'props'])->name('category.prop');
        Route::post('morph/search', [MorphController::class, 'search'])->name('morph.search');
        Route::post('visitor/display', [VisitorController::class, 'display'])->name('visitor.display');

        Route::apiResource('web', HomeController::class)->only('index');
        Route::get('tag/search/{q?}', [TagController::class, 'search'])->name('tag.search');

    });
