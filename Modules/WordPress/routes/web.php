<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(static function (): void {
    // Intentionally left blank; WordPress module currently exposes API routes only.
});
