---
title: "Mamiviet CMS đa ngôn ngữ + SEO + Filament admin"
description: "Migrate SPA → Inertia v2 + SSR, Filament 3 admin DE/EN, SEO chuẩn full schema"
status: pending
priority: P1
effort: ~40h
branch: main
tags: [cms, i18n, seo, filament, inertia, ssr]
created: 2026-04-20
blockedBy: []
blocks: []
---

## Mục tiêu

- Toàn bộ content (text, ảnh) edit qua `/admin` Filament
- DE primary (`/`) + EN (`/en/*`)
- Lighthouse SEO 100, Perf ≥90, CWV pass
- Schema.org Restaurant + LocalBusiness Rich Results
- Giữ Instagram cron 6h + manual scrape button

## Stack đã chốt

- Inertia.js v2 + React 18 + SSR sidecar
- Filament 3 panel `/admin`
- spatie/laravel-translatable + filament-spatie-laravel-translatable-plugin
- Intervention Image v3 (FileUpload hook → WebP + 480/768/1280/1920)
- spatie/laravel-sitemap
- Domain: https://restaurant-mamiviet.com

## Phases

| # | File | Status | Effort | BlockedBy |
|---|------|--------|--------|-----------|
| 01 | [phase-01-install-packages.md](phase-01-install-packages.md) | pending | 1h | — |
| 02 | [phase-02-db-schema.md](phase-02-db-schema.md) | pending | 2h | 01 |
| 03 | [phase-03-seed-content.md](phase-03-seed-content.md) | pending | 2h | 02 |
| 04 | [phase-04-locale-routing.md](phase-04-locale-routing.md) | pending | 2h | 02 |
| 05 | [phase-05-migrate-pages-to-inertia.md](phase-05-migrate-pages-to-inertia.md) | pending | 6h | 01,04 |
| 06 | [phase-06-seo-layer.md](phase-06-seo-layer.md) | pending | 4h | 05 |
| 07 | [phase-07-filament-resources.md](phase-07-filament-resources.md) | pending | 6h | 02,03 |
| 08 | [phase-08-media-pipeline.md](phase-08-media-pipeline.md) | pending | 3h | 07 |
| 09 | [phase-09-cron-scrape-button.md](phase-09-cron-scrape-button.md) | pending | 2h | 07 |
| 10 | [phase-10-performance-pass.md](phase-10-performance-pass.md) | pending | 4h | 06,08 |
| 11 | [phase-11-production-checklist.md](phase-11-production-checklist.md) | pending | 3h | 10 |

## Dependency graph

```
01 → 02 → 03 ─┐
       │     ├→ 07 → 08 ─┐
       │     │       │   │
       │     └→ 09 ──┤   │
       └→ 04 → 05 → 06 ──┴→ 10 → 11
```

## Workflow rule (BẮT BUỘC per phase)

Sau MỖI phase:
1. `/ck:code-review` → fix issues
2. `/simplify` → đảm bảo DRY/KISS
3. Manual smoke test
4. Update phase status `completed` → mới chuyển phase tiếp

## Reports & decisions tham chiếu

- **[decisions.md](decisions.md)** — single source of truth cho mọi giá trị chốt (IG handle `mami.viet`, Linux VPS, NAP placeholder, schema settings hiện tại...). Phases ưu tiên đọc file này trước.
- `plans/reports/brainstorm-20260420-1235-cms-i18n-seo-filament.md` — design decisions
- `plans/reports/researcher-20260420-inertia-ssr-setup.md` — Inertia v2 + SSR
- `plans/reports/researcher-20260420-filament-i18n-media.md` — Filament + media + cron

## Out of scope

Menu/dish CRUD, reservation, payment, multi-user roles, spin wheel, coupons.

## Success criteria toàn dự án

- Admin edit tất cả text/image DE+EN không cần dev
- Lighthouse SEO 100, Perf ≥90, LCP <2.5s, INP <200ms, CLS <0.1
- Google Rich Results Test pass cho LocalBusiness + Restaurant
- Sitemap được Google index trong 7 ngày
- SSR sidecar chạy ổn định prod via supervisord
