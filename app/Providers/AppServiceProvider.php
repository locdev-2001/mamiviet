<?php

namespace App\Providers;

use App\Models\Post;
use App\Observers\MediaDimensionObserver;
use App\Observers\PostObserver;
use Illuminate\Support\ServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Media::observe(MediaDimensionObserver::class);
        Post::observe(PostObserver::class);
    }
}
