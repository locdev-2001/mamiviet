---
title: "Redesign homepage editor: section-focused UX + Spatie Media Library"
description: "Replace generic type-based Section UX với fixed-key editor, thay Intervention Image bằng spatie/laravel-medialibrary với auto-conversions"
status: completed
priority: P1
effort: ~8h
branch: main
created: 2026-04-20
completed: 2026-04-20
supersedes:
  - plans/20260420-1235-mamiviet-cms-i18n-seo/phase-07-filament-resources.md (SectionsRelationManager)
  - plans/20260420-1235-mamiviet-cms-i18n-seo/phase-08-media-pipeline.md (entire ImageTransformationService)
---

## Mục tiêu

1. **UX admin rõ ràng**: mỗi section homepage = 1 tab với đúng field cần thiết; không còn dropdown `type` + conditional visibility.
2. **Section ẩn được**: toggle `enabled` — ẩn khỏi UI và form editor chỉ hiện section đang bật.
3. **Gallery nhiều ảnh**: section `gallery_slider` chấp nhận N ảnh, reorderable.
4. **Ảnh SEO-optimized**: `spatie/laravel-medialibrary` + `image-optimizer` tự sinh 4 responsive WebP variants (480/768/1280/1920) + `thumb` admin preview. Frontend render `<picture>` với `srcset`/`sizes` đúng breakpoint, `width`/`height` attrs chống CLS, alt text editable DE+EN, LCP preload cho hero.
5. **Content thực sự thay đổi UI**: inject data từ DB vào React qua `window.__APP_CONTENT__` (không refactor sang Inertia, tránh blast radius lớn).

## Section catalog (fixed keys, khớp 1-1 với `src/pages/Index.tsx`)

| key | UI block | Fields | Media |
|---|---|---|---|
| `hero` | Hero banner top | title | bg_image (1) |
| `welcome` | Cuisine Welcome 1 | brand_name, tagline, cuisine_label, title, body, cta_label | image_main, image_overlay |
| `welcome_second` | Cuisine Welcome 2 | cuisine_label, title, body, cta_label | image_main, image_overlay |
| `order` | Order horizontal | title, takeaway, delivery, reservation, free_delivery, cta_label | image_left, image_right |
| `reservation` | Reservation | title, subtitle, note, cta_label, overlay_text | image (1) |
| `contact` | Contact | title, restaurant_name, address, phone, email, instagram_url, instagram_label, map_embed, overlay_text | image (1) |
| `gallery_slider` | Swiper ảnh | title, subtitle | **images collection (N)** |
| `intro` | Intro bottom | title, text1, text2 | — |

Translatable: tất cả field text (DE+EN). Non-translatable: media, enabled, order.

## Phases

| # | File | Status | Effort | BlockedBy |
|---|------|--------|--------|-----------|
| 01 | [phase-01-install-media-library.md](phase-01-install-media-library.md) | ✅ completed | 0.5h | — |
| 02 | [phase-02-redesign-schema-and-seed.md](phase-02-redesign-schema-and-seed.md) | ✅ completed | 1.5h | 01 |
| 03 | [phase-03-filament-homepage-editor.md](phase-03-filament-homepage-editor.md) | ✅ completed | 3h | 02 |
| 04 | [phase-04-hydrate-content-to-react.md](phase-04-hydrate-content-to-react.md) | ✅ completed | 2h | 02 |
| 05 | [phase-05-migrate-react-sections.md](phase-05-migrate-react-sections.md) | ✅ completed | 1h | 04 |

## Fixes từ code-reviewer (2026-04-20)

Sau khi implement 5 phase, delegate `code-reviewer` review tổng thể. Đã apply:

1. ✅ Bỏ `'content' => 'array'` trong `Section::$casts` — xung đột với `HasTranslations` (trait quản lý JSON encoding).
2. ✅ Simplify `EditHomepageSection::mutateFormDataBeforeSave` — build payload thuần, không mutate `$record` tại chỗ.
3. ✅ `HomepageContentResource::stripEmpty()` — server strip `''`/`null` để FE fallback i18n hoạt động đúng.
4. ✅ Frontend đổi `??` → `||` trong mọi fallback i18n (`""` giờ trigger fallback).
5. ✅ Validate `data.map_embed` bằng regex `^https://www\.google\.com/maps/embed\?` + `iframe sandbox="allow-scripts allow-same-origin allow-popups"`.
6. ✅ `instagram_url` rule `url`.
7. ✅ `PageController` log warning khi homepage không có DB data.
8. ✅ Gallery slider: min 5 ảnh DB, ít hơn → fallback static 9 ảnh curated.
9. ✅ Alt fallback dùng `section.content.title` ở locale hiện tại khi admin chưa nhập alt.
10. ✅ `Section::isMultiCollection()` — bỏ hardcoded `'images'` check trong Resource.

## Known follow-ups (out of this plan)

- **Alt-text editor per-image**: SpatieMediaLibraryFileUpload không hỗ trợ native per-file custom_properties editing. Cần custom Filament field để admin nhập alt DE+EN cho từng ảnh. V1 dùng section title fallback — đủ SEO cơ bản.
- **Conversions queue**: `nonQueued()` block ~6-15s trên ảnh 4000×3000 JPEG. Cân nhắc Horizon khi volume cao.
- **Cloud disk**: `MediaDimensionObserver::getPath()` chỉ work với local disk. Nếu đổi sang S3 cần refactor sang stream-based dimension detection.

## Workflow per phase (BẮT BUỘC, per user memory)

1. Implement phase
2. `/ck:code-review` → fix issues
3. `/simplify` → DRY/KISS pass
4. Manual smoke test
5. Update status `completed` → phase tiếp

## Out of scope

- Bilder page (chỉ Instagram feed, không sửa)
- Header/Footer (không dynamic)
- SEO fields (giữ nguyên trên `pages.seo`, không đổi)
- Settings page (không liên quan section UX)
