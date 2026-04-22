<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostApiResource;
use App\Models\Post;
use App\Support\SeoBuilder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class BlogFeedController extends Controller
{
    private const MAX_ITEMS = 20;
    private const CACHE_TTL = 900; // 15 minutes

    public function de(): Response
    {
        return $this->render('de');
    }

    public function en(): Response
    {
        return $this->render('en');
    }

    private function render(string $locale): Response
    {
        $xml = Cache::remember("blog.feed.{$locale}", self::CACHE_TTL, function () use ($locale) {
            $posts = Post::query()
                ->published()
                ->forLocale($locale)
                ->with('media')
                ->latest('published_at')
                ->limit(self::MAX_ITEMS)
                ->get();

            $items = PostApiResource::collectionForLocale($posts, $locale);

            $base = rtrim((string) config('app.url'), '/');
            $channelPath = $locale === 'en' ? '/en/blog' : '/blog';
            $feedPath = $locale === 'en' ? '/en/blog/feed.xml' : '/blog/feed.xml';

            $companyName = (string) (\App\Models\Setting::raw('footer.company_name') ?: 'Mamiviet');
            $channelTitle = "{$companyName} — Blog";
            $channelDescription = (string) (SeoBuilder::forPage('blog', $locale)['description'] ?? '');

            $body = view('feeds.rss', [
                'locale' => $locale,
                'items' => $items,
                'channel_title' => $channelTitle,
                'channel_link' => $base . $channelPath,
                'feed_self' => $base . $feedPath,
                'channel_description' => $channelDescription,
                'company_name' => $companyName,
            ])->render();

            return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $body;
        });

        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=900',
        ]);
    }
}
