<?php

use App\Http\Controllers\BlogFeedController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', function () {
    $base = rtrim(config('app.url'), '/');
    $body = "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /api\n\nSitemap: {$base}/sitemap.xml\n";
    return response($body, 200, ['Content-Type' => 'text/plain']);
})->name('robots');

Route::get('/sitemap.xml', function () {
    $path = public_path('sitemap.xml');
    if (! file_exists($path)) {
        \Illuminate\Support\Facades\Cache::lock('sitemap-generate', 30)->block(10, function () {
            if (! file_exists(public_path('sitemap.xml'))) {
                Artisan::call('sitemap:generate');
            }
        });
    }
    return response()->file($path, ['Content-Type' => 'application/xml']);
})->name('sitemap');

Route::middleware('setlocale')->group(function () {
    Route::get('/', [PageController::class, 'home'])->name('home');
    Route::get('/bilder', [PageController::class, 'bilder'])->name('bilder');
    Route::get('/ueber-uns', [PageController::class, 'about'])->name('about');

    Route::get('/blog', [PostController::class, 'index'])->name('blog.index');
    Route::get('/blog/{slug}', [PostController::class, 'show'])
        ->where('slug', '[a-z0-9-]+')
        ->name('blog.show');

    Route::prefix('en')->group(function () {
        Route::get('/', [PageController::class, 'home'])->name('home.en');
        Route::get('/gallery', [PageController::class, 'bilder'])->name('bilder.en');
        Route::get('/about', [PageController::class, 'about'])->name('about.en');

        Route::get('/blog', [PostController::class, 'index'])->name('blog.index.en');
        Route::get('/blog/{slug}', [PostController::class, 'show'])
            ->where('slug', '[a-z0-9-]+')
            ->name('blog.show.en');
    });
});

Route::get('/blog/preview/{post}', [PostController::class, 'preview'])
    ->middleware('signed')
    ->name('blog.preview');

Route::get('/blog/feed.xml', [BlogFeedController::class, 'de'])->name('blog.feed');
Route::get('/en/blog/feed.xml', [BlogFeedController::class, 'en'])->name('blog.feed.en');
