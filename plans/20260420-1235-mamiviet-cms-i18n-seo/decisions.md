---
name: Confirmed decisions
description: Single source of truth cho mọi quyết định đã chốt — phases reference file này
---

# Decisions chốt

| Item | Value | Source |
|---|---|---|
| Domain prod | `https://restaurant-mamiviet.com` | Brainstorm 2026-04-20 |
| Locale primary | `de` (URL `/`) | Brainstorm |
| Locale secondary | `en` (URL `/en/...`) | Brainstorm |
| Stack render | Inertia.js v2 + React 18 + SSR Node sidecar | Brainstorm |
| Admin panel | Filament 3 tại `/admin` | Brainstorm |
| Translation storage | spatie/laravel-translatable (JSON cols) | Brainstorm |
| Media pipeline | Filament FileUpload + Intervention Image v3 → WebP + 480/768/1280/1920 | Brainstorm |
| Sitemap | spatie/laravel-sitemap multi-locale với hreflang | Brainstorm |
| Section types | hero, intro, featured_dishes, gallery_teaser, story, contact_cta | Brainstorm |
| Models existing | `User`, `InstagramPost`, `Setting` | Cleanup phase trước |
| Job existing | `app/Jobs/ScrapeInstagramPostsJob.php` | Cleanup phase trước |
| Command existing | `php artisan instagram:scrape [--async]` | Cleanup phase trước |
| **Instagram URL** | `https://www.instagram.com/mami.viet/` (handle: `mami.viet`) | User 2026-04-20 |
| **Production OS** | Linux VPS | User 2026-04-20 |
| Process manager prod | `supervisord` cho SSR sidecar | Linux confirmed → Phase 11 |
| Cron prod | crontab → `* * * * * cd /path && php artisan schedule:run` | Linux confirmed → Phase 09 |
| NAP data | Placeholder ban đầu, user điền qua admin Settings sau | Brainstorm |
| Settings table schema hiện tại | `id, group, key, value (text), unique(group,key), timestamps` | Cleanup phase trước |
| Quality loop | Sau MỖI phase chạy `/ck:code-review` → `/simplify` | Memory `workflow_phase_completion.md` |

## Out of scope (lần này)
- Menu food management (món ăn detail, giá, mô tả)
- Reservation/booking
- Online order/payment (đã có nút GloriaFood, ngoài CMS)
- Multi-user roles, permissions
- Coupons, spin wheel, cart
- Email marketing/newsletter

## Schema impact của 2 quyết định mới

### IG handle `mami.viet`
- `phase-03-seed-content.md`: seed `settings` row `group=social, key=instagram_url, value=https://www.instagram.com/mami.viet/` + `key=instagram_handle, value=mami.viet`
- `phase-06-seo-layer.md`: JSON-LD `LocalBusiness.sameAs` includes IG URL
- `phase-09-cron-scrape-button.md`: confirm scrape job đang đọc handle từ config/settings (kiểm tra `app/Services/Admin/InstagramScraperService.php` — nếu hardcode thì refactor đọc từ settings table)
- `phase-07-filament-resources.md`: SettingsPage có field `social.instagram_url`

### Linux VPS
- `phase-09-cron-scrape-button.md`: bỏ section Windows Task Scheduler, chỉ giữ crontab example
- `phase-11-production-checklist.md`: dùng supervisord config (đã có sẵn trong researcher report Inertia), bỏ NSSM/PM2 alternative
