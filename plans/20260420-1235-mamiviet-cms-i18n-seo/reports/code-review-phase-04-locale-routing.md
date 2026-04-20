# Code Review — Phase 04 Locale Routing

**Scope:** SetLocale middleware, web.php, 2 stub controllers
**Verdict:** PASS với 2 issue HIGH cần fix trước Phase 05.

## Critical
Không có.

## High

### H1. Redirect `/de` mất query string + đệ quy bảo vệ yếu
`SetLocale::handle()` line 19-22:
```php
return redirect('/' . $rest, 301);
```
- **Mất query string**: `/de?utm=foo` → redirect `/` (drop UTM) → ảnh hưởng SEO/analytics.
- **Mất trailing slash semantics**: `/de` → redirect tới `''` (empty rest) → URL `/`. OK nhưng `/de/` (segment rỗng cuối) cũng cho kết quả đúng — chấp nhận được.
- Fix: `return redirect()->to('/' . $rest . ($request->getQueryString() ? '?'.$request->getQueryString() : ''), 301);` hoặc dùng `$request->fullUrl()` rồi `str_replace`.

### H2. Trùng route name `home` vs `home.en` — DRY suy yếu
Route group `en` lặp lại y hệt cấu trúc DE. Khi thêm trang mới (impressum, menu...) sẽ phải khai báo 2 lần → dễ lệch. Đề xuất Phase 05+: dùng helper hoặc `foreach (['', 'en'] as $prefix)` để generate cả 2 cùng controller, đảm bảo single point of modification.

## Medium

### M1. Middleware không validate locale segment dạng `/EN` (uppercase)
`in_array($first, SUPPORTED, true)` strict — `/EN/gallery` rơi xuống default DE thay vì 404 hoặc redirect. Hiếm gặp nhưng nên `strtolower($first)` trước check.

### M2. `Page::find(1)` / `find(2)` hardcoded ID
Stub được phép, nhưng ghi chú: Phase 05 PHẢI đổi sang `Page::where('slug', ...)` + filter theo locale, nếu không sẽ vỡ khi seed data thay đổi hoặc xóa/thêm record.

## Low
- `SetLocale::SUPPORTED` nên đọc từ `config('app.supported_locales')` để DRY với config Laravel/Inertia sau này.
- Group `Route::prefix('en')` không có `->name('en.')` prefix → hiện phải tự viết `.en` suffix, dễ sót. Cân nhắc `->name('en.')` rồi rename `home.en` → `en.home`.

## Security / Catch-all
- Không có wildcard/fallback nào trong group → không leak. Filament `/admin` panel độc lập, không bị `setlocale` ăn vào. OK.
- Không redirect loop: `/de` redirect tới `/` (segment(1) = null ≠ 'de') → thoát. Verified.

## Tích cực
- Middleware nhỏ gọn, single responsibility.
- 301 redirect đúng chuẩn SEO (canonical DE = no prefix).
- Strict `in_array` ngăn type juggling.
- Route group sạch, không nest sâu.

## Action items trước Phase 05
1. Fix H1 (preserve query string).
2. Fix H2 hoặc ghi chú nợ kỹ thuật vào decisions.md.
3. M2: thay `find(id)` bằng slug lookup khi render Inertia thật.
