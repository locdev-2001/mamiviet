<?php

namespace App\Providers;

use App\Observers\MediaDimensionObserver;
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
    }
}
