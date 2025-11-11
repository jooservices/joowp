<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\CoreController;
use Modules\Core\Http\Controllers\WordPress\TokenController;

Route::prefix('v1')->group(function () {
    Route::get('wordpress/token', [TokenController::class, 'show'])->name('core.wordpress.token.show');
    Route::post('wordpress/token', [TokenController::class, 'store'])->name('core.wordpress.token.store');
    Route::delete('wordpress/token', [TokenController::class, 'destroy'])->name('core.wordpress.token.destroy');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::apiResource('cores', CoreController::class)->names('core');
    });
});
