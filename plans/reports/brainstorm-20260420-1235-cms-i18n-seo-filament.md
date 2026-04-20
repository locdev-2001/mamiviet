# Brainstorm: CMS đa ngôn ngữ + SEO chuẩn + Filament admin

**Ngày:** 2026-04-20
**Domain:** https://restaurant-mamiviet.com
**Scope:** Mamiviet landing page + /bilder Instagram feed (Leipzig DE)

## Mục tiêu
1. Toàn bộ content (text, ảnh, logo) edit được qua admin
2. 2 ngôn ngữ: DE primary + EN
3. Website chuẩn SEO hoàn toàn → top tìm kiếm + brand recognition
4. Giữ tính năng scrape Instagram (cron + manual button)

## Quyết định kiến trúc

| Hạng mục | Quyết định |
|---|---|
| Stack | Inertia.js v2 + React 18 + SSR sidecar |
| Admin | Filament 3 (panel `/admin`) |
| Content model | Section blocks có schema (page → sections theo type) |
| i18n storage | spatie/laravel-translatable (JSON column) |
| Translation UI | filament-spatie-laravel-translatable-plugin (tab DE/EN) |
| Media | Filament FileUpload + Intervention Image (auto WebP + responsive sizes) |
| URL i18n | `/` = DE primary, `/en/...` = EN. Middleware SetLocale parse prefix |
| SEO | Blade `<x-seo>`, JSON-LD (Restaurant + LocalBusiness + WebSite + BreadcrumbList), spatie/laravel-sitemap, hreflang |
| Instagram | Cron mỗi 6h + button "Scrape now" trong Filament Resource |
| NAP data | Placeholder ban đầu, user nhập qua admin Settings sau |

## Data model

```
settings (mở rộng): site identity, NAP, hours JSON, geo, social, cuisine, price_range
pages: id, slug, status, seo (JSON translatable: title, description, og_image_path)
sections: id, page_id, type, order, data (JSON translatable), image_path
instagram_posts (đã có)
users (đã có) — Filament admin auth
```

Section types: `hero | intro | featured_dishes | gallery_teaser | story | contact_cta`

## Stack rationale

- **Vì sao Inertia thay vì giữ SPA**: Google index tốt hơn nhiều, no double-codebase, share locale + auth + flash messages tự nhiên với Laravel. Giữ được toàn bộ React components shadcn/ui hiện có.
- **Vì sao Filament thay vì admin tự build**: scope cho phép, Filament Resources sinh CRUD + form + table + auth chỉ vài chục dòng. Plugin translatable + SEO sẵn.
- **Vì sao spatie/laravel-translatable thay vì translations table**: ít table, query đơn giản, Filament plugin native.

## Out of scope
- Menu management UI (món ăn detail)
- Reservation/order/payment (đã loại từ cleanup trước)
- Multi-user roles (chỉ 1 admin)

## Workflow phases (chi tiết → /ck:plan --hard)
1. Install Filament + Inertia + spatie packages + Intervention
2. DB schema (pages, sections; mở rộng settings)
3. Seed initial content từ `src/lib/locales/{de,en}.json`
4. Locale middleware + URL routing (/en prefix)
5. Migrate Index + Bilder từ React Router → Inertia pages
6. SEO layer (x-seo, JSON-LD, sitemap, robots, hreflang)
7. Filament resources (Settings, Page, Section, InstagramPost)
8. Media pipeline (FileUpload → Intervention WebP + responsive)
9. Cron schedule + manual scrape button
10. Performance pass (Lighthouse, CWV)
11. Production checklist (Google Search Console, Rich Results Test, sitemap submit)

## Quality loop (per phase)
Sau MỖI phase chạy `/ck:code-review` → `/simplify` trước khi sang phase tiếp.

## Rủi ro
- SSR sidecar Node cần process manager prod (supervisord/pm2)
- Translatable + Inertia phải share đúng locale shape, dễ leak DE sang EN
- Image responsive resize chậm → queue job
- NAP placeholder schema cần warning trong admin để user nhớ điền

## Success metrics
- Lighthouse SEO 100, Performance ≥ 90
- Core Web Vitals: LCP <2.5s, INP <200ms, CLS <0.1
- Google Rich Results Test pass cho LocalBusiness + Restaurant
- Sitemap được Google index trong 7 ngày
- Admin có thể edit toàn bộ text/image cả DE + EN không cần dev
