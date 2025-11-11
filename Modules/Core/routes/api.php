<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\CoreController;
use Modules\Core\Http\Controllers\WordPress\TokenController;

Route::prefix('v1')->group(function () {
    Route::post('wordpress/token', TokenController::class)->name('core.wordpress.token.store');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::apiResource('cores', CoreController::class)->names('core');
    });
});
