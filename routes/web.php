<?php

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::middleware('setlocale')->group(function () {
    Route::get('/', [PageController::class, 'home'])->name('home');
    Route::get('/bilder', [PageController::class, 'bilder'])->name('bilder');

    Route::prefix('en')->group(function () {
        Route::get('/', [PageController::class, 'home'])->name('home.en');
        Route::get('/gallery', [PageController::class, 'bilder'])->name('bilder.en');
    });
});
