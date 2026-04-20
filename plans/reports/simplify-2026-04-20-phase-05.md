# Báo cáo Simplify Phase 05 — Mamiviet CMS

Ngày: 2026-04-20
Phạm vi: chỉ Phase 05 deliverables. Không sửa src/pages/*, src/components/* JSX/styling.

## Thay đổi đã áp dụng

### 1. `src/lib/i18n.ts` (rewrite gọn lại)
- **Bỏ**: `i18next-browser-languagedetector` import + `.use(LanguageDetector)` + block `detection`.
- **Lý do**: locale do server quyết định 100% qua URL (`/en` prefix → SetLocale middleware → `__APP_LOCALE__`). `lng` được set explicit từ `serverLocale`, nên `LanguageDetector` cùng `htmlTag/localStorage/navigator` order là dead config — i18next bỏ qua detector khi `lng` đã set. Thêm vào đó `switchLocale` trong Header dùng `window.location.assign` (full reload) nên cache localStorage không có giá trị giữa lần điều hướng.
- **Hệ quả**: giảm 1 dep runtime, giảm bề mặt rối. Behavior không đổi.
- **Lưu ý**: nếu sau này muốn detect khi user vào URL không có locale từ trình duyệt → thêm lại detector. Hiện tại YAGNI.

### 2. `src/components/Header.tsx:8-15` (move constant ra ngoài component)
- **Move** `LOCALE_PATH_MAP` từ trong function component (re-tạo mỗi render) ra module-scope constant ngay dưới `NAV_KEYS`, kèm type annotation `Record<string, Record<string, string>>`.
- **Lý do**: constant không phụ thuộc props/state — không nên tái khởi tạo mỗi render. Đây là cải thiện nhỏ về performance + clarity. JSX/styling giữ nguyên 100%.
- **Behavior**: `switchLocale` vẫn dùng cùng map, kết quả identical.

### 3. `app/Http/Controllers/PageController.php:60`
- **Trước**: `$paths = $pathMap[$slugDe] ?? ['de' => '/', 'en' => '/en'];`
- **Sau**: `$paths = $pathMap[$slugDe] ?? $pathMap['home'];`
- **Lý do**: DRY — fallback literal trùng đúng với entry `home`. Single source of truth.

## Files không cần thay đổi

- **`routes/web.php`**: 2 routes + 2 EN routes — đủ ngắn, loop sẽ obfuscate. KISS thắng.
- **`resources/views/app.blade.php`**: meta block hơi dài nhưng chỉ render 1 lần per page; extract `@include` cho 7 dòng og + 4 dòng twitter sẽ tăng indirection mà không giảm trùng lặp đáng kể. YAGNI.
- **`vite.config.ts`**: 19 dòng, sạch.
- **`app/Models/Setting.php`**: gọn, có cache invalidation đúng kiểu Laravel.
- **`app/Http/Middleware/SetLocale.php`**: 30 dòng, mỗi nhánh có mục đích rõ. `implode + slice` có thể thay bằng `Str::after` nhưng không đáng.
- **`app/Http/Controllers/PageController.php`** phần còn lại: `renderPage` + `buildSeo` + `safeUrl` đã tách trách nhiệm rõ. `safeUrl` security-critical, giữ nguyên.

## Kiểm tra

- `php -l` PASS cho PageController, SetLocale, Setting.
- Behavior preserved: lang attr, hreflang, canonical, og/twitter meta, `__APP_LOCALE__` injection, full-reload switchLocale.

## Files thay đổi

- `d:\Data\laragon\www\mamiviet\src\lib\i18n.ts`
- `d:\Data\laragon\www\mamiviet\src\components\Header.tsx`
- `d:\Data\laragon\www\mamiviet\app\Http\Controllers\PageController.php`

**Status:** DONE
**Summary:** Đơn giản hóa 3 file (i18n bỏ detector dead config, Header hoist constant ra module scope, PageController dùng `$pathMap['home']` làm fallback DRY). Không sửa JSX/styling, không đổi route, giữ `safeUrl()`, behavior identical.
