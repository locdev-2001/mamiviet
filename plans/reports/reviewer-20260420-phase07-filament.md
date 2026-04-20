# Code Review — Phase 07 Filament Admin

**Date:** 2026-04-20
**Reviewer:** code-reviewer
**Scope:** 11 files (AdminPanelProvider, User, PageResource + 3 Pages + RelationManager, InstagramPostResource + 1 Page, ManageSiteSettings + view)
**Phase doc:** `plans/20260420-1235-mamiviet-cms-i18n-seo/phase-07-filament-resources.md`

## Tổng quan

Phase 07 hoàn thành scaffold Filament panel cho Mamiviet CMS. Cấu trúc rõ ràng, tách biệt resource/page/relation manager đúng convention. Translatable plugin được apply đúng cấp panel + trait đúng vị trí. Tuy nhiên có **1 lỗi Critical về authorization**, vài bug correctness về cache/translatable, và một số điểm DRY/UX cần cải thiện.

---

## CRITICAL

### C1. `User::canAccessPanel()` luôn trả về `true` — bất kỳ user nào đăng ký được đều vào `/admin`
**File:** `app/Models/User.php:31-34`

```php
public function canAccessPanel(Panel $panel): bool {
    return true;
}
```

**Impact:** Bất kỳ ai có account trong bảng `users` đều truy cập được toàn bộ panel admin (CRUD Page/Section, edit Settings, dispatch jobs, xoá InstagramPost). Nếu sau này mở registration công khai (auth/register routes vẫn còn từ source cũ) thì đây là leak nghiêm trọng. Production blocker.

**Fix recommendation:**
- Tối thiểu: kiểm tra whitelist email domain hoặc cờ `is_admin`:
  ```php
  return str_ends_with($this->email, '@restaurant-mamiviet.com');
  ```
- Tốt hơn: thêm migration cột `is_admin` boolean default false và check `$this->is_admin === true`. Đồng thời disable / xoá registration routes trong `routes/auth.php` (Phase 08+ scope).
- Verify: chạy `php artisan route:list | grep register` — nếu còn route public, đóng lại trước khi go live.

---

## HIGH

### H1. Cache `site.settings` không flush khi save bằng `Setting::updateOrCreate()` từ ManageSiteSettings → FE đọc value cũ
**File:** `app/Filament/Pages/ManageSiteSettings.php:100`, related `app/Models/Setting.php:27-32`

`Setting::booted()` đăng ký `static::saved` để forget cache. Tuy nhiên `updateOrCreate` không fire `saved` nếu attributes không đổi (no dirty). Khi user mở form và bấm Save mà không edit → cache giữ nguyên (OK). Nhưng vấn đề thực: `Setting::value()` được gọi trong `mount()` và `form()` BẤT chấp đã có cache, ngay sau khi save xong, controller FE đọc qua `all_grouped()` → cache rebuilt — về mặt logic ổn. **Tuy nhiên:** `value` casts `'string'` nhưng dòng 102 ghi `json_encode($value)` cho array → đọc lại sẽ trả về JSON string thay vì array, callers phía FE phải tự `json_decode` (asymmetric I/O).

**Impact:** Settings group `hours` (Repeater dự định) hoặc bất kỳ field array nào sẽ lưu chuỗi JSON, nhưng `Setting::value()` không decode → consumer nhận string khó dùng. Hiện chưa có array field thực tế (Repeater chưa add), nhưng `nap_lat`/`nap_lng` cast string sẽ mất kiểu numeric.

**Fix:**
- Hoặc: thêm cột `value` chuyển sang `json` cast và serialize/deserialize đối xứng trong model.
- Hoặc: thêm helper `Setting::set($group,$key,$value)` trong model để tập trung serialize, và ManageSiteSettings gọi helper đó (single source of truth — đúng DRY rule trong CLAUDE.md).
- Verify thủ công sau fix: `Cache::forget('site.settings'); Setting::value('nap','lat')` trả về đúng kiểu.

### H2. `ManageSiteSettings::save()` không có try/catch → partial write nếu DB fail giữa chừng
**File:** `app/Filament/Pages/ManageSiteSettings.php:94-108`

Loop 5 group × N keys = ~18 query `updateOrCreate` không bọc transaction. Nếu MySQL drop connection ở key thứ 10, settings bị lưu một nửa, cache tiếp tục giữ giá trị cũ (vì `saved` event đã fire ở record trước nhưng cache rebuild rồi lại fire forget → race nhỏ). Validation form cũng chưa được gọi tường minh.

**Fix:**
```php
public function save(): void {
    $state = $this->form->getState(); // already validates
    \DB::transaction(function () use ($state) {
        foreach (self::FIELDS as $group => $keys) { ... }
    });
    Notification::make()->title('Settings saved')->success()->send();
}
```
Bonus: gọi `$this->form->getState()` đã trigger validation nên OK; nhưng nên thêm `->live(onBlur: true)` cho TextInput URL/email để feedback sớm.

### H3. `Page::$translatable = ['slug','seo']` + form dùng dotted key `seo.title` — Spatie HasTranslations không hiểu nested
**File:** `app/Models/Page.php:17`, `app/Filament/Resources/PageResource.php:40-44`

`HasTranslations` trait coi mỗi field là 1 JSON column `{locale: scalar}`. Khi mark `seo` translatable, Spatie expect format `{"de": <whole-seo-object>, "en": <whole-seo-object>}`. Form Filament `TextInput::make('seo.title')->translatable()` sẽ thử ghi `seo->title` per-locale, format thực sẽ trở thành rối: hoặc Filament ghi `seo = {de: {title: ..., description: ...}, en: {...}}` (OK nếu Spatie hiểu), hoặc ghi `{title: {de:..., en:...}, description: {de:..., en:...}}` (Spatie KHÔNG hiểu).

**Impact:** Risk đọc/ghi không đối xứng. Khi load lại edit form, fields có thể trống hoặc lỗi cast. Đã được flag ở "Risks" của plan nhưng cần verify thực tế trước khi ship.

**Fix recommendation:**
- Test thực tế: tạo 1 Page, save SEO DE+EN, dump `Page::first()->getRawOriginal('seo')` xem format JSON.
- Nếu fail: tách thành các cột riêng `seo_title` (json), `seo_description` (json), `seo_og_image` (string non-translatable) và đăng ký từng cái translatable. Đơn giản hơn nested.

### H4. `Section::$casts` mark `image_path` là `array` nhưng FileUpload trả về string path
**File:** `app/Models/Section.php:31`, `app/Filament/Resources/PageResource/RelationManagers/SectionsRelationManager.php:54`

`FileUpload::make('image_path')` (single, không `multiple()`) trả về string đường dẫn. Cast `array` sẽ làm Eloquent `json_decode` chuỗi này → `null` hoặc throw. Lỗi runtime ngay lần Save Section đầu tiên có upload.

**Fix:** xoá `'image_path' => 'array'` khỏi `$casts`. Hoặc chuyển `FileUpload` sang `multiple()` nếu thực sự muốn nhiều ảnh.

### H5. `cta_link` vừa translatable vừa được dùng làm URL → mismatch UX/data shape
**File:** `app/Models/Section.php:28`, RelationManager line 58

Link nội bộ thường giống nhau giữa DE/EN (ví dụ `/menu`). Mark translatable buộc admin nhập 2 lần. Nếu để `cta_link` là plain string column và translatable chỉ cho `cta_label`, đỡ lỗi và đỡ nhầm (Phase 02 doc Hero footer-cta cũng gợi ý link nội bộ mặc định).

**Fix:** Loại `cta_link` khỏi `Section::$translatable`, đổi migration column `cta_link` từ `json` → `string`. Đã bị bỏ qua ở RelationManager line 58 (`->translatable()`) → cũng cần bỏ.

---

## MEDIUM

### M1. Mass assignment risk trên `Page::$fillable = ['slug','status','seo']`
**File:** `app/Models/Page.php:15`

Không nguy hiểm hiện tại (Filament tự enforce schema), nhưng nếu sau này có API endpoint nhận input user nó dễ bị set `status=published` tuỳ ý. Acceptable cho admin-only nhưng cân nhắc thêm `Gate::authorize` ở controller layer khi dùng ngoài Filament.

### M2. SEO `og_image` upload không đi qua `ImageTransformationService`
**File:** `app/Filament/Resources/PageResource.php:42-45`

Plan step 3 (line 84) yêu cầu `saveUploadedFileUsing` gọi service. Code hiện chỉ `directory('seo')->disk('public')` raw upload — không tối ưu, không tạo variants. Nếu Phase 08 chưa wire xong thì OK tạm; nhưng cần TODO comment + checklist trong phase-08 để không quên.

Tương tự với `SectionsRelationManager::image_path` (line 54) và `ManageSiteSettings::seo_default_og_image` (line 88-89).

**Fix:** Thêm `// TODO Phase 08: hook ImageTransformationService::processImage()` để dễ grep.

### M3. `ManageSiteSettings` thiếu authorization hook
**File:** `app/Filament/Pages/ManageSiteSettings.php`

Page không override `canAccess()` → mọi user đi qua được C1 (canAccessPanel) đều edit settings. Nếu sau này phân quyền (editor vs admin), Settings nên restrict admin only.

**Fix:**
```php
public static function canAccess(): bool {
    return auth()->user()?->is_admin === true;
}
```

### M4. `InstagramPostResource::$label` dùng `?string $label` — Filament 3 expect `$modelLabel`
**File:** `app/Filament/Resources/InstagramPostResource.php:27`

Property `protected static ?string $label` không tồn tại trong base `Resource` class của Filament 3. Đúng là `$modelLabel` và `$pluralModelLabel`. Hiện tại không gây lỗi (chỉ thừa property) nhưng label vẫn auto-generate từ model name → "Instagram Posts" thay vì custom. Cosmetic.

**Fix:** Đổi thành `protected static ?string $modelLabel = 'Instagram Post';`.

### M5. `KeyValue::make('data')` cho `featured_dishes` không validate shape
**File:** `RelationManagers/SectionsRelationManager.php:60`

Admin có thể nhập key bất kỳ → FE đọc `data.dishes[]` (Phase 02 expectation) sẽ fail nếu admin nhập sai. KeyValue là flat string→string, không support array of dishes. Cần `Repeater::make('data.dishes')->schema([...name, image, price...])` để khớp schema FE.

**Fix:** Thay `KeyValue` bằng `Repeater` đúng shape khi `type === featured_dishes` (Phase 08 hoặc bổ sung ngay).

### M6. `headerActions(['scrapeNow'])` không có throttle/confirmation
**File:** `app/Filament/Resources/InstagramPostResource.php:50-61`

User bấm 10 lần liên tiếp → 10 jobs queued, có thể trigger rate limit Instagram và lock account/IP. Không có UI feedback ngăn re-click trong window N giây.

**Fix:**
```php
->requiresConfirmation()
->modalDescription('Job will fetch latest posts from Instagram. Limit ~1 lần/giờ.')
->disabled(fn () => Cache::has('ig_scrape_dispatched'))
->action(function () {
    Cache::put('ig_scrape_dispatched', true, now()->addMinutes(10));
    ScrapeInstagramPostsJob::dispatch();
    ...
})
```

---

## LOW

### L1. DRY — `FIELDS_BY_TYPE` map dùng tốt nhưng có thể move sang `Section::FIELDS_BY_TYPE` constant
**File:** `RelationManagers/SectionsRelationManager.php:29-35`

Single source of truth: nếu FE/Phase 05 controllers cần biết section nào có field nào, định nghĩa nên ở Model (`Section`), không trong RelationManager. Tránh duplicate khi Phase 05 cần check.

### L2. `BadgeColumn` deprecated trong Filament 3.2+
**File:** `PageResource.php:54`, `SectionsRelationManager.php:69`

Filament 3 khuyến nghị dùng `TextColumn::make('status')->badge()->color(fn($state)=>...)`. `BadgeColumn` vẫn work nhưng sẽ remove ở v4. Update để future-proof.

### L3. `ManageSiteSettings` view lặp button Save — đã có `getFormActions()`
**File:** `resources/views/filament/pages/manage-site-settings.blade.php:5-9` vs `ManageSiteSettings.php:110-115`

View render manual button submit, nhưng method `getFormActions()` cũng định nghĩa Save action. Duplicate → có thể hiện 2 button. Dùng `<x-filament-panels::form.actions :actions="$this->getFormActions()" />` hoặc xoá button trong blade.

### L4. `'site_site_name'` naming nhân đôi prefix
**File:** `ManageSiteSettings.php:32, 57`

Field `site_site_name` (group `site`, key `site_name`) thừa "site_". Đặt key là `name` cho group `site`: `'site' => ['name','email','phone','cuisine','price_range']` → field `site_name`. Đọc dễ hơn, gọn key DB.

### L5. `napIncomplete` recompute mỗi lần render form — minor perf
**File:** `ManageSiteSettings.php:52-53`

`Setting::value()` đi qua cache nên rẻ, OK.

### L6. Translatable `slug` có thể tạo conflict route
Slug per-locale (DE: `ueber-uns`, EN: `about-us`) yêu cầu Phase 05 controller resolve theo current locale. Cần test edge: nếu user tạo Page mới với slug DE rỗng nhưng EN có giá trị → route DE 404. Nên thêm validation `required` ít nhất default locale.

**Fix:** `TextInput::make('slug')->translatable()->required()` hiện chỉ require trên locale active, không guard locale mặc định DE. Cần custom rule.

---

## Edge Cases (Scout)

1. **Tạo Page mới (chưa có ID) → SectionsRelationManager** — Filament native xử lý: RelationManager chỉ render trên EditPage, không xuất hiện ở CreatePage. Phải save Page trước, sau đó tab Sections mới enabled. OK behavior, nhưng cần document cho admin (placeholder text trên CreatePage).
2. **Section không có `title` translation cho locale đang chọn** — `TextColumn::make('title')->translatable()->limit(60)` sẽ render empty string. Không crash, nhưng admin khó scan list. Suggest fallback: `->formatStateUsing(fn ($state, $record) => $state ?: $record->getTranslation('title','de',false))`.
3. **Race khi 2 admin save Settings cùng lúc** — `updateOrCreate` không lock; người sau ghi đè. Acceptable cho 1 restaurant/single admin scenario.
4. **`Cache::rememberForever` + cache driver `file`** — nếu deploy production dùng `file` cache, multi-process không invalidate đồng thời. OK trên Laragon dev. Production nên Redis.
5. **InstagramPost delete + re-scrape** — không có unique constraint trên `short_code` được verified ở review này. Nếu thiếu, scrape lại sau delete sẽ tạo duplicate. (Out of phase 07 scope, flag cho Phase 09.)

---

## Positive Observations

- Cấu trúc thư mục chuẩn Filament 3, tách Pages/RelationManagers đúng convention.
- `Translatable` trait được dùng đúng trên Resource + 3 Page subclasses (`getLocaleSwitcherAction()`).
- `FIELDS_BY_TYPE` map + closure `$visibleWhen` là pattern DRY tốt — gọn hơn lặp `in_array` từng field.
- `Setting::booted()` flush cache automatic — đúng observer pattern.
- `InstagramPostResource::canCreate() = false` + form fields disabled — đúng intent read-only.
- Notification UX khi dispatch scrape job: rõ ràng có ETA "1-2 minutes".
- `Section::TYPES` constant — single source of truth cho enum types.

---

## Recommended Actions (priority order)

1. **[C1]** Sửa `User::canAccessPanel()` whitelist trước khi merge.
2. **[H3]** Test thực tế nested `seo.title` translatable; nếu broken → migrate sang flat columns.
3. **[H4]** Bỏ cast `image_path => array` trong `Section`.
4. **[H5]** Bỏ `cta_link` khỏi translatable list + đổi migration column.
5. **[H1, H2]** Refactor `Setting::set()` helper + bọc transaction trong `ManageSiteSettings::save()`.
6. **[M3]** Add `canAccess()` cho `ManageSiteSettings`.
7. **[M5]** Đổi `KeyValue` → `Repeater` cho `featured_dishes.dishes`.
8. **[M6]** Throttle scrape button.
9. **[L1-L6]** Cleanup labels, BadgeColumn → badge(), de-dup save button.
10. Re-test smoke flow sau khi fix C1+H3+H4: tạo Page → add 6 sections → switch DE/EN → save Settings → reload FE.

---

## Metrics

- Files reviewed: 11
- LOC reviewed: ~430
- Issues: 1 Critical, 5 High, 6 Medium, 6 Low
- Lint/typecheck: chưa chạy (cần `vendor/bin/phpstan analyse` Phase QA loop)

## Unresolved Questions

- Có plan thêm role/permission package (spatie/laravel-permission) hay chỉ dùng cờ `is_admin` boolean?
- `seo` JSON shape final là gì? `{title, description, og_image}` hay nested per-page-type?
- `Setting` có cần history/audit log không (compliance EU)?

**Status:** DONE_WITH_CONCERNS
**Summary:** Phase 07 scaffold đúng hướng nhưng có 1 Critical authz hole (`canAccessPanel = true`) phải fix trước khi merge và 4 High về data shape (translatable nested seo, image_path cast, cta_link translatable, transaction missing).
**Concerns:** C1 là production blocker. H3+H4 sẽ gây runtime error ngay lần dùng đầu tiên.
