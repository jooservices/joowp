<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\CoreController;

Route::prefix('v1')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::apiResource('cores', CoreController::class)->names('core');
    });
