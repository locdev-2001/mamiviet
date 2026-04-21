# SEO Admin + Blog — Plan

Mục tiêu: admin chủ động quản lý SEO cho trang chính (Home + Bilder) và blog bài viết mới, đầy đủ meta/keywords/OG/JSON-LD, song ngữ de/en. KHÔNG thêm plugin SEO bên thứ ba (tận dụng `<x-seo>` + `Setting` + `PageController::buildSeo()` đã có).

## Bối cảnh

- Stack: Laravel 10, Filament 3.3, React 18 + Vite, MySQL, song ngữ de/en.
- Đã có: `<x-seo>` component, `Setting` key-value với locale map, `GlobalSettings` Filament page, `GlobalSettingsSchema` (SSOT cho settings), `spatie/laravel-sitemap`, `spatie/laravel-translatable`, `spatie-laravel-media-library-plugin`, `filament/spatie-laravel-translatable-plugin`.
- Frontend là **Blade + React hydration** (không phải SPA fallback). Blade render SEO server-side → crawler đọc được ngay.

## Phases

| # | Phase | Trọng tâm | Ước lượng | Status |
|---|-------|-----------|-----------|--------|
| 01 | [SEO admin cho Home/Bilder](phase-01-seo-admin-homepage.md) | keywords, robots, og_image per page, mở rộng JSON-LD LocalBusiness | 0.5 ngày | Done |
| 02 | [Blog backend](phase-02-blog-backend.md) | Post model + PostResource Filament + tiptap editor + draft preview | 1-1.5 ngày | Done |
| 03 | [Blog frontend](phase-03-blog-frontend.md) | `/blog` + `/blog/{slug}` React pages + PostController SEO | 1 ngày | Pending |
| 04 | [SEO enhancements](phase-04-seo-enhancements.md) | Sitemap extend, RSS, Article JSON-LD, Organization | 0.5 ngày | Pending |
| 05 | [Testing & audit](phase-05-testing-audit.md) | Feature tests, sanitizer unit tests, Lighthouse, Rich Results | 0.5 ngày | Pending |

## Dependency

- 01 độc lập, làm trước để chuẩn hoá pattern SEO per-page → 02/03 tái dùng
- 02 → 03 (backend trước, frontend sau)
- 04 sau khi 01 + 03 xong

## Key decisions

- **Không dùng plugin SEO bên ngoài** — mở rộng `<x-seo>` + `GlobalSettingsSchema`
- **Rich editor**: `awcodes/filament-tiptap-editor:^3.0` (branch 3.x, stable với Filament 3.3, xác nhận qua context7)
- **Tiptap output HTML** (default), không dùng custom blocks cho MVP (custom block yêu cầu JSON output, phức tạp hơn — để dành cho sau)
- **Frontend render HTML post**: render **trực tiếp** từ Blade `{!! $sanitizedHtml !!}`, React hydrate lấy content từ DOM ref → không nhét full HTML vào `window.__APP_CONTENT__` (tránh JSON payload phình)
- **XSS defense-in-depth**: `mews/purifier` backend (lúc lưu) + `DOMPurify` frontend (lúc hydrate lại nếu dynamic render)
- **SEO per-post**: cột JSON translatable trên `posts` table (không dùng polymorphic Setting)
- **Slug**: translatable JSON + **generated column + UNIQUE INDEX per locale** (MySQL 8)
- **Date format**: preformat ở backend `PostApiResource` theo locale, FE chỉ render string → tránh hydration mismatch
- **Cache driver = file** → RSS/Setting invalidate bằng `Cache::forget` trực tiếp (không tag)
- **Robots meta**: Select dropdown 3 options thay vì regex validator

## Files gốc sẽ sửa

- `resources/views/components/seo.blade.php` — thêm keywords, robots, RSS autodiscovery link, Organization JSON-LD
- `resources/views/partials/jsonld-localbusiness.blade.php` — **đã tồn tại**, mở rộng dùng `seo.og_image` Setting thay vì hardcode `/logo.png`
- `resources/views/partials/jsonld-breadcrumb.blade.php` — **đã tồn tại**, tái dùng cho blog
- `resources/views/app.blade.php` — đảm bảo inject content data qua `@json`, include Article JSON-LD khi có post
- `app/Http/Controllers/PageController.php` — `buildSeo()` đọc thêm keywords/robots
- `app/Filament/Support/GlobalSettingsSchema.php` — thêm keywords/robots/og_image per page
- `app/Console/Commands/GenerateSitemapCommand.php` — **đã tồn tại**, extend cho Post URLs
- `src/App.tsx` — add routes Blog + BlogPost lazy
- `routes/web.php` — thêm routes `/blog`, `/blog/{slug}`, `/en/blog/*`, RSS feeds
- `postcss.config.js` — thêm `tailwindcss/nesting` plugin (yêu cầu của tiptap editor)
- `composer.json`, `package.json` — dependencies mới

## Files mới

- `app/Models/Post.php`
- `app/Filament/Resources/PostResource.php` (+ Pages: List/Create/Edit)
- `app/Http/Controllers/PostController.php`
- `app/Http/Controllers/BlogFeedController.php`
- `app/Http/Resources/PostApiResource.php` (tên khác `PostResource` Filament để tránh collide)
- `app/Support/SeoBuilder.php` — DRY SEO logic
- `app/Support/HtmlSanitizer.php`
- `app/Observers/PostObserver.php`
- `app/Jobs/RegenerateSitemap.php`
- `app/Policies/PostPolicy.php`
- `database/migrations/2026_04_21_XXXXXX_create_posts_table.php`
- `database/seeders/PostSeeder.php`
- `resources/views/partials/jsonld-article.blade.php`
- `resources/views/partials/jsonld-organization.blade.php`
- `resources/views/feeds/rss.blade.php`
- `src/pages/Blog.tsx`, `src/pages/BlogPost.tsx`
- `src/components/blog/PostCard.tsx`, `src/components/blog/PostContent.tsx`, `src/components/blog/PostMeta.tsx`, `src/components/blog/RelatedPosts.tsx`
- `src/lib/hooks/useAppContent.ts`
- `src/lib/types/post.ts`

## Risks / Open

- **SSR SEO cho `/blog/{slug}` khi slug không tồn tại** → 404 với SEO `robots: noindex`
- **Translatable slug collision** → generated column + UNIQUE INDEX per locale (MySQL 8)
- **HTML từ tiptap có thể chứa `<script>`** → sanitize 2 tầng (mews/purifier BE + DOMPurify FE)
- **Tiptap media URL** → transform về tương đối trong model saving event (regex replace `APP_URL/storage/...` → `/storage/...`)
- **Hydration mismatch date** → preformat string ở BE, FE không re-format
- **Sitemap regen khi unpublish** → observer dispatch cho cả `saved` + `deleted` không điều kiện
- **HtmlSanitizer + tiptap blocks** → integration test với full block output trước khi go live
- **Cache invalidation** → driver file, dùng `Cache::forget` trực tiếp (không tag)

## Resolved từ review round 1

- ✅ `awcodes/filament-tiptap-editor:^3.0` stable với Filament 3.3 (branch 3.x, High reputation)
- ✅ `jsonld-localbusiness.blade.php` + `jsonld-breadcrumb.blade.php` + `GenerateSitemapCommand.php` đã tồn tại → tái dùng, không tạo mới
- ✅ **Phase 01 shipped** — 6 files modified, 4 URLs render meta/og/JSON-LD đầy đủ, code review 8/10, 3 post-review fixes applied
- ✅ **Phase 02 shipped** — 14 files created + 4 modified (18 total), admin CRUD Post với tiptap + draft preview, 6 bugs fixed, review 8+/10

## Resolved questions

- ✅ **Tác giả blog**: chỉ owner → bỏ `author_id` FK + relation, hiển thị "Mamiviet" làm tác giả hard-code (hoặc lấy từ `Setting::footer.company_name`)
- ✅ **`view_count`**: bỏ hoàn toàn
