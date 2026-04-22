# Handover — Mamiviet Restaurant

Production website cho **Mami Viet — Sushi Asian Cuisine** (Leipzig, Đức).

- **Domain**: https://restaurant-mamiviet.com
- **Admin**: https://restaurant-mamiviet.com/admin
- **Tech stack**: Laravel 10 + Filament 3.3 + React 18 + MySQL + Nginx (aaPanel)
- **Server**: VPS Ubuntu, IP `103.166.182.185`
- **Repo**: https://github.com/locdev-2001/mamiviet

---

## 1. Credentials cần chuyển cho client

| Service | Current owner | Action |
|---|---|---|
| Domain registrar | — | Client giữ hoặc transfer theo thoả thuận |
| VPS provider | Dev | Giữ quyền SSH + billing tới khi client setup infra riêng |
| aaPanel admin | Dev | Tạo user riêng cho client hoặc giữ dev làm sysadmin |
| Filament admin user | Dev | Đổi password + tạo thêm user cho client |
| MySQL DB password | Dev | Lưu trong `.env` server, client không cần touch |
| GitHub repo | `locdev-2001` | Invite client as collaborator hoặc transfer ownership |
| Google Search Console | Dev Gmail | **Add client email as Owner** (xem mục 2) |
| Google Analytics 4 | Dev Gmail | **Add client email as Administrator** (xem mục 3) |
| Google Business Profile | Dev / Client | **Add client as Primary Owner** (xem mục 4) |
| Apify Instagram scraper | Dev token | Tạo token riêng cho client account, paste admin |

---

## 2. Transfer Google Search Console

1. Login https://search.google.com/search-console với Gmail đã verify `restaurant-mamiviet.com`
2. Click property → **Settings** (gear icon sidebar) → **Users and permissions**
3. Click **Add user**
4. Nhập email client → **Permission: Owner** → Add
5. Client sẽ nhận email → accept → có full access
6. (Optional) Sau khi client confirm access → remove dev email hoặc giảm xuống "User" role

**Lưu ý**: Verification token (`seo.google_site_verification` trong admin) persist trong DB, KHÔNG cần re-verify khi đổi owner. Nếu client muốn verify method khác (ví dụ DNS), có thể thêm — không xung đột.

---

## 3. Transfer Google Analytics 4

1. Login https://analytics.google.com với Gmail dev
2. **Admin** (gear icon) → Account **Property Access Management** (cột Property)
3. Click **+** → **Add users**
4. Nhập email client → chọn roles:
   - ☑ **Administrator** (quản lý users + config)
   - ☑ **Editor** (edit data streams + events)
5. Click **Add**

**Measurement ID** (dạng `G-XXXXXXXXXX`) vẫn giữ nguyên trong admin Tracking tab — không cần đổi khi transfer owner.

---

## 4. Transfer Google Business Profile

1. Login https://business.google.com với Gmail dev (nếu dev đang own)
2. Chọn business "Mami Viet" → **Settings** → **People and access**
3. Click **Add** → nhập email client → chọn **Primary owner**
4. Client accept invite → full control

**Place ID** trong admin Tracking tab không thay đổi theo owner — giữ nguyên.

---

## 5. Admin workflow guide (cho client)

### Login
- https://restaurant-mamiviet.com/admin
- Email + password dev cung cấp — đổi password ngay sau nhận

### Viết bài blog mới
1. Sidebar → **Posts** → **New post**
2. Tab **Content**:
   - Title + Slug (auto-gen từ title)
   - Excerpt: tóm tắt 1-2 câu (hiển thị trong blog card + OG share)
   - Content: dùng tiptap editor — paste từ Word được (auto convert sang HTML)
3. Tab **SEO**:
   - SEO title: để trống → dùng title chính. Chỉnh nếu muốn khác
   - SEO description: 60-160 chars cho Google (counter hiển thị)
   - SEO keywords: comma-separated, <255 chars
   - Open Graph image: upload ảnh 1200×630 cho social share đẹp
4. Tab **Publishing**:
   - Status: Draft (privacy, có preview link) / Published (live) / Scheduled (auto-publish future)
   - Published at: để trống → publish ngay
   - Cover image: bắt buộc, 1200×630 (ảnh hero đẹp nhất của post)
5. Click **Save** → bài live trên `/blog/{slug}`

### Quản lý SEO trang chính
- Sidebar → **Global Settings** → Tab **SEO**
- Chỉnh title/description/keywords/OG image cho:
  - **Home** (`/`)
  - **Bilder** (`/bilder`)
- Mỗi field có 2 tab **DE** / **EN** (click tab chuyển locale)
- Click **Save** → reload site trong incognito browser để verify

### Quản lý tracking
- Sidebar → **Global Settings** → Tab **Tracking**
- 4 fields: GA4 ID, GTM container, GBP Place ID, FB Pixel
- Paste ID → **Save** → effective ngay (không cần restart)

### Dịch sang tiếng Anh
- Mọi field có 🌐 icon (title, content, excerpt, SEO...) đều translatable
- Mở bài/setting → click icon **🌐 DE/EN** ở header → chuyển tab → nhập EN → Save
- Nếu chưa dịch: site tự fallback sang DE, không vỡ

### Instagram posts sync
- Sidebar → **Instagram Posts** → Click **Sync now** (button top-right)
- Hoặc auto-sync qua cron (background job mỗi ngày)

---

## 6. Hosting & maintenance

### SSH vào server
```bash
ssh root@103.166.182.185
cd /www/wwwroot/restaurant-mamiviet.com
```

### Deploy update mới
Sau khi dev push code lên `main`:
```bash
cd /www/wwwroot/restaurant-mamiviet.com
./deploy.sh
```

Script tự: pull → composer install → build frontend → migrate → cache → sitemap regen → queue restart → smoke test.

### Monitor logs
```bash
# Laravel errors
tail -50 /www/wwwroot/restaurant-mamiviet.com/storage/logs/laravel.log

# Nginx errors
tail -50 /www/wwwlogs/restaurant-mamiviet.com.error.log

# Queue worker
tail -50 /www/wwwroot/restaurant-mamiviet.com/storage/logs/queue-default.log
tail -50 /www/wwwroot/restaurant-mamiviet.com/storage/logs/queue-instagram.log
```

### Backup lịch
aaPanel tự backup daily 03:00 (DB), weekly Chủ nhật 04:00 (files). Giữ 7/4 versions.

### SSL renewal
Let's Encrypt tự renew 60 ngày trước expire. Nếu fail:
```bash
certbot renew --dry-run
```

---

## 7. Troubleshooting quick reference

| Triệu chứng | Fix nhanh |
|---|---|
| Site 500 | `tail -100 storage/logs/laravel.log` |
| Site 404 admin | Check nginx root trỏ `/public` + rewrite file |
| Blog bài mới không hiện sau publish | `php artisan cache:clear && php artisan view:clear` |
| Image upload fail | `chown -R www:www storage/app/public public/storage` |
| Sitemap stale | `php artisan sitemap:generate` |
| Queue không chạy | `supervisorctl restart mamiviet-queue-default:* mamiviet-queue-instagram:*` |
| Tracking không track | Check `/admin` → Tracking tab → format ID đúng? Clear cache |
| GSC verify fail | Clear cache + reload site source, check meta tag render |

Chi tiết: [docs/deployment.md § 4](deployment.md) — 15 troubleshooting entries.

---

## 8. Documentation map

| File | Nội dung |
|---|---|
| [docs/deployment.md](deployment.md) | Server setup, Nginx config, Supervisor, deploy script |
| [docs/journals/](journals/) | 5 dev journals (Phase 01-05 completion notes) |
| [plans/20260421-1706-seo-admin-and-blog/](../plans/20260421-1706-seo-admin-and-blog/) | Full implementation plan (5 phases, reviewed) |
| [CLAUDE.md](../CLAUDE.md) | AI assistant context cho dev tương lai |

---

## 9. Support & maintenance contract

### Post-handover support (suggested scope)
- **Bug fix critical** (site down, security issue): trong vòng 24h
- **Feature request**: scope riêng, quote theo work
- **Content advisory** (SEO, blog strategy): monthly review optional

### Contact
- Dev email: `nhatkha1407@gmail.com`
- GitHub issues: https://github.com/locdev-2001/mamiviet/issues (private repo, chỉ collaborator access)

---

## 10. Post-handover checklist (client side)

- [ ] Nhận Filament admin password, đổi mới ngay
- [ ] Accept GSC Owner invite
- [ ] Accept GA4 Admin invite
- [ ] Accept GBP Primary Owner invite (nếu có)
- [ ] Login admin thử — tạo 1 post test draft
- [ ] Review Global Settings → SEO + Tracking đầy đủ
- [ ] Preview 1 bài blog trước publish
- [ ] Verify bài publish live trên `/blog/{slug}`
- [ ] Monitor GA4 Realtime — thấy user khi mở site
- [ ] Check GSC Coverage report weekly (2 tuần đầu)
- [ ] Đặt lịch post blog 2-3 bài/tháng

---

Plan close date: **2026-04-22**. Production URL: https://restaurant-mamiviet.com
