# Phase 01 — SEO admin cho Home + Bilder

## Context Links

- [plan.md](plan.md)
- Files liên quan:
  - [resources/views/components/seo.blade.php](../../resources/views/components/seo.blade.php)
  - [app/Http/Controllers/PageController.php](../../app/Http/Controllers/PageController.php)
  - [app/Filament/Support/GlobalSettingsSchema.php](../../app/Filament/Support/GlobalSettingsSchema.php)
  - [app/Models/Setting.php](../../app/Models/Setting.php)

## Overview

- **Priority**: Cao — nền tảng cho Phase 02-04
- **Status**: Done
- Mở rộng SEO cho 2 trang đang có (Home, Bilder): admin chỉnh title/description/**keywords**/**robots**/**og_image per-page** qua `GlobalSettings` Filament page.

## Key Insights

- `GlobalSettingsSchema` là SSOT → chỉ cần thêm key trong schema, `GlobalSettings::mount/save` tự xử lý
- `Setting::get($key, $locale)` đã support locale map → thêm key mới là đủ, không phải đổi logic
- `<x-seo>` đang nhận `$seo` array → mở rộng thêm `keywords`, `robots`
- `PageController::buildSeo()` tập trung build SEO data → mở rộng 1 chỗ, 2 trang hưởng lợi
- **`partials/jsonld-localbusiness.blade.php` đã tồn tại** và đang include trong `app.blade.php` khi `$isHome === true` → **tái dùng + mở rộng**, KHÔNG tạo partial mới
- `GlobalSettingsSchema` chưa support `type: 'select'` → cần thêm trong `buildComponent()` của `GlobalSettings` page

## Requirements

**Functional**
- Admin chỉnh được per-page per-locale: title, description, **keywords**, **robots** (index/noindex/nofollow), **og_image**
- Render `<meta name="keywords">`, `<meta name="robots">` trong `<head>`
- JSON-LD `Restaurant` render cho homepage: name, address, telephone, openingHours, servesCuisine, image, url, priceRange

**Non-functional**
- Không tăng số query (cache Setting đã có)
- Không thay đổi response time > 5ms
- Fallback an toàn nếu admin chưa nhập (dùng default trong `buildSeo`)

## Architecture

```
Admin (Filament GlobalSettings)
    └─> GlobalSettingsSchema::TABS['seo'] (thêm keys)
         └─> Setting table (key-value JSON, locale map)
              └─> PageController::buildSeo() (đọc per-locale)
                   └─> view('app', ['seo' => $seo])
                        └─> <x-seo> render <head>
                             ├─ <meta name="keywords">
                             ├─ <meta name="robots">
                             └─ @include jsonld-restaurant (if isHome)
```

## Related Code Files

**Modify:**
- `app/Filament/Support/GlobalSettingsSchema.php` — thêm keys keywords/robots/og_image cho home + bilder
- `app/Filament/Pages/GlobalSettings.php::buildComponent()` — thêm case `'select'` type
- `app/Http/Controllers/PageController.php::buildSeo()` — đọc thêm keywords/robots, og_image per page
- `resources/views/components/seo.blade.php` — render keywords + robots
- `resources/views/partials/jsonld-localbusiness.blade.php` — (ĐÃ TỒN TẠI) dùng `seo.og_image` Setting cho `image` property thay vì hardcode
- `database/seeders/SettingSeeder.php` (nếu có) — seed default cho key mới

**Create:** (none — tái dùng partial đã có)

**Delete:** (none)

## Implementation Steps

### 1. Mở rộng `GlobalSettingsSchema` + support type `select`

Trong tab `seo`, với mỗi page (home, bilder), thêm:
- `seo.{page}.keywords` — text, translatable, rules `max:255`
- `seo.{page}.robots` — **type `select`**, translatable=false, options: `['index, follow' => 'Index + Follow (mặc định)', 'noindex, follow' => 'Noindex + Follow', 'noindex, nofollow' => 'Noindex + Nofollow']`, default `index, follow`
- `seo.{page}.og_image` — image, translatable=false

Trong "Site-wide defaults" giữ nguyên `seo.og_image` làm fallback.

**Đồng thời cập nhật `GlobalSettings::buildComponent()`** thêm case `'select'`:
```php
'select' => Select::make($statePath)->options($def['options'] ?? [])->default($def['default'] ?? null),
```

### 2. Cập nhật `PageController::buildSeo()`

Thêm vào array trả về:
```php
'keywords' => Setting::get("seo.{$pageKey}.keywords", $locale) ?: '',
'robots' => Setting::raw("seo.{$pageKey}.robots") ?: 'index, follow',
```

Cập nhật logic `og_image`:
```php
$ogImage = Setting::raw("seo.{$pageKey}.og_image")
    ?: Setting::raw('seo.og_image')
    ?: '/logo.png';
```

### 3. Cập nhật `seo.blade.php`

Thêm:
```blade
@if (! empty($seo['keywords']))
    <meta name="keywords" content="{{ $seo['keywords'] }}">
@endif
<meta name="robots" content="{{ $seo['robots'] ?? 'index, follow' }}">
```

### 4. Mở rộng `jsonld-localbusiness.blade.php` (đã tồn tại)

File đã có và đã include trong `app.blade.php:10` khi `$isHome === true`. Chỉ cần:
- Đổi `image` từ hardcode `/logo.png` → `Setting::raw('seo.og_image') ?? '/logo.png'` (URL tuyệt đối qua `url()`)
- Xác minh `@type: Restaurant`, `servesCuisine`, `priceRange` đã có — nếu chưa, bổ sung
- Xác minh `openingHoursSpecification` parse từ `footer.hours` robust với format multi-line

**KHÔNG tạo file mới.**

### 5. Test manual

- Login Filament, vào GlobalSettings → SEO tab
- Nhập keywords/robots/og_image cho Home
- Reload `/` → view-source xác minh `<meta name="keywords">`, `<meta name="robots">`, JSON-LD Restaurant render đúng
- Nhập `noindex, nofollow` cho Bilder → reload `/bilder` kiểm tra

### 6. Run migration/compile check

- Không có migration mới ở phase này
- `php artisan view:clear && php artisan config:clear`
- `npm run build` không cần (chỉ sửa Blade)

## Todo List

- [x] Mở rộng `GlobalSettingsSchema::TABS['seo']` với keywords/robots (select)/og_image per page
- [x] Thêm case `'select'` trong `GlobalSettings::buildComponent()`
- [x] Cập nhật `PageController::buildSeo()` đọc key mới + fallback og_image per page
- [x] Render keywords + robots trong `seo.blade.php`
- [x] Mở rộng `jsonld-localbusiness.blade.php` dùng `seo.og_image` Setting
- [x] (Không cần) tạo partial mới — đã có sẵn
- [x] Seed default robots = `index, follow` cho cả 2 page
- [x] Test manual admin → render
- [x] Validate với Google Rich Results Test (URL public hoặc ngrok)

## Success Criteria

- Admin chỉnh 5 field (title/description/keywords/robots/og_image) per page per locale qua Filament
- View-source `/` và `/bilder` hiển thị đúng meta tags
- `search.google.com/test/rich-results` parse Restaurant JSON-LD OK (0 errors)
- Không breaking change: các trang hiện tại vẫn render bình thường khi Setting trống

## Risk Assessment

| Risk | Mitigation |
|------|-----------|
| Admin nhập robots sai format | **Dùng Select dropdown 3 options**, không cho nhập tự do — loại bỏ hoàn toàn rủi ro typo |
| og_image per-page phá fallback khi xoá | Logic `?? Setting::raw('seo.og_image') ?? '/logo.png'` đã có |
| JSON-LD LocalBusiness phụ thuộc footer hours format | Parser strict + fallback (bỏ openingHours nếu parse lỗi, không crash) |
| Setting cache không refresh | `Setting::booted()` đã flush cache on save/delete |
| `og_image` là path tương đối (`/storage/...`) → social crawler không hiểu | Prefix `url()` khi render meta + JSON-LD (không hardcode domain) |

## Security Considerations

- `keywords`, `robots` render trong `<head>` → escape bằng Blade `{{ }}` (auto htmlspecialchars)
- `og_image` URL qua `safeUrl()` đã có trong PageController (chỉ cho http/https hoặc /storage/)
- JSON-LD render raw JSON → dùng `json_encode` với `JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP` để chống XSS

## Completion Notes

**Date completed:** 2026-04-21

**Files modified:**
1. `app/Filament/Support/GlobalSettingsSchema.php` — thêm keywords/robots/og_image schema per page
2. `app/Filament/Pages/GlobalSettings.php::buildComponent()` — thêm case `'select'`
3. `app/Http/Controllers/PageController.php::buildSeo()` — đọc keywords/robots, og_image per page với fallback
4. `resources/views/components/seo.blade.php` — render keywords + robots meta tags
5. `resources/views/partials/jsonld-localbusiness.blade.php` — dùng `seo.og_image` Setting thay hardcode
6. `database/seeders/GlobalSettingsSeeder.php` — seed defaults, refactor thành `firstOrCreate` (idempotent)

**Post-review fixes applied:**
- `mount()` chỉ apply default khi `$stored === null` (tránh override user input)
- Cast `(string)` cho title/description/keywords trong `buildSeo()` 
- `GlobalSettingsSeeder` chuyển `firstOrCreate` (tránh duplicate errors)

**Code review score:** 8/10 — không có critical issue, 3 suggestions đã apply

**Deferred to Phase 02:**
- Refactor IIFE URL logic thành `App\Support\ImageUrl` helper (tái dùng cho Post)
- Consolidate fallback chain giữa `safeUrl()` + blade IIFE

## Next Steps

- Phase 02: Pattern `buildSeo()` sẽ được generalize để Post tái dùng (extract helper `SeoBuilder` class nếu cần)
- Phase 04: Thêm hreflang + canonical cho post dùng cùng helper
