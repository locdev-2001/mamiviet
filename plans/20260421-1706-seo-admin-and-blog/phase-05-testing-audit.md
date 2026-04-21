# Phase 05 — Testing & SEO audit

## Context Links

- [plan.md](plan.md)
- [phase-01](phase-01-seo-admin-homepage.md), [phase-02](phase-02-blog-backend.md), [phase-03](phase-03-blog-frontend.md), [phase-04](phase-04-seo-enhancements.md)

## Overview

- **Priority**: Trung bình (không chặn launch, nhưng tránh regression sau này)
- **Status**: Pending
- **Depends on**: Phase 01-04 xong
- Viết test coverage các điểm quan trọng + chạy audit SEO/Performance/A11y trước khi ship.

## Key Insights

- Feature cốt lõi: SEO admin (Phase 01) + Blog CRUD (Phase 02) + Blog render (Phase 03) + Sitemap/RSS (Phase 04)
- Rủi ro regression cao nhất: `HtmlSanitizer` (lọc nhầm content hợp lệ), slug uniqueness (DB constraint), SEO meta (render sai silent fail)
- Framework: PHPUnit 10 (đã có, `phpunit.xml` từ Laravel default), Vitest cho FE (chưa có — cần cài nếu muốn unit test JS)
- Tool SEO audit: Lighthouse CI, Google Rich Results Test, W3C feed validator, xmllint

## Requirements

**Functional**
- Feature tests: PostController index/show/404/preview, sitemap generate, RSS feed render
- Unit tests: HtmlSanitizer (tất cả tiptap blocks), SeoBuilder (all paths), slug validator
- Integration tests: Admin flow — create post → publish → visible frontend
- Audit: Lighthouse SEO score = 100 mobile, Performance > 85, A11y > 95
- Rich Results Test: LocalBusiness, Article, BreadcrumbList, Organization — 0 errors

**Non-functional**
- Test run toàn suite < 30s (cho dev loop nhanh)
- CI ready: có thể plug vào GitHub Actions sau

## Architecture

```
tests/
├── Feature/
│   ├── Seo/
│   │   ├── HomepageSeoTest.php          — meta tags render
│   │   ├── SeoAdminTest.php             — GlobalSettings save → reflect frontend
│   │   └── RobotsNoIndexTest.php        — 404 + preview header
│   ├── Blog/
│   │   ├── BlogIndexTest.php            — list paginate, locale filter
│   │   ├── BlogShowTest.php             — show post, 404 slug
│   │   ├── BlogPreviewTest.php          — signed URL expired/valid
│   │   └── BlogHreflangTest.php         — fallback khi thiếu locale
│   ├── Sitemap/
│   │   ├── SitemapGenerateTest.php      — includes posts + hreflang
│   │   └── SitemapObserverTest.php      — post save → job dispatched
│   └── Feed/
│       └── RssFeedTest.php              — format, cache invalidate
├── Unit/
│   ├── HtmlSanitizerTest.php            — each tiptap block preserved
│   ├── SeoBuilderTest.php               — forPost, forPage, notFound, fallback chain
│   ├── PostSlugValidatorTest.php        — unique per locale
│   └── PostModelTest.php                — scopes, reading_time, URL normalize
```

## Related Code Files

**Create:**
- `tests/Feature/Seo/*`
- `tests/Feature/Blog/*`
- `tests/Feature/Sitemap/*`
- `tests/Feature/Feed/*`
- `tests/Unit/*`
- `tests/Fixtures/tiptap-output-full.html` — fixture HTML với all blocks
- `.github/workflows/test.yml` (optional, chuẩn bị cho CI)

**Modify:**
- `phpunit.xml` — add testsuite groups nếu cần
- `composer.json` — `phpunit/phpunit` đã có (verify ver >= 10)

## Implementation Steps

### 1. Test setup

```bash
php artisan test --parallel  # verify current suite pass
```

Thêm trait `RefreshDatabase` + factory cho `Post`:
```bash
php artisan make:factory PostFactory --model=Post
```

`PostFactory` generate bài translatable (title de + en random), slug random, status random.

### 2. HtmlSanitizer unit test (critical)

Fixture `tests/Fixtures/tiptap-output-full.html`:
- Heading h2/h3/h4
- Bold/italic/underline/strike
- Link (external + internal)
- Bullet + ordered list
- Blockquote
- Image (`<img src="/storage/..." alt="...">`)
- YouTube iframe
- Vimeo iframe
- Table (thead/tbody/tr/td/th)
- Code block
- Horizontal rule

Test:
- `HtmlSanitizer::clean($fixture)` KHÔNG bị rỗng
- Các block trên vẫn present (assertStringContainsString cho từng tag)
- `<script>alert(1)</script>` BỊ strip
- `<iframe src="https://evil.com">` BỊ strip
- `<a href="javascript:...">` BỊ strip
- `onclick="..."` attr BỊ strip

### 3. SeoBuilder unit test

- `forPage('home', 'de')` trả về đủ keys (title, description, keywords, robots, og_image, canonical, hreflang)
- `forPage('home', 'en')` — hreflang de+en đúng
- `forPost($post, 'de')` — keywords lấy từ seo_keywords, fallback title khi seo_title trống
- `forPost` — hreflang fallback khi post chỉ có de (bỏ en alternate)
- `notFound('de')` — robots=noindex
- OG image fallback chain: post.og_image → cover hero → site default → /logo.png

### 4. Post model unit test

- Factory create → `scopePublished()` filter đúng
- `scopeForLocale('en')` loại post không có slug en
- Saving event: content sanitized (assert `<script>` removed)
- Saving event: tiptap URL normalized (assert no `app.url` prefix)
- `reading_time` auto-computed khi content thay đổi (mock 400 words → 2 phút)

### 5. Blog feature tests

**`BlogIndexTest`**:
- `/blog` returns 200, render meta title/description đúng
- 13 posts → paginated 12 per page, trang 2 hiển thị bài thứ 13
- Locale en: `/en/blog` chỉ hiển thị post có slug en
- Published filter: draft post không xuất hiện

**`BlogShowTest`**:
- `/blog/{valid-slug}` returns 200, JSON-LD Article present, `<template id="post-content-html">` chứa content
- `/blog/nonexistent` returns 404, robots=noindex meta, render React NotFound
- OG image meta absolute URL (prefix APP_URL)
- Canonical đúng với locale

**`BlogPreviewTest`**:
- Signed URL valid → render draft post với `X-Robots-Tag: noindex, nofollow`
- Expired URL → 403
- Unsigned URL → 403

**`BlogHreflangTest`**:
- Post có cả de+en → `<link rel="alternate" hreflang="en" href="/en/blog/{en-slug}">` present
- Post chỉ có de → không render alternate en, x-default trỏ blog list

### 6. SEO admin feature tests

**`SeoAdminTest`** (dùng Filament test helpers):
- Admin save keywords cho home → reload `/` thấy `<meta name="keywords">` đúng
- Admin save robots=noindex cho bilder → `/bilder` có `<meta name="robots" content="noindex, follow">`
- Admin save og_image per page → `/bilder` `<meta property="og:image">` khác `/`

### 7. Sitemap + RSS tests

**`SitemapGenerateTest`**:
- Run command `sitemap:generate` → file `public/sitemap.xml` tồn tại
- XML valid (parse ok, không exception)
- Contains `<loc>` cho tất cả published posts
- Contains `<xhtml:link>` hreflang alternate
- Draft/scheduled posts KHÔNG trong sitemap

**`SitemapObserverTest`**:
- `Queue::fake()` → create published post → assert `RegenerateSitemap` dispatched
- Update draft → published → assert dispatched
- Soft delete → assert dispatched

**`RssFeedTest`**:
- `/blog/feed.xml` returns 200, Content-Type RSS
- Contains 20 latest published posts (de)
- `/en/blog/feed.xml` chỉ post có slug en
- Update post → Cache::forget gọi → next request re-render

### 8. SEO audit (manual nhưng document cụ thể)

**Lighthouse CI (local):**
```bash
npm install -g @lhci/cli
lhci autorun --collect.url=http://mamiviet.test/ --collect.url=http://mamiviet.test/bilder --collect.url=http://mamiviet.test/blog --collect.url=http://mamiviet.test/blog/sample-slug
```

Assertion:
- SEO >= 100
- Performance >= 85 (mobile)
- Accessibility >= 95
- Best Practices >= 95

**Rich Results Test** (online):
- https://search.google.com/test/rich-results
- Test mỗi URL: /, /bilder, /blog, /blog/{slug}
- Screenshot pass/fail lưu vào `plans/reports/phase-05-rich-results-YYYYMMDD.md`

**Structured data manual check:**
- LocalBusiness: /
- Article + BreadcrumbList: /blog/{slug}
- Organization: site-wide
- WebSite: site-wide (đã có)

**Sitemap/Feed validation:**
```bash
xmllint --noout public/sitemap.xml
curl -s http://mamiviet.test/blog/feed.xml | xmllint --noout -
# https://validator.w3.org/feed/ paste URL
```

**hreflang audit:**
- https://technicalseo.com/tools/hreflang/
- Test URL list từ sitemap

### 9. A11y spot-check

- Semantic: `<article>`, `<time datetime>`, `<nav>` breadcrumb
- Alt text: verify cover image alt không rỗng
- Focus visible: tab qua list post
- Keyboard nav: enter open post detail
- Screen reader: headings hierarchy h1 (title) → h2 (post sections)

### 10. Báo cáo tổng hợp

Tạo `plans/reports/phase-05-seo-audit-YYYYMMDD.md`:
- Lighthouse scores từng URL
- Rich Results Test screenshots
- Validator outputs
- A11y issues (nếu có)
- Priority fix list nếu score chưa đạt

## Todo List

- [ ] `PostFactory` với translatable data
- [ ] Fixture `tiptap-output-full.html`
- [ ] `HtmlSanitizerTest` — assert all blocks preserved + XSS stripped
- [ ] `SeoBuilderTest` — cover forPage/forPost/notFound/fallback
- [ ] `PostModelTest` — scopes + saving events
- [ ] `PostSlugValidatorTest` — unique per locale
- [ ] `BlogIndexTest`, `BlogShowTest`, `BlogPreviewTest`, `BlogHreflangTest`
- [ ] `SeoAdminTest` (Filament integration)
- [ ] `SitemapGenerateTest`, `SitemapObserverTest`
- [ ] `RssFeedTest`
- [ ] Run full suite: `php artisan test --parallel` < 30s
- [ ] Lighthouse CI local run 4 URLs
- [ ] Rich Results Test 4 URLs + screenshots
- [ ] xmllint + W3C feed validator
- [ ] A11y spot-check
- [ ] Viết `phase-05-seo-audit-{date}.md` report

## Success Criteria

- Test suite pass 100%, coverage phần logic chính > 70%
- Lighthouse: SEO 100, Performance ≥ 85 mobile, A11y ≥ 95
- Rich Results Test: 0 errors cho tất cả schema
- Sitemap XML valid, RSS feed valid
- Không hreflang warning trên technicalseo.com
- Report audit tổng hợp committed trong `plans/reports/`

## Risk Assessment

| Risk | Mitigation |
|------|-----------|
| Test Filament cần auth setup phức tạp | Dùng `actingAs($admin)` helper, fixture user admin trong setUp |
| Lighthouse mobile < 85 do LCP cover image | Phase 03 đã có srcset + fetchpriority; fine-tune thêm ở phase này nếu cần |
| Rich Results Test cần URL public | Dùng ngrok cho staging hoặc deploy preview trước test |
| Fixture tiptap HTML lỗi thời khi tiptap upgrade | Document version, re-fixture khi upgrade |
| Flaky test do cache file driver stale | `Cache::flush()` trong `setUp` của feature test |

## Security Considerations

- Test không expose API key hoặc credential
- Fixture không chứa sensitive data
- Preview URL test với fake user, không thật

## Next Steps

- Nếu audit pass: ready to ship
- Nếu fail: priority list fix rồi re-audit
- Post-launch: theo dõi Google Search Console coverage 2 tuần
- Consider CI: GitHub Actions run test + Lighthouse trên mỗi PR
