<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

Route::get('/', function (): Response {
    return Inertia::render('Home');
})->name('home');

Route::prefix('taxonomy')->name('taxonomy.')->group(function (): void {
    Route::inertia('/categories', 'Taxonomy/Categories/Index')->name('categories');
    Route::inertia('/tags', 'Taxonomy/Tags/Index')->name('tags');
});
