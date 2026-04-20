# Code Review — Phase 06 SEO Layer

**Ngày:** 2026-04-20
**Reviewer:** code-reviewer
**Phạm vi:** SEO meta tags, JSON-LD, sitemap, robots, Setting cache

---

## Tóm tắt

Phase 06 implement chắc tay: cache settings hợp lý, dùng Spatie sitemap, JSON-LD escape an toàn (`json_encode` thay vì raw blade). Có một số vấn đề schema.org hợp lệ nhưng không tối ưu, vài chỗ hardcode/extensibility, một race condition nhỏ ở sitemap route, và vấn đề `og:locale` không match chuẩn khi locale khác `de`/`en`. Không phát hiện lỗ hổng XSS nghiêm trọng.

---

## Critical

Không có.

---

## High

### H1. Sitemap race condition khi file chưa tồn tại
**File:** `routes/web.php:7-13`

`file_exists()` + `Artisan::call('sitemap:generate')` chạy đồng bộ trong request. Nếu 2+ request đầu tiên (sau deploy hoặc xóa file) đến cùng lúc, cả 2 cùng generate ghi đè `public/sitemap.xml`. Spatie writeToFile không atomic → có khả năng request thứ 2 đọc file đang ghi dở.

Ngoài ra, để bot crawl trigger artisan command đồng bộ là attack vector DoS nhẹ (ai gọi `/sitemap.xml` cũng chạy DB query + ghi file).

**Fix:**
```php
Route::get('/sitemap.xml', function () {
    $path = public_path('sitemap.xml');
    if (! file_exists($path)) {
        // Lock + atomic write
        Cache::lock('sitemap:gen', 30)->block(10, function () use ($path) {
            if (! file_exists($path)) {
                Artisan::call('sitemap:generate');
            }
        });
    }
    return response()->file($path, ['Content-Type' => 'application/xml']);
});
```
Tốt hơn: chạy `php artisan sitemap:generate` trong `post-deploy` script và bỏ auto-generate trong route.

### H2. `og:locale` chỉ hỗ trợ `de`/`en`, fallback sai
**File:** `resources/views/components/seo.blade.php:17`

```blade
content="{{ $locale === 'de' ? 'de_DE' : 'en_US' }}"
```
Nếu sau này thêm locale khác (ja, vi…) sẽ rơi về `en_US` — sai. Đồng thời thiếu `og:locale:alternate` cho locale còn lại — Facebook OG khuyến nghị có.

**Fix:**
```blade
@php
    $ogLocaleMap = ['de' => 'de_DE', 'en' => 'en_US'];
    $ogLocale = $ogLocaleMap[$locale] ?? 'de_DE';
@endphp
<meta property="og:locale" content="{{ $ogLocale }}">
@foreach ($ogLocaleMap as $lang => $tag)
    @if ($lang !== $locale)
        <meta property="og:locale:alternate" content="{{ $tag }}">
    @endif
@endforeach
```

### H3. `openingHoursSpecification` sai schema.org
**File:** `resources/views/partials/jsonld-localbusiness.blade.php:43-54`

Mỗi entry đặt `dayOfWeek` là cả 7 ngày Mon–Sun và `name = $key` (vd `mon_sun_lunch`). Hệ quả:
- Tất cả entries đều áp dụng cho cả tuần → nếu có nhiều khung (lunch/dinner) đều phủ 7 ngày, nhưng key như `mon_fri_dinner` ngụ ý chỉ Mon–Fri — schema bị sai sự thật.
- `name` không phải property hợp lệ trong `OpeningHoursSpecification` schema.org (Google/Yandex sẽ ignore, không error nhưng vô ích).
- Nếu admin thêm key như `sat_sun_brunch`, mã không parse được day-range.

**Fix (gợi ý):** Định nghĩa schema rõ ràng cho hours setting, ví dụ value JSON `{"days":["Monday","Tuesday"...],"opens":"11:30","closes":"15:00"}`, và parse:
```php
foreach ($hours as $key => $raw) {
    $entry = json_decode($raw, true);
    if (! is_array($entry) || empty($entry['days'])) continue;
    $openingHours[] = [
        '@type' => 'OpeningHoursSpecification',
        'dayOfWeek' => $entry['days'],
        'opens' => $entry['opens'],
        'closes' => $entry['closes'],
    ];
}
```
Tối thiểu trong phiên bản hiện tại: **bỏ `name`** và parse day-range từ `$key` (`mon_fri_*` → Mon–Fri).

---

## Medium

### M1. Sitemap hardcode URL list, không query Page model
**File:** `app/Console/Commands/GenerateSitemapCommand.php:19-22`

Phase 02 đã có `Page` model với `published()` scope và `slug` translatable. Hardcode `[home, bilder]` đồng nghĩa: mỗi lần CMS thêm page mới (Phase 07 admin), phải sửa code và deploy. Trái với mục đích CMS.

**Fix:**
```php
$pages = Page::published()->get()->map(function ($p) {
    return [
        'de' => '/' . ltrim($p->getTranslation('slug', 'de'), '/'),
        'en' => '/en/' . ltrim($p->getTranslation('slug', 'en'), '/'),
        'priority' => $p->slug['de'] === 'home' ? 1.0 : 0.8,
    ];
})->all();
```
Cần xử lý case home (`/` thay vì `/home`).

### M2. Setting cache invalidation chưa tính tới mass update
**File:** `app/Models/Setting.php:27-32`

`saved`/`deleted` hooks ổn cho update qua Eloquent. Nhưng:
- `Setting::query()->update([...])` (mass update) **không trigger model events** → cache stale.
- `truncate()` cũng không trigger.

Nếu admin Phase 07 chỉ update qua Eloquent instance (`$setting->save()`) thì OK. Cần ghi chú rõ trong code:
```php
// LƯU Ý: chỉ invalidate khi save/delete qua model instance.
// Mass update via query builder phải gọi Cache::forget(self::CACHE_KEY) thủ công.
```

### M3. Restaurant schema thiếu `image` đa dạng và `address` empty fields
**File:** `resources/views/partials/jsonld-localbusiness.blade.php:17-25`

- Nếu setting `nap.street/city/zip` chưa được seed, `address` sẽ chứa string rỗng `""`. Google Rich Results validator có thể warning.
- `image` chỉ trỏ `/logo.png`. Schema.org Restaurant guidelines của Google khuyến nghị ảnh chất lượng cao của không gian/món ăn.
- Thiếu `acceptsReservations` (có thể `false` cho dự án này).
- Thiếu `@id` để liên kết entity giữa các pages.

**Fix tối thiểu:** filter empty trước khi đưa vào address:
```php
$address = array_filter([
    '@type' => 'PostalAddress',
    'streetAddress' => $nap['street'] ?? null,
    'addressLocality' => $nap['city'] ?? null,
    'postalCode' => $nap['zip'] ?? null,
    'addressCountry' => $nap['country'] ?? 'DE',
]);
if (count($address) > 2) { // có @type + ít nhất 1 field
    $data['address'] = $address;
}
```

### M4. `buildBreadcrumb` hardcode page name map — không scale
**File:** `app/Http/Controllers/PageController.php:49-56`

`$pageNames = ['bilder' => ...]`. Mỗi page mới phải sửa code. Phase 02 Page model đã có `title` translatable — dùng nó.

**Fix:**
```php
$page = Page::published()->whereJsonContains('slug->de', $slugDe)->first();
$pageName = $page?->getTranslation('title', $locale) ?? ucfirst($slugDe);
```
Hiện `renderPage` đã query `$page` rồi — pass nó vào `buildBreadcrumb` để tránh query lại.

### M5. `robots.txt` hardcode production URL
**File:** `public/robots.txt:6`

```
Sitemap: https://restaurant-mamiviet.com/sitemap.xml
```
Trên staging/local, robots.txt vẫn trỏ production → bot có thể crawl staging vào index của production sitemap, hoặc ngược lại.

**Fix:** chuyển robots.txt thành route động:
```php
Route::get('/robots.txt', function () {
    $env = app()->environment();
    $disallow = $env === 'production' ? "Disallow: /admin\nDisallow: /api" : "Disallow: /";
    $sitemap = url('/sitemap.xml');
    return response("User-agent: *\nAllow: /\n{$disallow}\n\nSitemap: {$sitemap}\n",
        200, ['Content-Type' => 'text/plain']);
});
```
Và xóa file static `public/robots.txt` (Laravel sẽ ưu tiên file static qua web server).

### M6. Inconsistent: home không có breadcrumb nhưng JSON-LD WebSite vẫn render trên mọi page
**File:** `resources/views/components/seo.blade.php:29-30`

`@include('partials.jsonld-website')` và `jsonld-localbusiness` chạy trên CẢ home và bilder. Cùng entity render 2 lần trên site. Google chấp nhận nhưng không tối ưu — nên đặt LocalBusiness chỉ ở home (entity chính), các page khác chỉ cần WebSite + Breadcrumb.

**Fix:** thêm flag `$isHome` từ controller, conditional include.

---

## Low

### L1. `safeUrl` parse_url không validate host
**File:** `PageController.php:96-106`

Cho phép user đưa URL như `http://evil.com/x.jpg` vào `og_image`. Đây là feature mong muốn (admin set OG ảnh từ CDN ngoài) hay rủi ro? Nếu Phase 07 admin nhập trường này, attacker có quyền admin có thể inject (nhưng họ đã có quyền admin rồi). Note: không phải XSS vì blade `{{ }}` escape attribute.

### L2. JSON-LD: trailing nulls trong Restaurant
**File:** `jsonld-localbusiness.blade.php:13-14`

`telephone` và `email` có thể là `null` → JSON output `"telephone": null`. Hợp lệ nhưng noisy. Nên `array_filter` để bỏ nulls.

### L3. `x-default` hreflang fallback dùng `url()->current()`
**File:** `seo.blade.php:10`

Nếu controller không pass `hreflang['de']`, fallback là URL hiện tại — có thể là URL `/en/...`. Không lý tưởng (x-default thường nên là URL chính ngôn ngữ mặc định).

### L4. Sitemap không bao gồm `lastmod`
**File:** `GenerateSitemapCommand.php`

Spatie `Url::create` mặc định `lastModificationDate = now()`. Vì hardcode pages, lastmod sẽ là thời điểm chạy command, không phản ánh thay đổi thực. Khi chuyển sang query Page model (M1), set lastmod = `$page->updated_at`.

### L5. Cache key cố định, không versioned
**File:** `Setting.php:13`

`'site.settings'` — nếu shape thay đổi (ví dụ thêm cast), restart không clear cache → bug. Thêm version: `'site.settings.v1'`.

---

## Trả lời các câu hỏi cụ thể

1. **JSON-LD validity:** Restaurant thiếu `image` chất lượng + có thể `address` rỗng (M3); BreadcrumbList và WebSite hợp lệ.
2. **openingHoursSpecification:** dayOfWeek arrays đúng chuẩn schema.org, nhưng phủ 7 ngày cho mọi entry là **sai sự thật**. `name` field nên drop (không hợp lệ trong spec). Xem H3.
3. **Setting cache invalidation:** Đúng cho save qua model instance. Mass update không trigger (M2).
4. **Sitemap hardcoded:** Nên query Page model (M1).
5. **Sitemap route race condition:** Có (H1) — cần lock hoặc generate trong deploy.
6. **buildBreadcrumb hardcoded:** Nên dùng Page->title (M4).
7. **og:locale fallback:** Sai khi locale ngoài de/en (H2).
8. **robots.txt hardcoded URL:** Có vấn đề trên staging (M5).
9. **XSS:** Không có. Tất cả meta dùng `{{ }}` escape; JSON-LD dùng `json_encode` (escape `<`, `>`, `&`, `'`, `"`). An toàn ngay cả khi DB chứa giá trị độc hại từ admin Phase 07. **Lưu ý:** nếu admin được phép nhập raw HTML, đảm bảo Filament dùng plain text input cho settings, không rich-text.
10. **N+1 / DB calls:** Mỗi partial gọi `Setting::group()` → `Setting::all_grouped()` → cache hit sau lần đầu request. Trong cùng request, gọi 4 lần (`site`, `nap`, `hours`, `social`) đều hit cache (Laravel `Cache::rememberForever` với driver file/redis là 1 read mỗi lần). Có thể tối ưu thêm bằng `array` driver memoize trong Service Provider, nhưng không đáng lo.

---

## Recommended Actions (ưu tiên)

1. **H1** — fix sitemap race / chuyển sang post-deploy
2. **H3** — sửa openingHoursSpecification (sai schema)
3. **H2** — og:locale extensible
4. **M1 + M4** — sitemap & breadcrumb dùng Page model (chuẩn bị Phase 07)
5. **M5** — robots.txt động theo env
6. **M2** — comment cảnh báo mass update vô hiệu cache
7. M3, L1–L5 — nice to have

---

**Status:** DONE_WITH_CONCERNS
**Summary:** SEO layer hoạt động và an toàn XSS. 1 race condition (sitemap route), 2 vấn đề schema.org (openingHours sai dayOfWeek + og:locale fallback), và một số chỗ hardcode cần chuyển sang Page model trước Phase 07 admin.
**Concerns/Blockers:** H1 (sitemap race) và H3 (openingHours schema sai) nên fix trước khi submit Search Console; M1/M4 nên fix trước Phase 07 để admin tạo page mới không cần sửa code.
