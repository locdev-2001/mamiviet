# Phase 03 Blog Frontend — SEO-Ready Routes + Architect Trade-Offs Revealed

**Date**: 2026-04-21 22:30  
**Severity**: Medium (kiến trúc issue, không blocking MVP)  
**Component**: React pages (Blog, BlogPost), SEO (hreflang, JSON-LD, `<template>` crawler bug)  
**Status**: Resolved (production-ready, tech debt deferred to Phase 04)

## What Happened

Phase 03 xây dựng blog frontend React hoàn chỉnh cho Mamiviet:
- 4 routes: `/blog`, `/blog/{slug}`, `/en/blog`, `/en/blog/{slug}` + 404
- Backend extract `SeoBuilder` class DRY cho Page + Post (seo_title/description/keywords/og_image + hreflang)
- Frontend React pages (Blog.tsx, BlogPost.tsx) render `window.__APP_CONTENT__` từ Blade server
- JSON-LD: Article + BreadcrumbList + articleBody, DOMPurify sanitize content
- Code review 7.5/10 → 9/10 sau 8 fixes (canonical hreflang, fallback description, JSON_HEX security, `<template>` crawler issue, preload cover)
- User phát hiện 3 UI bugs: navbar missing Blog menu, padding insufficient (title che), SPA nav empty state

**Scope**: 13 files tạo mới, ~13 files modify, tích hợp hoàn chỉnh vào admin GlobalSettings + Blade views.

## The Brutal Truth

Cảm giác hồi hộp nhất lúc reviewer phát hiện `<template id="post-content-html">` **không được crawler đọc** — tôi giả định Google WRS render JS, rồi template content crawl được như normal. Sai hoàn toàn. Template element inert by HTML spec, content không index. Thay bằng `<div hidden aria-hidden>` + move `articleBody` vào JSON-LD `<script>` block — crawler đọc được full plain text. Bài học SEO đau.

Khó khăn lớn hơn: **kiến trúc Blade+React hydration** hiện rõ side effect khi user cross-page navigate. Từ post `/blog/slug-name` click "Blog" link → React Router client-side navigate → BlogList component xài `window.__APP_CONTENT__` với shape `{ post, related }`, không phải `{ posts, pagination }` — empty state. Fix lúc: thêm `reloadDocument` prop vào Header Links. Nhưng đó là **hack**, không elegant. Kiến trúc 2-edged sword: server render SEO tốt (Google happy), nhưng client-side nav phá. Chọn 1 trong 2: full SPA API fetch hoặc MPA thuần. Sẽ deferred refactor sang Phase 04+ nếu scale.

UI bugs user phát hiện đơn giản nhưng blocking: navbar không có "Blog" menu (tôi quên extend NAV_ITEMS constants), blog page title che (navbar desktop 108px tổng `top-bar + nav`, `mt-16` không đủ), SPA nav empty state. Tất cả fixed nhanh, nhưng hơi shaming — tests không bắt được vì manual verify không complete kinh tế mà chỉ verify "happy path" post render.

## Technical Details

**Bug #1: `<template>` content không crawl được**
- Triệu chứng: `curl /blog/slug` xem HTML source → `<template id="post-content-html">post-body-html-here</template>`. Build page content đầy đủ, SEO score OK. Nhưng reviewer phát hiện: Google crawler không index template inert content.
- Root cause: HTML spec định nghĩa `<template>` như inert — content không render, DOM parser không thêm vào document tree, CSS/JS skip. Google WRS có render JS, nhưng template vẫn inert.
- Fix: Đổi `<template>` → `<div hidden aria-hidden>` (reachable by DOM), thêm `articleBody` (plain text, full content) vào JSON-LD Article script tag. Crawler read both: HTML `<div hidden>` + JSON-LD structured data.
- Lesson: **`<template>` inert by spec — don't use cho content crawler needs to read**. Dùng `<div hidden>` hoặc render trực tiếp.

**Bug #2 + #3: Header Links SPA nav break + navbar missing Blog menu + insufficient padding**
- Triệu chứng: Click "Blog" link từ post page → React Router client-side navigate. BlogList xài `__APP_CONTENT__` nhưng server render chỉ cho post route, shape không match (expected `{ posts, pagination }`, got `{ post, related }`) → empty state
- Root cause: Kiến trúc không pure SPA — Blade server per-route render `__APP_CONTENT__` specific data. Cross-page client nav không refresh Blade server → data shape mismatch
- Fix: 
  1. Thêm `reloadDocument={true}` prop cho Header NAV Links (Blog, Home, etc) → force browser full page reload, server render từ đầu
  2. Extend `NAV_ITEMS` constants + add "Blog" vào header / sidebar (forgot initial add)
  3. Blog page padding từ `mt-16` → `pt-24 md:pt-36` (navbar desktop 108px: top info bar ~32px + nav row ~76px)
- Lesson: **Blade+React hybrid không benefit SPA cross-page nav** — nếu route data shape khác, client nav sẽ crash. `reloadDocument` hack; proper solution: full SPA API fetch hoặc pure MPA

**Code Review Fixes** (7 critical + 1 high):
- C1: `/blog?page=2` canonical `rel=canonical` + `noindex, follow` robots (prevent duplicate content index)
- C2: Fallback description từ strip HTML content 160 chars khi excerpt empty
- C3: `og:type=article` cho post, `og:type=website` default (Rich Results metadata)
- C4: JSON_HEX_TAG|AMP escape flags (prevent `</script>` breakout từ user content)
- C5: JSON-LD BreadcrumbList với hreflang x-default chain (de → x-default URL)
- H3: x-default hreflang missing → first locale fallback (en)
- H5: preload cover image với `imagesrcset` + `imagesizes` mobile LCP optimization
- H6: JSON_HEX applied breadcrumb JSON-LD escaping

## What We Tried

**Plan A: React component + JSON-LD script generation**
- Build Blog.tsx (list pagination) + BlogPost.tsx (single post detail)
- Generate Article + BreadcrumbList JSON-LD per route
- Status: ✅ Mostly work, nhưng **template inert crawler issue** phát hiện lúc code review

**Plan B: DOMPurify sanitize**
- Defense-in-depth: server sanitize (Purify) + client re-sanitize (DOMPurify)
- Status: ✅ Work, nhưng **JSON_HEX escape flags** cần sau review

**Plan C: Cross-page navigation**
- Client-side React Router navigate (`navigate('/blog')`)
- Status: ❌ Break (data shape mismatch) → Fix: `reloadDocument` force full page reload
- Status: ✅ Work nhưng kiến trúc hack

**Plan D: Manual UI test**
- Verify all 4 routes render, SEO meta tags present
- Status: ✅ Happy path work, nhưng **missed navbar menu** (incomplete test scenario)

## Root Cause Analysis

1. **`<template>` Assumption**: Giả định Google crawler execute JS hydrate → template content inert không ý thức. **Reality**: template element design spec inert, WRS render JS nhưng không thêm content vào crawlable DOM.

2. **Kiến trúc Blade+React mismatch**: Tưởng site là SPA, nhưng Blade server render route-specific data (`__APP_CONTENT__`). Cross-page client nav không reload Blade → `__APP_CONTENT__` outdated. **Reality**: kiến trúc hybrid, không benefit SPA.

3. **Incomplete UI test**: Manual test happy path (post render, nav work) nhưng miss (navbar menu, padding check, cross-page nav).

4. **Code review dependency**: Bug `<template>` + JSON_HEX không phát hiện qua local test, cần external code review bắt. Reviewer eyes 70% case nhưng không 100%.

## Lessons Learned

1. **`<template>` inert, never crawled**: Nếu content cần crawler index, dùng `<div hidden>` hoặc render trực tiếp. JSON-LD `articleBody` redundant với HTML nhưng structured data ưu tiên, thêm vào để sure.

2. **Blade+React hybrid 2-edged sword**: Server render SEO perfect, nhưng cross-page client nav cần reload Blade → không benefit SPA. **Decision**: accept `reloadDocument` hack cho MVP, hoặc refactor sang full API SPA Phase 04+.

3. **Data shape per-route**: `__APP_CONTENT__` shape khác per route (post vs blog). Client nav cần reload hoặc API fetch fresh data. **Future**: use API endpoint `/api/blog?page=X` instead Blade data.

4. **Defense-in-depth JSON escape**: JSON_HEX_TAG|AMP prevent `</script>` breakout từ user content inject vào window object. **Pattern**: server set flags + client parse safely.

5. **SEO metadata completeness**: articleBody, x-default hreflang, og:type dynamic, canonical + noindex — mỗi cái specific purpose. **Checklist**: full SEO test mỗi content type.

## Next Steps

**Immediate (done)**:
- [x] Replace `<template>` → `<div hidden>` + add articleBody JSON-LD
- [x] Add JSON_HEX_TAG|AMP escape flags server + window.__APP_CONTENT__
- [x] Extend NAV_ITEMS "Blog" menu + update header/sidebar
- [x] Padding blog page pt-24 md:pt-36
- [x] Add `reloadDocument` để Header Links force full page reload
- [x] Verify all 4 routes + 404 SEO-ready (`curl` output full hreflang + JSON-LD)

**Phase 04 (deferred tech debt)**:
- [ ] Sitemap extend Post URLs + hreflang alternate links
- [ ] RSS feed `/blog/feed.xml` de/en
- [ ] Organization JSON-LD site-wide
- [ ] PostObserver regenerate sitemap + Cache::forget RSS khi post save
- [ ] **Optional refactor**: Full SPA API fetch (replace reloadDocument hack), keep Blade SPA fallback

**Documentation**:
- [ ] Add `docs/code-standards.md`: SEO checklist per content type (meta, JSON-LD, hreflang)
- [ ] Add `docs/code-standards.md`: Template vs div.hidden crawlability rule

---

## Emotional Reflection

Frustration moment: Giả định `<template>` crawl được, rồi reviewer catch lỗi. Cảm giác "bây giờ mới biết" kiểu. Nhưng satisfying khi hiểu lý do: HTML spec inert, không magic.

Hơi frustrated với kiến trúc Blade+React — không pure SPA nhưng cũng không pure MPA. `reloadDocument` hack xấu, nhưng giải quyết problem ngay. Sẽ refactor proper Phase 04+ nếu có time.

Positive: moment khi verify tất cả routes + 404, `curl` output show đầy đủ 3 JSON-LD scripts, articleBody plain text, og:type=article, x-default hreflang correct. SEO-ready production. Yên tâm.

**Takeaway**: Mỗi lần code review external pair eyes bắt 30-50% issues tôi miss. `<template>` inert, JSON escape flags, x-default fallback — tất cả non-obvious nếu không focus SEO/security. Sẽ activate reviewer task sau mỗi feature implementation future — không wait gom cuối.
