<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MorphController;
use App\Http\Controllers\Api\StateController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\VisitorController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->group(function () {
    Route::get('states', [StateController::class, 'index'])->name('state.index');
    Route::get('state/{state?}', [StateController::class, 'show'])->name('state.show');
    Route::get('category/props/{category?}', [CategoryController::class, 'props'])->name('category.prop');
    Route::post('morph/search', [MorphController::class, 'search'])->name('morph.search');
    Route::post('visitor/display', [VisitorController::class, 'display'])->name('visitor.display');
    Route::get('tag/search/{q?}', [TagController::class, 'search'])->name('tag.search');
});
