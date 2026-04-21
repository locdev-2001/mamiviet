<?php

namespace App\Observers;

use App\Jobs\RegenerateSitemap;
use App\Models\Post;
use Illuminate\Support\Facades\Cache;

class PostObserver
{
    private const WATCHED_FIELDS = ['status', 'published_at', 'slug', 'title'];

    public function saved(Post $post): void
    {
        if (! $post->wasRecentlyCreated) {
            $changed = array_intersect(self::WATCHED_FIELDS, array_keys($post->getChanges()));
            if ($changed === []) {
                return;
            }
        }

        $this->triggerRefresh();
    }

    public function deleted(Post $post): void
    {
        $this->triggerRefresh();
    }

    public function restored(Post $post): void
    {
        $this->triggerRefresh();
    }

    public function forceDeleted(Post $post): void
    {
        $this->triggerRefresh();
    }

    private function triggerRefresh(): void
    {
        Cache::forget('blog.feed.de');
        Cache::forget('blog.feed.en');

        RegenerateSitemap::dispatch();
    }
}
