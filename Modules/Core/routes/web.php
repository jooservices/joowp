<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Modules\Core\Http\Controllers\CoreController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('cores', CoreController::class)->names('core');

    Route::get('ai/lmstudio/demo', function () {
        return Inertia::render('LmStudio/Demo');
    })->name('lmstudio.demo');
});
