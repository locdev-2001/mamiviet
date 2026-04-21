# Phase 01 Hoàn thành: SEO admin cho Home + Bilder

**Date**: 2026-04-21 17:06
**Severity**: Medium
**Component**: SEO / Admin / Filament
**Status**: Completed

## What Happened

Hoàn thành Phase 01 của plan SEO admin — mở rộng GlobalSettings Filament để quản lý meta tags per-page (keywords, robots, og_image) cho 2 trang hiện có: Home và Bilder. Toàn bộ 6 file được sửa, code review đánh giá 8/10, tất cả 3 suggestions từ reviewer được apply.

## The Brutal Truth

Điều hay nhất là chúng ta không cần thêm field mới vào database — pattern `Setting` key-value + `GlobalSettingsSchema` SSOT đã tồn tại từ trước, chỉ cần bổ sung 6 keys vào schema và logic xử lý tự động hoạt động. Nhưng điều khó chịu là `GlobalSettings::buildComponent()` chưa support type `'select'`, phải thêm case để dropdown robots không bị bỏ qua.

Post-review phát hiện `mount()` bị override user input khi có default — tưởng là fix đơn giản nhưng là bug logic có thể xóa mất data. May mà reviewer bắt được. Seeder ban đầu dùng `create()` rồi lại thêm dòng `update()`, gây duplicate error nếu chạy lại — phải refactor thành `firstOrCreate()` mới idempotent.

## Technical Details

**Files modified:**

1. **GlobalSettingsSchema.php** — Thêm 6 keys: `seo.{home,bilder}.{keywords,robots,og_image}`
   - `robots` dùng type `'select'` với 3 options: `index,follow` (default), `noindex,follow`, `noindex,nofollow`
   - Fallback `og_image` chain: per-page → site-wide → `/logo.png`

2. **GlobalSettings.php** — Thêm case `'select'`:
   ```php
   'select' => Select::make($statePath)->options($def['options'] ?? [])->default($def['default'] ?? null)
   ```

3. **PageController::buildSeo()** — Đọc keywords/robots per-page, handle casting type string

4. **seo.blade.php** — Render conditional `<meta name="keywords">` + `<meta name="robots">`

5. **jsonld-localbusiness.blade.php** — Thay hardcode `/logo.png` → `Setting::raw('seo.og_image')` (URL safe via `url()`)

6. **GlobalSettingsSeeder.php** — Refactor từ `create/update` → `firstOrCreate` (idempotent)

**Manual testing:** 4 URLs (de/en × home/bilder) — tất cả pass view-source verification.

## What We Tried

- **Approach 1:** Thêm select type vào `buildComponent()` trực tiếp → work, nhưng reviewer gợi ý thêm `helperText` + xác minh `default` logic
- **Approach 2:** Seed robots value ngay trong GlobalSettingsSchema default → không work, phải seed từ `GlobalSettingsSeeder` vì schema chỉ dùng khi `$stored === null`
- **Approach 3:** Dùng plugin SEO thứ ba (Spatie?) → rejected, tối ưu với pattern Setting + GlobalSettingsSchema sẵn có (DRY hơn)

## Root Cause Analysis

Chúng ta có sẵn architecture tốt (Setting model + GlobalSettingsSchema SSOT), nhưng:
- **Thiếu type `'select'`** trong builder → phải thêm case, không phải bug mà là feature gap
- **Mount logic naive** — apply default unconditional → xóa user input nếu có default
- **Seeder không idempotent** — tái chạy lỗi vì `create()` không check duplicate

Bài học: Pattern DRY này làm việc rất tốt miễn là implementation logic đủ careful (null check, idempotent seeds, type casting).

## Lessons Learned

1. **Setting + GlobalSettingsSchema = SSOT cực mạnh**: Thêm field chỉ sửa 1 chỗ (schema), migration/seeder tự theo. Không như column database phải migrate. Cho Phase 02-04 tái dùng model này cho Post.

2. **Type casting quan trọng**: Laravel `Setting::get()` trả về `mixed`, cần explicit cast `(string)` khi đưa vào Blade hoặc DB query — `null` coalescing dễ tạo bug.

3. **URL normalization duplicate**: `PageController::safeUrl()` + Blade IIFE `/storage` handling → đây là tech debt. Phase 02 phải extract `App\Support\ImageUrl` helper.

4. **Idempotent seeding**: Dùng `firstOrCreate(['key' => ..., 'locale' => ...], ['value' => ...])` để tránh duplicate error trên re-run.

5. **Mount/Save lifecycle cần tỉnh táo**: Filament form lifecycle có tẽ nạp default → user input → validate → save. `mount()` bỏ qua nếu không `$stored === null`.

## Next Steps

- **Phase 02**: Generalize `buildSeo()` pattern thành base class hoặc helper class `SeoBuilder` để Post tái dùng (keywords, robots, og_image, title, description)
- **Tech debt (Phase 02+)**: Consolidate URL normalization thành `App\Support\ImageUrl::secure()` — dùng chung giữa PageController + blade partials
- **Phase 04**: Hreflang + canonical dùng cùng helper
- **Immediate**: Code theo rule "không tiếng Việt trong code/admin" — đã enforce ở review

---

**Files affected:** 6 modified, 0 created, 0 deleted
**Review score:** 8/10 → Applied 3 post-review fixes
**Tested:** Manual verification 4 URLs (de/en home + bilder) ✓
**Blocking:** None — Phase 02 can start
