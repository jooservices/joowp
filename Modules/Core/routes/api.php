<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\CoreController;
use Modules\Core\Http\Controllers\LmStudioController;

Route::prefix('v1')->group(function () {
    Route::get('ai/lmstudio/models', [LmStudioController::class, 'models'])->name('ai.lmstudio.models');
    Route::post('ai/lmstudio/infer', [LmStudioController::class, 'infer'])->name('ai.lmstudio.infer');

    Route::apiResource('cores', CoreController::class)->names('core');
});
