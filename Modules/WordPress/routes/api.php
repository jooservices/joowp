<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\WordPress\Http\Controllers\CategoryController;
use Modules\WordPress\Http\Controllers\TokenController;

Route::prefix('v1')->group(function () {
    Route::get('wordpress/token', [TokenController::class, 'show'])->name('core.wordpress.token.show');
    Route::post('wordpress/token', [TokenController::class, 'store'])->name('core.wordpress.token.store');
    Route::delete('wordpress/token', [TokenController::class, 'destroy'])->name('core.wordpress.token.destroy');

    Route::prefix('wordpress')->name('core.wordpress.')->group(function () {
        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::post('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    });
});
