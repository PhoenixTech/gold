<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Auth::routes(['register' => false]);

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::prefix(config('app.panel.prefix'))->name('admin.')->group(
    function () {
        Route::group(
            ['middleware' => ['auth']],
            function () {


                Route::get('/',[\App\Http\Controllers\HomeController::class,'index'])->name('dash');

                Route::prefix('users')->name('user.')->group(
                    function () {
                        Route::get('', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('index');
                        Route::get('create', [\App\Http\Controllers\Admin\UserController::class, 'create'])->name('create');
                        Route::post('store', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('store');
                        Route::get('edit/{item}', [\App\Http\Controllers\Admin\UserController::class, 'edit'])->name('edit');
                        Route::get('log/{item}', [\App\Http\Controllers\Admin\UserController::class, 'log'])->name('log');
                        Route::get('show/{item}', [\App\Http\Controllers\Admin\UserController::class, 'show'])->name('show');
                        Route::post('update/{item}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('update');
                        Route::get('delete/{item}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('destroy');
                        Route::get('restore/{item}', [\App\Http\Controllers\Admin\UserController::class, 'restore'])->name('restore');
                        Route::post('bulk', [\App\Http\Controllers\Admin\UserController::class, "bulk"])->name('bulk');
                        Route::get('trashed', [\App\Http\Controllers\Admin\UserController::class, "trashed"])->name('trashed');
                    });
                Route::prefix('group')->name('group.')->group(
                    function () {
                        Route::get('', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('index');
                        Route::get('create', [\App\Http\Controllers\Admin\UserController::class, 'create'])->name('create');
                        Route::post('store', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('store');
                        Route::get('edit/{item}', [\App\Http\Controllers\Admin\UserController::class, 'edit'])->name('edit');
                        Route::post('update/{item}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('update');
                        Route::get('delete/{item}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('destroy');
                        Route::get('restore/{item}', [\App\Http\Controllers\Admin\UserController::class, 'restore'])->name('restore');
                        Route::post('bulk', [\App\Http\Controllers\Admin\UserController::class, "bulk"])->name('bulk');
                        Route::get('trashed', [\App\Http\Controllers\Admin\UserController::class, "trashed"])->name('trashed');
                    });
            });
    });