<?php

use App\Http\Controllers\User\InstagramPostController;
use Illuminate\Support\Facades\Route;

Route::middleware('setlocale')->prefix('user')->group(function () {
    Route::get('instagram-posts', [InstagramPostController::class, 'index']);
    Route::get('instagram-posts/{id}', [InstagramPostController::class, 'show']);
});
