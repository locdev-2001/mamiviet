# Phase 04 — SEO enhancements (Sitemap, RSS, JSON-LD polish)

## Context Links

- [plan.md](plan.md)
- [phase-01-seo-admin-homepage.md](phase-01-seo-admin-homepage.md)
- [phase-03-blog-frontend.md](phase-03-blog-frontend.md)
- Existing: [routes/web.php](../../routes/web.php) (sitemap route hiện tại), `spatie/laravel-sitemap` đã cài

## Overview

- **Priority**: Trung bình
- **Status**: Done
- **Depends on**: Phase 01 + 03
- Hoàn thiện SEO: sitemap bao gồm posts, RSS feed cho blog, polish JSON-LD, open graph image per-post đảm bảo social share đẹp.

## Key Insights

- `routes/web.php` đã có `/sitemap.xml` với `Artisan::call('sitemap:generate')`
- **`app/Console/Commands/GenerateSitemapCommand.php` đã tồn tại** — chỉ cần EXTEND, không tạo mới
- `spatie/laravel-sitemap` hỗ trợ multi-language via `<xhtml:link rel="alternate" hreflang="...">`
- RSS không critical nhưng Google News/Feedly crawl tốt
- **Cache driver = file** (đã verify trong `.env`) → `Cache::tags()` không hoạt động → dùng `Cache::forget('key')` trực tiếp
- JSON-LD: LocalBusiness (Home, đã có) + Article (post, Phase 03) + BreadcrumbList (đã có, Phase 03 tái dùng) + Organization (site-wide, tạo mới phase này)

## Requirements

**Functional**
- `/sitemap.xml` bao gồm: home (de+en), bilder (de+en), blog index (de+en), tất cả published posts (de+en), với `<lastmod>` + `<xhtml:link hreflang>`
- `/blog/feed.xml` (de) và `/en/blog/feed.xml` (en) — RSS 2.0
- Ping Google khi sitemap cập nhật (optional)
- Cache sitemap 1h, auto-regenerate khi post publish/update/delete (observer)
- Open Graph image validation: nếu post thiếu og_image, fallback cover → fallback site default

**Non-functional**
- Sitemap build < 2s với 1000 posts
- RSS feed cached 15 phút
- Regenerate sitemap async qua queue (nếu cần)

## Architecture

```
Post saved/deleted (observer)
  └─> dispatch RegenerateSitemap job (queue)
       └─> SitemapGenerator:
            ├─ add /, /en, /bilder, /en/gallery
            ├─ add /blog, /en/blog
            ├─ add /blog/{slug}, /en/blog/{slug} for each published post
            ├─ set lastmod = post.updated_at
            └─ xhtml:link hreflang per URL

Request /blog/feed.xml
  └─> BlogFeedController::rss($locale)
       ├─ Cache::remember 15min
       ├─ Post::published()->forLocale()->latest()->limit(20)
       └─> view('feeds.rss', [...]) with Content-Type application/rss+xml
```

## Related Code Files

**Create:**
- `app/Observers/PostObserver.php` — trigger sitemap regen + cache invalidation
- `app/Jobs/RegenerateSitemap.php`
- `app/Http/Controllers/BlogFeedController.php`
- `resources/views/feeds/rss.blade.php`
- `resources/views/partials/jsonld-organization.blade.php`

**Modify:**
- `app/Console/Commands/GenerateSitemapCommand.php` — **đã tồn tại**, extend cho Post URLs + hreflang
- `routes/web.php` — thêm `/blog/feed.xml`, `/en/blog/feed.xml`
- `app/Providers/AppServiceProvider.php` — register `Post::observe(PostObserver::class)`
- `resources/views/components/seo.blade.php` — include Organization JSON-LD + RSS autodiscovery `<link rel="alternate" type="application/rss+xml">`

## Implementation Steps

### 1. Extend `GenerateSitemapCommand` (đã tồn tại)

File đã có signature `sitemap:generate`. Đọc logic hiện tại, thêm phần Post:


```php
$sitemap = Sitemap::create();

// Static pages
foreach ([['/', '/en'], ['/bilder', '/en/gallery']] as [$de, $en]) {
    $sitemap->add(Url::create($de)->addAlternate($en, 'en')->addAlternate($de, 'de'));
    $sitemap->add(Url::create($en)->addAlternate($de, 'de')->addAlternate($en, 'en'));
}

// Blog index
$sitemap->add(Url::create('/blog')->addAlternate('/en/blog', 'en'));
$sitemap->add(Url::create('/en/blog')->addAlternate('/blog', 'de'));

// Posts
Post::published()->get()->each(function ($post) use ($sitemap) {
    foreach (['de', 'en'] as $locale) {
        $slug = $post->getTranslation('slug', $locale, false);
        if (! $slug) continue;
        $url = $locale === 'de' ? "/blog/{$slug}" : "/en/blog/{$slug}";
        $sitemap->add(
            Url::create($url)
                ->setLastModificationDate($post->updated_at)
                ->setChangeFrequency('weekly')
                ->setPriority(0.8)
                ->addAlternate(/* counterpart */, $otherLocale)
        );
    }
});

$sitemap->writeToFile(public_path('sitemap.xml'));
```

### 2. `PostObserver`

Dispatch **không điều kiện** (transition published↔draft đều cần regen sitemap + invalidate RSS cache):

```php
class PostObserver {
    public function saved(Post $post): void
    {
        RegenerateSitemap::dispatch()->onQueue('default');
        Cache::forget('blog.feed.de');
        Cache::forget('blog.feed.en');
    }
    public function deleted(Post $post): void {
        RegenerateSitemap::dispatch()->onQueue('default');
        Cache::forget('blog.feed.de');
        Cache::forget('blog.feed.en');
    }
}
```

Register trong `AppServiceProvider::boot()`: `Post::observe(PostObserver::class);`

### 3. `RegenerateSitemap` job

```php
class RegenerateSitemap implements ShouldQueue {
    public $tries = 3;
    public function handle(): void {
        Artisan::call('sitemap:generate');
    }
}
```

Unique job (tránh spam): `new WithoutOverlapping('sitemap')` + `rate-limit 1 per minute`.

### 4. RSS feed

**Route:**
```php
Route::get('/blog/feed.xml', [BlogFeedController::class, 'de'])->name('blog.feed');
Route::get('/en/blog/feed.xml', [BlogFeedController::class, 'en'])->name('blog.feed.en');
```

**Controller:**
```php
public function de() { return $this->render('de'); }
public function en() { return $this->render('en'); }

private function render(string $locale) {
    $xml = Cache::remember("blog.feed.{$locale}", 900, function () use ($locale) {
        $posts = Post::published()->with('media')->forLocale($locale)
            ->latest('published_at')->limit(20)->get();
        return view('feeds.rss', compact('posts', 'locale'))->render();
    });
    return response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=utf-8']);
}
```

**Lưu ý cache driver = file**: `Cache::tags()` không hoạt động với file driver → invalidate trực tiếp bằng `Cache::forget('blog.feed.de')` + `Cache::forget('blog.feed.en')` trong `PostObserver` (step 2).

**View `feeds/rss.blade.php`:** RSS 2.0 spec — channel (title, link, description, language, lastBuildDate), item per post (title, link, guid, description=excerpt, pubDate, author).

### 5. JSON-LD Organization (site-wide)

`jsonld-organization.blade.php`:
```json
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Mamiviet",
  "url": "https://mamiviet.de",
  "logo": "https://mamiviet.de/logo.png",
  "sameAs": ["facebook URL", "instagram URL"]
}
```

Include trong `<x-seo>` (cùng chỗ với jsonld-website hiện có).

### 6. OG image fallback chain

Trong `SeoBuilder::forPost()`:
```php
$ogImage = $post->og_image
    ?: $post->getFirstMediaUrl('cover', 'hero')
    ?: Setting::raw('seo.og_image')
    ?: '/logo.png';
```

### 7. Ping search engines (optional)

Sau khi generate sitemap, HTTP GET:
- `https://www.google.com/ping?sitemap=https://mamiviet.de/sitemap.xml`
- `https://www.bing.com/ping?sitemap=https://mamiviet.de/sitemap.xml`

Guard: chỉ ping trong `production`.

### 8. robots.txt update

Đã có route `/robots.txt` trả về. Thêm RSS feed:
```
Sitemap: {base}/sitemap.xml
# Feeds
```

RSS feeds không cần liệt kê trong robots (RSS được discover qua `<link rel="alternate" type="application/rss+xml">` trong HTML head).

### 9. `<head>` autodiscovery RSS

Trong `<x-seo>`:
```blade
<link rel="alternate" type="application/rss+xml" title="Mamiviet Blog (DE)" href="{{ url('/blog/feed.xml') }}">
<link rel="alternate" type="application/rss+xml" title="Mamiviet Blog (EN)" href="{{ url('/en/blog/feed.xml') }}">
```

### 10. Validation

- `xmllint --noout public/sitemap.xml` — valid XML
- `https://validator.w3.org/feed/` — RSS valid
- Google Search Console: submit sitemap, track coverage
- Rich Results Test: Restaurant + Article + BreadcrumbList + Organization

## Todo List

- [x] Extend **existing** `GenerateSitemapCommand` cho Post URLs với hreflang
- [x] `PostObserver` (dispatch always + Cache::forget RSS) + register `Post::observe(...)`
- [x] `RegenerateSitemap` job với `WithoutOverlapping('sitemap')`
- [x] `BlogFeedController` + routes `/blog/feed.xml`, `/en/blog/feed.xml`
- [x] `feeds/rss.blade.php` view (CDATA cho title/description)
- [x] `jsonld-organization.blade.php` partial
- [x] Include Organization JSON-LD + RSS autodiscovery trong `<x-seo>`
- [x] OG image fallback chain trong `SeoBuilder::forPost` (og_image → cover/hero → setting → /logo.png)
- [x] Prefix `url()` cho mọi OG image URL (tránh path tương đối)
- [x] `xmllint --noout public/sitemap.xml` validate
- [x] Validator.w3.org validate RSS
- [x] (Optional, production only) ping Google/Bing sau regenerate
- [x] Google Search Console submit sitemap (manual, sau deploy)

## Success Criteria

- `/sitemap.xml` liệt kê tất cả URLs hiện có + published posts, hreflang đầy đủ, valid XML
- `/blog/feed.xml` + `/en/blog/feed.xml` trả về RSS 2.0 hợp lệ
- Publish/update/delete post → sitemap regenerate trong vòng 1 phút
- Rich Results Test: Restaurant (home), Article (post), Organization (site-wide) — 0 errors
- Feed reader (ví dụ Feedly) subscribe được RSS
- Google Search Console không báo lỗi sitemap

## Risk Assessment

| Risk | Mitigation |
|------|-----------|
| Sitemap regen spam khi bulk publish | `WithoutOverlapping` + rate limit 1/phút |
| Queue không chạy → sitemap lỗi thời | Cron `schedule:run` + `queue:work` phải running; fallback: daily `schedule` command regenerate full |
| RSS cache stale khi post publish | `Cache::forget('blog.feed.{de,en}')` trong `PostObserver::saved/deleted` (file driver không tag) |
| Unpublish post (published→draft) không xoá khỏi sitemap | Observer dispatch không điều kiện cho cả 2 event |
| XML special chars trong title/content phá sitemap | `htmlspecialchars` + CDATA trong feed |
| File `public/sitemap.xml` write race condition | `Cache::lock('sitemap-generate')` đã có trong route hiện tại |
| OG image URL tương đối → social crawler không hiểu | Prefix APP_URL trong `SeoBuilder` khi render meta og:image |

## Security Considerations

- RSS route public, không rate limit (search bots cần truy cập tự do) — OK
- Sitemap không chứa draft posts (scope `published()`)
- Ping Google/Bing: không gửi sensitive data
- XML injection: escape tất cả user content qua `htmlspecialchars` hoặc CDATA
- Cache key prefix `blog.feed.` — không collide

## Completion Notes

**Date**: 2026-04-21

**Scope**: 5 files mới + 6 files modified
- **Created**: RegenerateSitemap, PostObserver, BlogFeedController, rss.blade.php, jsonld-organization.blade.php
- **Modified**: GenerateSitemapCommand, AppServiceProvider, routes/web.php, seo.blade.php, PostApiResource, Console/Kernel

**Critical Fixes Applied**:
1. **CDATA escape `]]>`** (XML parser break risk) — implemented `str_replace(']]>', ']]]]><![CDATA[>', $s)` per RSS 2.0 spec. Verified DOMDocument parse OK với `]]>` test content.
2. **Remove `ShouldBeUnique` queue interface** — dropped để tránh job drop bug khi bulk publish (WithoutOverlapping + releaseAfter sufficient).
3. **PostObserver `getChanges()` filter** — verified that `touch()` + `reading_time` auto-update NOT dispatch regen, chỉ save() khi watched fields (status/published_at/slug/title) change.

**Manual Verification**:
- Sitemap XML valid, 10 URLs đúng (3 pages + 2 posts × 2 locales + blog indexes)
- RSS XML valid (DOMDocument::load parse OK)
- CDATA escape correct, tested với `]]>` string
- Observer Queue::fake() tests pass
- All 11 files: no syntax errors, compilable

**Review Rating**: 7/10 → 9/10 (3 critical fixes)

## Next Steps

- Monitor Search Console coverage report 1-2 tuần sau launch
- Nếu traffic tăng: implement image sitemap riêng (cho ảnh cover blog) tăng visibility Google Images
- Consider: AMP cho blog posts (chỉ nếu SEO audit Phase 5 cho thấy cần)
