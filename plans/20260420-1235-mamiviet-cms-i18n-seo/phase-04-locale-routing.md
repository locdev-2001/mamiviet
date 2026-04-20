---
title: "Phase 04 — Locale routing + SetLocale middleware"
status: completed
priority: P1
effort: 2h
blockedBy: [02]
completedAt: 2026-04-20
---

## Completion notes

- `SetLocale` middleware: parse `segment(1)` lowercase, support `de`/`en`, redirect 301 `/de/*` → `/*` (preserve query string), default DE
- Alias `setlocale` đã có sẵn trong Kernel — reuse
- Routes: 4 routes (`/`, `/bilder`, `/en`, `/en/gallery`). EN slug khác DE: `gallery` thay vì `bilder` (đúng theo seed Page id=2)
- Stub controllers `IndexController` + `BilderController` trả JSON (Phase 05 đổi sang `Inertia::render`)
- Curl test 6 cases: `/`=200 DE, `/en`=200 EN, `/bilder`=200 DE bilder slug, `/en/gallery`=200 EN gallery slug, `/de`=404, `/fr`=404
- Code review fixes applied: H1 query-string preserve trong redirect, M1 lowercase normalize. Defer: H2 DRY route group + M2 hardcoded ID lookup → Phase 05 refactor sang slug-based lookup
- Report: `plans/20260420-1235-mamiviet-cms-i18n-seo/reports/code-review-phase-04-locale-routing.md`

## Context Links

- Report: `plans/reports/researcher-20260420-inertia-ssr-setup.md` §4
- Brainstorm: `/` = DE, `/en/*` = EN

## Overview

Middleware parse URL prefix, set `app()->setLocale()`. Routes group cho cả DE (no prefix) và EN (`/en` prefix). 1 controller serve cả 2 locales.

## Key Insights

- Dùng `Route::prefix({locale?})` không tốt vì optional prefix khó match. Tách 2 group rõ ràng.
- Middleware đọc segment đầu tiên (`$request->segment(1)`) thay vì route param — đơn giản hơn.
- Redirect: `/en` → `/en/` OK; nhưng `/de` → `/` (canonical), `/en/home` → `/en` (canonical no trailing).
- Locale share qua Inertia (Phase 05) bằng `HandleInertiaRequests`.

## Requirements

**Functional:**
- `/` + `/bilder` → DE
- `/en` + `/en/bilder` → EN
- Invalid locale prefix (e.g. `/fr`) → 404 hoặc fallback DE
- `/de` (explicit) → 301 redirect `/` để tránh duplicate content

**Non-functional:** middleware chạy trước Inertia share data.

## Architecture

```
Request URL
   │
   ▼
SetLocale middleware ── parse segment(1) ── set app locale
   │
   ▼
Route group (de | en)
   │
   ▼
Controller ── return Inertia::render(...) ── shared['locale']
```

## Related Code Files

**Create:**
- `app/Http/Middleware/SetLocale.php`

**Modify:**
- `app/Http/Kernel.php` (register `setLocale` alias)
- `routes/web.php` (replace catch-all SPA fallback với 2 groups)

## Implementation Steps

1. SetLocale middleware:
```php
class SetLocale {
    public function handle(Request $req, Closure $next) {
        $first = $req->segment(1);
        $locale = in_array($first, ['en']) ? $first : 'de';
        if ($first === 'de') return redirect('/'.implode('/', array_slice($req->segments(),1)), 301);
        app()->setLocale($locale);
        return $next($req);
    }
}
```

2. Register Kernel alias:
```php
protected $middlewareAliases = [
    // ...
    'setLocale' => \App\Http\Middleware\SetLocale::class,
];
```

3. Update routes/web.php — REMOVE old SPA fallback `/` route, replace với:
```php
use App\Http\Controllers\IndexController;
use App\Http\Controllers\BilderController;

Route::middleware('setLocale')->group(function () {
    // DE (default, no prefix)
    Route::get('/', [IndexController::class,'show'])->name('home');
    Route::get('/bilder', [BilderController::class,'show'])->name('bilder');

    // EN
    Route::prefix('en')->group(function () {
        Route::get('/', [IndexController::class,'show'])->name('home.en');
        Route::get('/bilder', [BilderController::class,'show'])->name('bilder.en');
    });
});

// Keep /admin handled by Filament panel provider
// Keep API routes in routes/api.php untouched
```

4. Controllers (skeleton — Phase 05 hoàn thiện với Inertia):
```php
class IndexController extends Controller {
    public function show() { /* Phase 05 returns Inertia::render('Index', [...]) */ }
}
```

5. Test routing:
```bash
php artisan route:list --except-vendor
curl -I http://mamiviet.test/        # 200 + locale=de
curl -I http://mamiviet.test/en      # 200 + locale=en
curl -I http://mamiviet.test/de      # 301 → /
curl -I http://mamiviet.test/fr      # fallback DE 200 OR 404
```

## URL examples

| URL | Locale | Page |
|-----|--------|------|
| `/` | de | home |
| `/bilder` | de | bilder |
| `/en` | en | home |
| `/en/bilder` | en | bilder |
| `/de` | — | 301 → `/` |
| `/admin` | — | Filament (bypass middleware) |

## Todo List

- [ ] SetLocale middleware
- [ ] Register alias in Kernel
- [ ] Update routes/web.php (remove SPA fallback)
- [ ] Stub controllers IndexController + BilderController
- [ ] Verify route:list
- [ ] Manual curl 4 cases
- [ ] Confirm /admin Filament still loads

## Success Criteria

- 4 URLs trả status đúng (200/301)
- `app()->getLocale()` đúng trong controller
- Filament admin không bị middleware interference

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| Old route fallback (`/{any?}`) shadow new routes | Xóa hoàn toàn fallback trước khi add new |
| Middleware redirect loop | Ensure `/de` redirect chỉ trigger 1 lần (segment check rõ) |
| Filament panel `/admin` bị set locale wrong | Filament dùng group middleware riêng; `setLocale` chỉ apply web routes nhóm này |
| Static asset request đi qua middleware | Asset serve trước middleware (public/) — không ảnh hưởng |

## Quality Loop

`/ck:code-review` middleware + routes → `/simplify` (DRY route declarations bằng helper nếu lặp) → curl test.

## Next Steps

→ Phase 05 Inertia controllers dùng routing này. → Phase 06 SEO dùng `app()->getLocale()` cho hreflang.
