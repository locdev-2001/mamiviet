# Code Review — Phase 05: SPA blade + SEO server-side

**Ngày:** 2026-04-20
**Phạm vi:** PageController, app.blade.php, web.php, SetLocale, vite.config, App.tsx, i18n.ts
**Tổng quan:** Kiến trúc đúng (no Inertia, src/ giữ nguyên, DRY trong PageController). Tuy nhiên có **1 lỗi fatal blocker** + vài lỗ hổng SEO/UX cần sửa trước khi ship.

---

## CRITICAL — phải sửa trước khi ship

### C1. Fatal: `Setting.php` còn import class đã xóa
**File:** `app/Models/Setting.php:5,28`
```php
use App\Http\Middleware\HandleInertiaRequests;
...
$flush = fn () => Cache::forget(HandleInertiaRequests::SETTINGS_CACHE_KEY);
```
`HandleInertiaRequests` đã bị xóa (xác nhận trong `app/Http/Middleware/`). Bất kỳ thao tác nào load model `Setting` (save/delete, hoặc Eloquent boot) sẽ ném `Class "App\Http\Middleware\HandleInertiaRequests" not found`. CI có thể chưa bắt vì model chưa được dùng ở route nào ở phase 5, nhưng Filament admin (phase tiếp) hoặc seeder sẽ vỡ ngay.

**Fix:** Chuyển hằng số cache key về Setting model hoặc về `PageController`/`config()`:
```php
class Setting extends Model {
    public const CACHE_KEY = 'app.settings';
    protected static function booted(): void {
        $flush = fn () => Cache::forget(self::CACHE_KEY);
        static::saved($flush);
        static::deleted($flush);
    }
}
```
Rồi grep toàn project thay `HandleInertiaRequests::SETTINGS_CACHE_KEY` → `Setting::CACHE_KEY`.

### C2. Cleanup chưa hoàn tất: `resources/js/` còn tồn tại
**File:** `resources/js/app.js`, `resources/js/bootstrap.js`
Plan tuyên bố đã xóa toàn bộ `resources/js/*` nhưng 2 file còn lại. Không gây runtime error (vite.config không reference), nhưng:
- Gây nhiễu khi grep
- `app.js` set `window.axios` global — nếu lỡ ai add lại vào vite input sẽ override axios instance ở `src/lib/services/`

**Fix:** `rm -rf resources/js/`

---

## HIGH

### H1. `og:image` không có domain khi `og_image` từ DB là path tuyệt đối khác origin
**File:** `resources/views/app.blade.php:21,27`
`url($seo['og_image'])` chỉ prepend `APP_URL` khi input bắt đầu bằng `/`. Nếu admin nhập full URL (`https://cdn.example.com/x.png`) thì `url()` Laravel vẫn xử lý đúng (giữ nguyên absolute URL) — nhưng nếu nhập path không có `/` đầu (`logo.png`) sẽ thành `APP_URL/logo.png` trùng nhau và OK. Vấn đề chính: **không validate** `og_image` ở Page model → admin có thể nhập `<script>` hoặc `javascript:` URL.

**Fix:** Trong `PageController::buildSeo()` whitelist scheme:
```php
$ogImage = $pageSeo['og_image'] ?? '/logo.png';
if (!preg_match('#^(https?:)?/#i', $ogImage)) $ogImage = '/logo.png';
```

### H2. Hreflang không escape khi key thiếu / unicode
**File:** `resources/views/app.blade.php:12-15`
Loop `@foreach ($seo['hreflang'] as $lang => $url)` — nếu PageController vô tình trả mảng khác (vd test/mock), Blade `{{ $lang }}` ép ra string nhưng không validate `$lang` chỉ chứa ký tự BCP-47 hợp lệ. Hiện tại key được hardcode `de`/`en` trong PageController nên an toàn, nhưng dòng 15 `$seo['hreflang']['de']` sẽ ném `Undefined index` nếu mảng rỗng.

**Fix:** Đổi dòng 15:
```blade
<link rel="alternate" hreflang="x-default" href="{{ $seo['hreflang']['de'] ?? url('/') }}">
```

### H3. i18n không sync khi user đổi ngôn ngữ qua Header button
**File:** `src/components/Header.tsx:39,110` + `src/lib/i18n.ts:16`
`i18n.changeLanguage('en')` chỉ đổi state client — URL vẫn là `/` (de), `<html lang>` vẫn `de`, server SEO meta vẫn `de`. Search engine + share link sẽ thấy mismatch giữa nội dung hiển thị và meta tag → ảnh hưởng SEO mục tiêu của phase này.

**Fix:** Header button phải `window.location.assign('/en' + currentPath)` thay vì `i18n.changeLanguage()`. Hoặc tối thiểu sau khi changeLanguage thì redirect:
```ts
const target = i18n.language === 'de' ? '/en' : '/';
window.location.assign(target);
```

---

## MEDIUM

### M1. Page null → SEO mặc định nhưng canonical vẫn build
**File:** `app/Http/Controllers/PageController.php:25-29`
Khi `Page::published()->whereJsonContains(...)->first()` trả null (DB rỗng/seed chưa chạy), `$page?->getTranslation()` an toàn (null → `?: []`). Canonical/hreflang vẫn dựng từ `$pathMap` nên OK. **Nhưng** title/description rơi về tiếng Đức ngay cả khi `$locale === 'en'` → English visitors thấy meta tiếng Đức.

**Fix:** Tách defaults theo locale:
```php
$defaults = $locale === 'en'
    ? ['title' => 'Mamiviet — Vietnamese Restaurant Leipzig', 'description' => 'Authentic Vietnamese cuisine in Leipzig.']
    : ['title' => 'Mamiviet — Vietnamesisches Restaurant Leipzig', 'description' => 'Authentische vietnamesische Küche mitten in Leipzig.'];
```

### M2. SetLocale redirect mất method/body
**File:** `app/Http/Middleware/SetLocale.php:19-24`
`/de/...` → 301 redirect về `/...`. OK cho GET, nhưng nếu form POST tới `/de/something` sẽ mất body. Hiện routes chỉ là GET nên không gây vỡ — note để tương lai khi có form.

**Fix tương lai:** Chỉ redirect khi `$request->isMethod('GET')`.

### M3. SetLocale không strip trailing slash khi redirect root
**File:** `app/Http/Middleware/SetLocale.php:22`
`/de` (không slash sau) → `$rest = ''` → redirect `/` ✓. `/de/` → `$rest = ''` → redirect `/` ✓. Nhưng `/de/bilder/` → redirect `/bilder/` (giữ trailing). Không lỗi nhưng tạo URL không canonical. Minor.

### M4. Vite không build font.css/index.css thành chunk dùng được
**File:** `vite.config.ts:9` + `public/build/manifest.json`
Manifest có `src/index.css` và `src/styles/main.css` là entry tách biệt với `src/main.tsx` (mà `main.tsx` cũng có `css` chunk riêng — `main-CdjTyQEJ.css`). Khả năng cao có **2 file CSS** bị inject (CSS từ JS entry + CSS entry độc lập) → potential duplicate styles / FOUC.

**Khuyến nghị:** Verify trên browser DevTools xem có CSS bị load 2 lần không. Nếu `src/main.tsx` đã `import './index.css'` rồi thì bỏ `src/index.css` khỏi vite input + `@vite()`.

---

## LOW

### L1. `__APP_LOCALE__` typing
**File:** `src/lib/i18n.ts:16` — `(window as any).__APP_LOCALE__`. Nên thêm `src/types/global.d.ts`:
```ts
declare global { interface Window { __APP_LOCALE__?: 'de' | 'en'; } }
export {};
```

### L2. Blade thiếu `<meta name="robots">` cho hreflang setup
Khuyến nghị thêm `<meta name="robots" content="index,follow">` để rõ ràng intent crawl.

### L3. CSP / preconnect Google Fonts
Nếu sau này thêm CSP header, font preconnect tới `fonts.gstatic.com` cần được whitelist. Note thôi.

---

## XÁC NHẬN POSITIVE

- PageController DRY tốt (`buildSeo` + `renderPage` private helpers, single source of truth).
- Blade `{{ }}` escape mặc định → an toàn XSS cho `title`/`description` từ DB (phase này không có raw `{!! !!}`).
- SetLocale 301 redirect `/de/*` → `/` đúng SEO best practice (avoid duplicate content).
- `whereJsonContains('slug->de', ...)` đúng cú pháp spatie/laravel-translatable.
- `vite.config.ts` alias `@` khớp `tsconfig.json` → no broken imports.
- Cleanup: `bootstrap/ssr/`, `config/inertia.php`, `HandleInertiaRequests`, `IndexController`, `BilderController` đã xóa thật.
- Không còn reference `Inertia` nào trong `*.tsx`/`*.ts`/blade (chỉ còn 2 reference trong `Setting.php` — đã ghi ở C1).

---

## RECOMMENDED ACTIONS (ưu tiên)

1. **C1** — Sửa `Setting.php` ngay (1-line fix, blocker).
2. **C2** — `rm -rf resources/js/`.
3. **H3** — Sửa Header language switcher dùng URL navigation (giữ SEO consistency).
4. **H1, H2, M1** — Hardening PageController/blade với defaults theo locale + fallback `??`.
5. **M4** — Verify CSS build không duplicate trên browser.
6. **L1** — Thêm typing global cho `__APP_LOCALE__`.

---

**Status:** DONE_WITH_CONCERNS
**Summary:** Kiến trúc Phase 05 đúng và pristine src/ được tôn trọng, nhưng có 1 fatal blocker (`Setting.php` còn import class đã xóa) + cleanup chưa sạch + 1 lỗ hổng SEO consistency khi đổi ngôn ngữ qua nút Header. Sửa C1+C2+H3 trước khi đóng phase.
**Concerns:** Setting model sẽ throw khi Filament admin (phase sau) load — phải fix C1 trước khi bắt đầu phase Filament.
