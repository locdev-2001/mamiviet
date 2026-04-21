# Phase 03 — Blog frontend (React + SEO per-post)

## Context Links

- [plan.md](plan.md)
- [phase-02-blog-backend.md](phase-02-blog-backend.md)
- Pattern tham khảo:
  - [app/Http/Controllers/PageController.php](../../app/Http/Controllers/PageController.php)
  - [src/App.tsx](../../src/App.tsx)
  - [routes/web.php](../../routes/web.php)
  - [resources/views/app.blade.php](../../resources/views/app.blade.php)

## Overview

- **Priority**: Cao
- **Status**: Pending
- **Depends on**: Phase 02 xong
- Render `/blog`, `/blog/{slug}` (+ `/en/blog`, `/en/blog/{slug}`) với SEO server-side đầy đủ (title/description/keywords/OG/canonical/hreflang/Article JSON-LD), React hydrate list + content.

## Key Insights

- Frontend là **Blade + React hydration**, KHÔNG phải SPA fallback → SEO phải render server-side
- Pattern `view('app', ['seo' => ..., 'appContent' => ...])` đã có, Blog tái dùng
- React nhận `appContent` qua biến global inline (xem `app.blade.php`) → pass post meta qua đây (**không** nhét full HTML content)
- **HTML content render trực tiếp trong Blade** `<div id="post-content">{!! $sanitizedHtml !!}</div>`, React hydrate đọc từ DOM ref → giảm JSON payload, tốt cho SEO (Google crawl HTML, không cần run JS)
- **Preformat date ở backend** (`"21. April 2026"` cho de, `"April 21, 2026"` cho en) → FE chỉ render string, tránh hydration mismatch
- Route React (`App.tsx`) phải match route Laravel
- Lazy-load Blog pages (Vite dynamic import) để không phình bundle homepage
- `partials/jsonld-breadcrumb.blade.php` **đã tồn tại** → chỉ build `$breadcrumb` array trong `PostController::show()` pass vào `view('app', ...)`

## Requirements

**Functional**
- List page `/blog`: grid card (cover, title, excerpt, reading_time, published_at), phân trang 12 bài/trang
- Detail page `/blog/{slug}`: cover hero + title + meta (author, date, reading_time) + content + related posts (3 bài cùng tag hoặc mới nhất)
- Song ngữ: `/blog` (de) + `/en/blog` (en), nội dung theo locale, hreflang chính xác
- 404 với SEO noindex nếu slug không tồn tại
- SEO per-post: title/description/keywords/OG/canonical/hreflang/Article JSON-LD
- Breadcrumb hiển thị: Home → Blog → Post title

**Non-functional**
- TTFB < 300ms (query post + hydrate data)
- LCP cover image < 2.5s (preload + responsive srcset)
- CLS < 0.1 (fix dimensions cho cover image)
- Bundle Blog page < 50KB gzipped

## Architecture

```
Request /blog/pho-leipzig
    ↓
routes/web.php (middleware setlocale)
    ↓ setlocale = 'de'
PostController::show('pho-leipzig')
    ├─> Post::published()->with('media')
    │    ->whereRaw("JSON_UNQUOTE(slug->'$.de') = ?", [$slug])->first()
    │    └─ không tìm thấy → view('app', SEO noindex) status 404
    ├─> SeoBuilder::forPost($post, 'de') — dùng seo_* + fallback title/excerpt
    ├─> $postMeta = PostApiResource::forLocale($post, 'de') (KHÔNG bao gồm content)
    ├─> $sanitizedHtml = $post->getTranslation('content', 'de')  // đã sanitize lúc lưu
    ├─> $breadcrumb = [['name' => 'Home', 'url' => ...], ['name' => 'Blog', ...], ['name' => $title, ...]]
    └─> view('app', ['seo', 'appContent' => ['post' => $postMeta, 'related' => ...], 'breadcrumb', 'postContent' => $sanitizedHtml])
         ├─> <x-seo> render <head>
         │    ├─ @include partials.jsonld-article
         │    └─ @include partials.jsonld-breadcrumb (đã tồn tại)
         └─> <div id="root">
              ├─ <script>window.__APP_CONTENT__ = @json($appContent)</script>  // meta, không HTML
              └─ <template id="post-content-html">{!! $postContent !!}</template>  // HTML sanitized
                   └─ React App.tsx hydrate
                        └─ BlogPost.tsx đọc window.__APP_CONTENT__ (meta) + template (HTML)
                             └─ DOMPurify re-sanitize (defense in depth) + html-react-parser
```

## Related Code Files

**Create:**
- `app/Http/Controllers/PostController.php` — index/show/preview
- `app/Http/Resources/PostApiResource.php` — transform meta (không content)
- `app/Support/SeoBuilder.php` — DRY SEO (refactor từ PageController::buildSeo)
- `resources/views/partials/jsonld-article.blade.php`
- `src/pages/Blog.tsx` — list page
- `src/pages/BlogPost.tsx` — detail page
- `src/components/blog/PostCard.tsx`
- `src/components/blog/PostContent.tsx` — đọc template HTML + DOMPurify sanitize + parse
- `src/components/blog/RelatedPosts.tsx`
- `src/components/blog/PostMeta.tsx` — author + date + reading time
- `src/lib/hooks/useAppContent.ts` — đọc `window.__APP_CONTENT__`
- `src/lib/types/post.ts`

**Modify:**
- `routes/web.php` — thêm routes `/blog`, `/blog/{slug}`, `/en/blog`, `/en/blog/{slug}`
- `src/App.tsx` — thêm routes lazy-load Blog + BlogPost
- `resources/views/app.blade.php` — inject `<template id="post-content-html">` + conditional `@include` Article JSON-LD
- `app/Http/Controllers/PageController.php` — refactor `buildSeo()` gọi `SeoBuilder::forPage()`

**Reuse (đã tồn tại):**
- `resources/views/partials/jsonld-breadcrumb.blade.php`
- `resources/views/components/seo.blade.php` (mở rộng accept `$jsonLd` array từ Phase 01)

## Implementation Steps

### 1. Extract SEO helper (DRY từ Phase 01)

Tạo `app/Support/SeoBuilder.php`:
```php
class SeoBuilder {
    public static function forPost(Post $post, string $locale): array
    public static function forPage(string $pageKey, string $locale): array  // refactor từ PageController
}
```
`PageController::buildSeo()` gọi `SeoBuilder::forPage()`. `PostController` gọi `SeoBuilder::forPost()`.

### 2. Routes `routes/web.php`

Trong group `setlocale`:
```php
Route::get('/blog', [PostController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [PostController::class, 'show'])->name('blog.show');

// Trong prefix('en')
Route::get('/blog', [PostController::class, 'index'])->name('blog.index.en');
Route::get('/blog/{slug}', [PostController::class, 'show'])->name('blog.show.en');
```

### 3. `PostController`

- `index()`: `Post::published()->with('media')->forLocale($locale)->latest('published_at')->paginate(12)`, pass `posts` (PostApiResource::collection) + `pagination` meta
- `show($slug)`: find post + locale, **eager load author + media**, build SEO, pass:
  - `appContent.post` = PostApiResource (meta)
  - `appContent.related` = 3 bài published gần nhất (trừ bài hiện tại)
  - `breadcrumb` = array cho `jsonld-breadcrumb` partial
  - `postContent` = `$post->getTranslation('content', $locale)` (HTML đã sanitize)
  - `jsonLd` = ['article' => [...Article data]]
- 404 path: `view('app', ['seo' => SeoBuilder::notFound($locale)])` với `response()->setStatusCode(404)`
- `preview($post)` (signed middleware): y hệt `show()` nhưng bỏ qua `scopePublished`, response header `X-Robots-Tag: noindex, nofollow`

### 4. `PostApiResource`

Shape trả về FE (**không bao gồm content HTML** — render trực tiếp Blade):
```ts
{
  id: number,
  slug: string,
  title: string,
  excerpt: string,
  cover: { url, srcset, thumb, card, hero, width, height },
  og_image: string | null,
  author_name: string,              // hard-code "Mamiviet" hoặc Setting::raw('footer.company_name')
  published_at_iso: string,         // ISO cho <time datetime>
  published_at_display: string,     // preformatted theo locale ("21. April 2026" / "April 21, 2026")
  reading_time: number,             // minutes (int)
  url: string,                      // full URL per locale
}
```

**Preformat date** qua `Carbon::parse($post->published_at)->locale($locale)->isoFormat('D. MMMM YYYY' | 'MMMM D, YYYY')`. Lý do: tránh hydration mismatch + giảm work FE.

### 5. JSON-LD partials

**`jsonld-article.blade.php`** (tạo mới):
```json
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "...",
  "image": ["..."],
  "author": {"@type": "Person", "name": "..."},
  "publisher": {"@type": "Organization", "name": "Mamiviet", "logo": {"@type": "ImageObject", "url": "..."}},
  "datePublished": "ISO8601",
  "dateModified": "ISO8601",
  "mainEntityOfPage": "canonical url"
}
```

**`jsonld-breadcrumb.blade.php`** — **đã tồn tại**, chỉ cần pass `$breadcrumb` đúng format từ `PostController::show()`.

### 6. `<x-seo>` enhancement

Accept optional `$jsonLd` prop (array of schema types). Render mỗi schema thành `<script type="application/ld+json">`.

### 7. React routes

`src/App.tsx`:
```tsx
const Blog = lazy(() => import("@/pages/Blog"));
const BlogPost = lazy(() => import("@/pages/BlogPost"));

<Route path="/blog" element={<Blog />} />
<Route path="/blog/:slug" element={<BlogPost />} />
<Route path="/en/blog" element={<Blog />} />
<Route path="/en/blog/:slug" element={<BlogPost />} />
```

### 8. `useAppContent` hook

```ts
export function useAppContent<T>(): T | null {
  return (window as any).__APP_CONTENT__ ?? null;
}
```

### 9. `Blog.tsx` (list)

- Render grid PostCard (responsive: 1 col mobile, 2 col tablet, 3 col desktop)
- Pagination component (next/prev)
- Skeleton fallback khi data chưa tới (dù SSR có sẵn, phòng trường hợp client navigate)

### 10. `BlogPost.tsx` (detail)

- Hero section: cover image (aspect 16/9, lazy nhưng priority=high cho LCP)
- Title h1, PostMeta (author, date, reading_time)
- `<PostContent html={post.content} />` — parse + sanitize + lazy ảnh inline
- Sidebar/footer: RelatedPosts
- Share buttons (Facebook, Twitter, WhatsApp — static links)

### 11. `PostContent.tsx`

Đọc HTML từ `<template id="post-content-html">` trong DOM (đã được Blade render sẵn, tốt cho crawler):

```tsx
import parse from "html-react-parser";
import DOMPurify from "dompurify";
import { useMemo } from "react";

export function PostContent() {
  const html = useMemo(() => {
    const tpl = document.getElementById("post-content-html") as HTMLTemplateElement | null;
    return tpl?.innerHTML ?? "";
  }, []);

  const clean = useMemo(() => DOMPurify.sanitize(html, {
    ALLOWED_TAGS: ['p','h2','h3','h4','ul','ol','li','a','strong','em','u','s','blockquote','img','figure','figcaption','iframe','table','thead','tbody','tr','td','th','code','pre','hr','br','div','span'],
    ALLOWED_ATTR: ['href','title','rel','target','src','alt','width','height','loading','allow','allowfullscreen','srcset','sizes','class','data-type','frameborder'],
    ALLOWED_URI_REGEXP: /^(https?:\/\/|\/storage\/|\/)/,
    ADD_TAGS: ['iframe'],
  }), [html]);

  return <div className="prose prose-lg max-w-none">{parse(clean, {
    replace: (node) => {
      // swap <img> → lazy, <iframe youtube> → YoutubeEmbed component
    }
  })}</div>;
}
```

**Lưu ý SSR**: Blade đã render HTML trong template → crawler đọc được ngay. React chỉ enhance sau hydration (lazy image, click handler).

### 12. Types

`src/lib/types/post.ts` — mirror `PostApiResource` shape.

### 13. i18n

Thêm keys vào `src/lib/locales/de.json` + `en.json`:
- `blog.title`, `blog.readMore`, `blog.readingTime`, `blog.publishedOn`, `blog.relatedPosts`, `blog.backToList`, `blog.notFound.title`, `blog.notFound.message`

### 14. Build check

```bash
npm run build
# verify bundle size Blog chunk
php artisan route:list | grep blog
# curl /blog → view-source check meta + JSON-LD
```

## Todo List

- [ ] Extract `SeoBuilder` class (`forPage`, `forPost`, `notFound`) — refactor `PageController::buildSeo`
- [ ] `PostController::index/show/preview` + 404 handling + eager load
- [ ] `PostApiResource` transform (không bao gồm content HTML; preformat date)
- [ ] Routes `/blog` + `/blog/{slug}` cho de + en
- [ ] `jsonld-article.blade.php` (tạo mới)
- [ ] Tái dùng `jsonld-breadcrumb.blade.php` (đã có) — build `$breadcrumb` trong controller
- [ ] `<x-seo>` accept `$jsonLd` array (từ Phase 01, confirm)
- [ ] `app.blade.php` inject `<template id="post-content-html">` khi có `$postContent`
- [ ] `src/App.tsx` add Blog routes lazy
- [ ] `useAppContent` hook
- [ ] `Blog.tsx` list + pagination
- [ ] `BlogPost.tsx` detail — đọc `postMeta` từ appContent + HTML từ template
- [ ] `PostCard`, `PostMeta`, `PostContent`, `RelatedPosts` components
- [ ] i18n keys de/en (blog.*)
- [ ] Hreflang fallback: nếu post thiếu 1 locale → bỏ alternate link đó + x-default trỏ blog list
- [ ] Manual test: `/blog`, `/blog/{slug}`, `/en/blog/{slug}`, 404 slug, draft preview signed URL
- [ ] Lighthouse audit > 90 SEO + Performance mobile
- [ ] Rich Results Test pass Article + BreadcrumbList schema

## Success Criteria

- `/blog` render list đúng locale, paginate hoạt động
- `/blog/{slug}` render đúng bài, hreflang trỏ đúng counterpart
- View-source có đủ: title, description, keywords, OG, Twitter, canonical, hreflang, Article JSON-LD, BreadcrumbList JSON-LD
- 404 slug không tồn tại → robots=noindex, React render NotFound component
- Lighthouse SEO = 100, Performance > 85 mobile
- Rich Results Test: 0 errors, 0 warnings cho Article + BreadcrumbList

## Risk Assessment

| Risk | Mitigation |
|------|-----------|
| DOMPurify khác Purifier allowlist → block content hợp lệ | Định nghĩa allowlist chung 1 chỗ (document), BE/FE tham chiếu cùng list |
| Hreflang trỏ sai khi post chỉ có 1 locale | `SeoBuilder::forPost` — nếu thiếu counterpart bỏ alternate link, x-default trỏ blog list URL; hreflang de luôn là fallback cuối |
| LCP chậm do cover image lớn | srcset + `loading="eager"` + `fetchpriority="high"` cho cover hero, conversions `hero` 1600×900 WebP, preload hint |
| Hydration mismatch date | **Preformat date string ở BE `PostApiResource`**, FE chỉ render string — không dùng date-fns trong render path |
| Bundle blog nặng do html-react-parser + dompurify | Lazy-load BlogPost.tsx, total ~40KB gzipped — chấp nhận được |
| Slug unicode (tiếng Việt có dấu) trong URL | `Str::slug` đã xử lý ở Phase 02 |
| `<template>` innerHTML bị cache stale khi client-side navigate | Không issue — route refresh full page (server-render); nếu SPA navigate trong tương lai, fetch via API |
| JSON payload `__APP_CONTENT__` phình | **Không bao gồm content HTML**, chỉ meta → payload < 2KB/post |

## Security Considerations

- XSS: 2-tầng sanitize (Purifier BE lưu + DOMPurify FE render) — defense in depth
- CSRF: GET-only trên blog, không vấn đề
- Rate limit: nếu cần, thêm `throttle:60,1` cho `/blog/*`
- Unpublished posts: `scopePublished()` đã filter — không leak draft
- Preview draft: signed URL riêng (phase 02), không public route
- `window.__APP_CONTENT__`: JSON encode với `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` để chống XSS inline script injection

## Next Steps

- Phase 04: Sitemap thêm post URLs, RSS feed, Restaurant homepage JSON-LD enhancements
