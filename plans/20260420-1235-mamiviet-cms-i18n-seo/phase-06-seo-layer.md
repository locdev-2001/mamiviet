---
title: "Phase 06 — SEO layer (meta, JSON-LD, sitemap, hreflang)"
status: pending
priority: P1
effort: 4h
blockedBy: [05]
---

## Context Links

- Report: `plans/reports/researcher-20260420-filament-i18n-media.md` §8 (sitemap)
- Brainstorm: schemas Restaurant + LocalBusiness + WebSite + BreadcrumbList

## Overview

Build SEO layer: Blade `<x-seo>` cho meta tags + Open Graph + Twitter + canonical + hreflang. JSON-LD partials theo schema.org. Sitemap dynamic via spatie/laravel-sitemap. robots.txt. `<html lang>` đã handled Phase 05.

## Key Insights

- Inertia + SSR đảm bảo crawler thấy meta tags không cần JS
- `@inertiaHead` inject `<Head>` từ React vào `<head>` Blade — vẫn dùng cho per-page title/description
- `<x-seo>` Blade component nhận props từ controller → render OG/Twitter/canonical/hreflang server-side (trước Inertia hydrate)
- JSON-LD: 1 site-wide block (LocalBusiness + WebSite) + per-page (Restaurant cho home + BreadcrumbList khi cần)
- Sitemap: cron weekly + manual generate command. Mỗi URL có hreflang alternate

## Requirements

**Functional:**
- Mỗi page có: `<title>`, `<meta name=description>`, canonical, og:title/description/image/url/locale, twitter:card summary_large_image, hreflang DE+EN+x-default
- JSON-LD Restaurant + LocalBusiness present trên `/` (DE & EN)
- `/sitemap.xml` lists tất cả pages cả 2 locales với `<xhtml:link rel="alternate" hreflang>`
- `/robots.txt` allow all + sitemap reference

**Non-functional:** Lighthouse SEO 100, validator schema.org pass.

## Architecture

```
Controller ── pass ['seo' => [...], 'locale' => 'de'] ──┐
                                                        │
Blade app.blade.php ── <x-seo :seo=$seo /> ────────── server-side meta render
                       │
                       ├── <title>, meta description
                       ├── canonical + hreflang alternates
                       ├── OG + Twitter
                       └── JSON-LD (Restaurant + LocalBusiness from Settings)

Route /sitemap.xml ── SitemapController ── spatie\Sitemap with hreflang
Route /robots.txt ── static or dynamic
```

## Related Code Files

**Create:**
- `resources/views/components/seo.blade.php`
- `resources/views/partials/jsonld-restaurant.blade.php`
- `resources/views/partials/jsonld-localbusiness.blade.php`
- `resources/views/partials/jsonld-website.blade.php`
- `resources/views/partials/jsonld-breadcrumb.blade.php`
- `app/Http/Controllers/SitemapController.php`
- `app/Console/Commands/GenerateSitemapCommand.php`
- `public/robots.txt`

**Modify:**
- `resources/views/app.blade.php` (replace inline meta với `<x-seo>`)
- `app/Http/Controllers/IndexController.php` + `BilderController.php` (pass `seo` + `breadcrumb` props to view via Inertia + share to Blade root data)
- `routes/web.php` (add `/sitemap.xml`)
- `app/Console/Kernel.php` (schedule sitemap weekly — config Phase 09 cũng touch file này)

## Implementation Steps

1. **x-seo component** (`resources/views/components/seo.blade.php`):
```blade
@props(['seo' => [], 'locale' => 'de', 'currentUrl' => '', 'alternates' => []])
@php
  $title = $seo['title'] ?? config('app.name');
  $desc = $seo['description'] ?? '';
  $ogImage = $seo['og_image_path'] ?? asset('images/og-default.jpg');
@endphp
<title>{{ $title }}</title>
<meta name="description" content="{{ $desc }}">
<link rel="canonical" href="{{ $currentUrl }}">
@foreach($alternates as $loc => $url)
  <link rel="alternate" hreflang="{{ $loc }}" href="{{ $url }}">
@endforeach
<link rel="alternate" hreflang="x-default" href="{{ $alternates['de'] ?? $currentUrl }}">

<meta property="og:type" content="website">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $desc }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:url" content="{{ $currentUrl }}">
<meta property="og:locale" content="{{ $locale === 'de' ? 'de_DE' : 'en_US' }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $desc }}">
<meta name="twitter:image" content="{{ $ogImage }}">

@include('partials.jsonld-website')
@include('partials.jsonld-localbusiness')
{{ $slot }}  {{-- per-page extra JSON-LD --}}
```

2. **Update app.blade.php**:
```blade
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <x-seo :seo="$seo ?? []" :locale="app()->getLocale()" :currentUrl="url()->current()" :alternates="$alternates ?? []">
    @if(!empty($jsonldRestaurant)) @include('partials.jsonld-restaurant') @endif
    @if(!empty($breadcrumb)) @include('partials.jsonld-breadcrumb') @endif
  </x-seo>
  @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
  @inertiaHead
</head>
<body>@inertia</body>
</html>
```

3. **Inertia share view data via Inertia rootView OR view data**: Use `Inertia::share` không đủ vì Blade root cần — set qua `Inertia::setRootView()` defaults + share to view via middleware `View::share`:
```php
// HandleInertiaRequests::share() — keep Inertia data
// Additionally in middleware, View::share for Blade root template:
View::share('seo', $controllerSeo); // set in controller before Inertia::render
View::share('alternates', $this->buildAlternates($req));
```
Or simpler: pass `seo` + `alternates` as Inertia props, and read in Blade via `$page['props']['seo']`. Pick one — recommend `View::share` from controller helper:
```php
// app/Http/Controllers/Concerns/SharesSeo.php
trait SharesSeo {
    protected function shareSeo(array $seo, array $alternates, bool $isHome=false): void {
        view()->share(compact('seo','alternates'));
        view()->share('jsonldRestaurant', $isHome);
    }
}
```

4. **JSON-LD partials**:

`jsonld-localbusiness.blade.php`:
```blade
@php
  $nap = \App\Models\Settings::get('nap', []);
  $hours = \App\Models\Settings::get('hours', []);
  $social = \App\Models\Settings::get('social', []);
@endphp
<script type="application/ld+json">
{!! json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'LocalBusiness',
  'name' => $nap['name'] ?? config('app.name'),
  'address' => [
    '@type' => 'PostalAddress',
    'streetAddress' => $nap['address'] ?? '',
    'addressLocality' => $nap['city'] ?? '',
    'postalCode' => $nap['zip'] ?? '',
    'addressCountry' => $nap['country'] ?? 'DE',
  ],
  'geo' => $nap['lat'] ? ['@type'=>'GeoCoordinates','latitude'=>$nap['lat'],'longitude'=>$nap['lng']] : null,
  'telephone' => $nap['phone'] ?? null,
  'sameAs' => array_filter([$social['instagram'] ?? null, $social['facebook'] ?? null]),
  'openingHoursSpecification' => collect($hours)->map(fn($h)=>[
    '@type'=>'OpeningHoursSpecification','dayOfWeek'=>$h['day'],'opens'=>$h['open'],'closes'=>$h['close'],
  ]),
], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) !!}
</script>
```

`jsonld-restaurant.blade.php` — same pattern, `@type: Restaurant`, add `servesCuisine: 'Vietnamese'`, `priceRange: '€€'` from Settings.

`jsonld-website.blade.php`:
```blade
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"WebSite","name":"{{ config('app.name') }}","url":"{{ config('app.url') }}"}
</script>
```

`jsonld-breadcrumb.blade.php` — only render khi `$breadcrumb` set.

5. **SitemapController + Command**:
```php
class GenerateSitemapCommand extends Command {
    protected $signature = 'sitemap:generate';
    public function handle() {
        $sitemap = Sitemap::create();
        foreach (Page::where('status','published')->get() as $page) {
            $deUrl = url('/'); // for home; otherwise use slug map
            $enUrl = url('/en');
            $url = Url::create($deUrl)
                ->setLastModificationDate($page->updated_at)
                ->setPriority(0.9);
            $url->addAlternate($enUrl, 'en');
            $url->addAlternate($deUrl, 'de');
            $sitemap->add($url);
        }
        // bilder pages
        $sitemap->add(Url::create(url('/bilder'))->addAlternate(url('/en/bilder'),'en'));
        $sitemap->writeToFile(public_path('sitemap.xml'));
    }
}
```

Route: `Route::get('/sitemap.xml', fn()=>response()->file(public_path('sitemap.xml'),['Content-Type'=>'application/xml']));`

6. **robots.txt** (`public/robots.txt`):
```
User-agent: *
Allow: /
Disallow: /admin
Sitemap: https://restaurant-mamiviet.com/sitemap.xml
```

7. Schedule sitemap weekly (Console/Kernel.php — Phase 09 cũng touch):
```php
$schedule->command('sitemap:generate')->weekly();
```

8. Test:
```bash
php artisan sitemap:generate
curl http://mamiviet.test/sitemap.xml
curl -s http://mamiviet.test/ | grep -E '(og:|twitter:|hreflang|application/ld)'
```

9. Validators:
- https://search.google.com/test/rich-results — Restaurant + LocalBusiness pass
- https://validator.schema.org/

## Todo List

- [ ] x-seo component
- [ ] 4 JSON-LD partials
- [ ] SharesSeo trait + apply to controllers
- [ ] app.blade.php updated với <x-seo>
- [ ] Sitemap command + route
- [ ] robots.txt
- [ ] Schedule weekly sitemap
- [ ] Validator pass: schema.org + Rich Results Test
- [ ] Lighthouse SEO 100 trên `/` và `/en`

## Success Criteria

- View source `/` shows: title, description, canonical, hreflang (de+en+x-default), OG, Twitter, JSON-LD (Website + LocalBusiness)
- `/sitemap.xml` valid XML, contains hreflang `<xhtml:link>` alternates
- Rich Results Test pass cho Restaurant + LocalBusiness
- Lighthouse SEO score 100

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| NAP placeholder → schema validator warn về missing required | Phase 03 seed minimum required (`name`,`address`,`city` luôn có giá trị, không null) + admin warning Phase 07 |
| Inertia `<Head>` overwrite Blade `<title>` | Blade chỉ set fallback; Inertia `<Head>` khi present sẽ override (đúng behavior) |
| Sitemap dùng route names không match khi page slug translatable | Build URL bằng `url('/')` + `url('/en')` rather than `route()` |
| `View::share` fire trước Inertia render → null seo | Set trong controller TRƯỚC return Inertia::render |

## Quality Loop

`/ck:code-review` SEO component + JSON-LD + sitemap → `/simplify` (extract NAP query 1 lần share view, đừng query mỗi partial) → run validators.

## Next Steps

→ Phase 07 admin để user edit seo per page. → Phase 10 Lighthouse audit verify SEO 100.
