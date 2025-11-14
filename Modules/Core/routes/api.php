<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\CoreController;
use Modules\Core\Http\Controllers\LmStudioController;
use Modules\Core\Http\Controllers\LmStudioJobController;
use Modules\Core\Http\Controllers\LmStudioRoleController;

Route::prefix('v1')->group(function () {
    Route::get('ai/lmstudio/models', [LmStudioController::class, 'models'])->name('ai.lmstudio.models');
    Route::post('ai/lmstudio/infer', [LmStudioController::class, 'infer'])->name('ai.lmstudio.infer');
    Route::post('ai/lmstudio/jobs', [LmStudioJobController::class, 'store'])->name('ai.lmstudio.jobs.store');
    Route::get('ai/lmstudio/jobs/{uuid}', [LmStudioJobController::class, 'show'])->name('ai.lmstudio.jobs.show');
    Route::get('ai/lmstudio/roles', [LmStudioRoleController::class, 'index'])->name('ai.lmstudio.roles.index');

    Route::apiResource('cores', CoreController::class)->names('core');
});
