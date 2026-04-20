<?php

use Illuminate\Support\Facades\Route;

// SPA — serve built React app for the landing page.
Route::get('/', function () {
    $index = public_path('build/index.html');
    abort_unless(file_exists($index), 404, 'Frontend build missing. Run: npm run build');
    return response()->file($index);
});

// SPA fallback — any non-admin, non-api route serves the React app.
Route::fallback(function () {
    $index = public_path('build/index.html');
    abort_unless(file_exists($index), 404, 'Frontend build missing. Run: npm run build');
    return response()->file($index);
});
