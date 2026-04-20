# Code Review — Phase 02 DB Schema

**Scope:** 2 migrations + Page/Section models. Migrations đã chạy + tinker verified.

## Critical
Không có.

## High
1. **`pages.slug` thiếu uniqueness / index** — slug translatable JSON nhưng không có generated column hoặc index nào trên `slug->de` / `slug->en`. Phase 04 routing sẽ dùng `whereJsonContains('slug->de', ...)` → full table scan + cho phép trùng slug. Khuyến nghị: thêm MySQL generated column `slug_de VARCHAR(191) AS (JSON_UNQUOTE(slug->'$.de'))` UNIQUE, hoặc tối thiểu validate uniqueness ở application layer (FormRequest) và document rủi ro.

2. **`Section::$casts` thiếu cast cho translatable JSON fields** — `image_path` và `data` cast `array` OK, nhưng spatie/translatable tự handle title/subtitle/body/cta_label/cta_link nên không cần cast — đúng. Tuy nhiên `Page::$casts` chỉ có `seo => array` mà `seo` đã nằm trong `$translatable` → spatie sẽ override. Cast `array` thừa, có thể conflict khi spatie decode (spatie returns string per-locale, không phải array). Nên **xoá `seo => array`** trong Page::$casts để tránh double-decode (spatie v6 docs).

## Medium
3. **`type` ENUM cứng** — đã chấp nhận trong plan (risk table), nhưng hardcoded ở 3 nơi (migration, model `TYPES` const, plan). Khi thêm type mới phải sửa migration mới + const. OK trong scope, nhưng cân nhắc bỏ ENUM dùng `string` + validate qua `TYPES` const → single source of truth là model.

4. **`order` là `integer` không unsigned** — cho phép giá trị âm vô nghĩa. Đổi `unsignedInteger` hoặc `unsignedSmallInteger`.

5. **Thiếu index `status`** — query `scopePublished()` sẽ scan toàn bảng. Pages ít record nên chấp nhận được, nhưng thêm `$table->index('status')` chi phí gần 0.

6. **`Page::$fillable` chứa `slug` và `seo`** — translatable fields fillable an toàn với spatie (nó intercept setAttribute), nhưng nếu mass-assign `['slug' => 'home']` (string thay vì array) sẽ lưu vào current locale only — dễ bug ở Filament form nếu không cấu hình đúng. Document rõ trong Filament resource phase.

## Low
7. Migration filename dùng date `2026_04_20` — Laravel convention dùng `_HHMMSS` 6 digits, file đang dùng `120000` OK.
8. `Section` model thiếu `scopeOrdered()` helper (đã orderBy trong relation, nhưng query trực tiếp `Section::all()` không order).
9. Thiếu factory class dù `use HasFactory` được khai báo — sẽ vướng khi viết test.

## Edge Cases
- **Cascade delete OK** cho sections khi xoá page. Nhưng nếu sau này có `media` polymorphic relation → cần observer cleanup file vật lý (không scope phase này).
- **Rollback test** chưa thấy verify trong checklist — FK cascade thì rollback sections trước pages OK do thứ tự timestamp.

## Recommended Actions
1. Xoá `seo => array` cast trong `Page` (xung đột spatie).
2. Thêm uniqueness strategy cho `slug->de` (generated column hoặc validation layer) — quyết trước Phase 04.
3. Đổi `order` → `unsignedInteger`.
4. Thêm `index('status')` trên pages.

## Unresolved
- Có cần soft deletes cho `pages`/`sections` không? Plan không nhắc — nếu admin lỡ xoá thì mất data + media references.
