---
title: "Phase 04 — Hydrate content DB vào React qua window.__APP_CONTENT__"
status: pending
priority: P1
effort: 2h
blockedBy: [02]
---

## Overview

Backend `PageController` gom sections + media URLs theo locale → render vào Blade dưới dạng `<script>window.__APP_CONTENT__ = {...}</script>`. React đọc lúc mount, fallback về i18n JSON nếu DB trống.

Không migrate sang Inertia (blast radius quá lớn) — chỉ hydrate payload ban đầu.

## Payload shape (SEO-ready)

Mỗi media item có **`srcset` (5 widths) + `sizes` hint + alt per locale + width/height**:

```ts
type MediaItem = {
  src: string;          // original fallback (e.g. /storage/1/orig.jpg)
  srcset: string;       // "url480 480w, url768 768w, url1280 1280w, url1920 1920w"
  type: 'image/webp';
  alt: string;          // already resolved cho locale hiện tại
  width: number;        // dimensions GỐC (chống CLS)
  height: number;
};

window.__APP_CONTENT__ = {
  locale: 'de' | 'en',
  homepage: {
    hero: {
      enabled: true,
      content: { title: '...' },
      media: { bg: MediaItem }          // single
    },
    gallery_slider: {
      enabled: true,
      content: { title, subtitle },
      media: { images: MediaItem[] }    // array (reordered theo order_column)
    },
    // ... 8 keys
  }
}
```

## Related Code Files

**Create:**
- `app/Http/Resources/HomepageContentResource.php` — gom section → shape chuẩn
- `src/types/appContent.ts` — TypeScript types

**Modify:**
- `app/Http/Controllers/PageController.php` — pass `$content` prop vào view
- `resources/views/app.blade.php` — inject `window.__APP_CONTENT__`
- `src/lib/contexts/AppContentContext.tsx` (new) — React context provider

## Implementation Steps

1. **HomepageContentResource** — build srcset từ 4 conversions:
```php
class HomepageContentResource {
    private const WIDTHS = [480, 768, 1280, 1920];

    public static function forLocale(Page $page, string $locale): array {
        return $page->sections->sortBy('order')
            ->mapWithKeys(fn(Section $s) => [
                $s->key => [
                    'enabled' => $s->enabled,
                    'content' => $s->getTranslation('content', $locale, false) ?? [],
                    'media'   => self::mediaFor($s, $locale),
                    'data'    => $s->data,
                ],
            ])->all();
    }

    private static function mediaFor(Section $s, string $locale): array {
        return $s->media->groupBy('collection_name')
            ->mapWithKeys(function($items, $name) {
                $multi = $name === 'images';
                $shape = fn($m) => self::shape($m, $locale);
                return [$name => $multi
                    ? $items->sortBy('order_column')->map($shape)->values()->all()
                    : $shape($items->first())];
            })->all();
    }

    private static function shape($media, string $locale): array {
        if (! $media) return null;
        $srcset = collect(self::WIDTHS)
            ->filter(fn($w) => $media->hasGeneratedConversion("w{$w}"))
            ->map(fn($w) => $media->getUrl("w{$w}") . " {$w}w")
            ->implode(', ');
        $alt = $media->getCustomProperty('alt') ?? [];

        return [
            'src'    => $media->getUrl(),
            'srcset' => $srcset,
            'type'   => 'image/webp',
            'alt'    => is_array($alt) ? ($alt[$locale] ?? $alt['de'] ?? '') : (string) $alt,
            'width'  => (int) $media->getCustomProperty('width', 0),
            'height' => (int) $media->getCustomProperty('height', 0),
        ];
    }
}
```

2. **PageController** update:
```php
private function renderPage(string $slugDe): View {
    $locale = App::getLocale();
    $page = Page::with('sections.media')->published()
        ->whereJsonContains('slug->de',$slugDe)->first();

    $content = $page ? HomepageContentResource::forLocale($page, $locale) : [];

    return view('app', [
        'locale' => $locale,
        'seo' => $this->buildSeo(...),
        'isHome' => $slugDe === 'home',
        'appContent' => ['locale'=>$locale, 'homepage'=>$content],
        ...
    ]);
}
```

3. **Blade** — thêm LCP preload cho hero image (SEO + LCP metric):
```blade
<script>
    window.__APP_LOCALE__ = @json($locale);
    window.__APP_CONTENT__ = @json($appContent ?? null);
</script>

@php($heroMedia = $appContent['homepage']['hero']['media']['bg'] ?? null)
@if ($heroMedia && ! empty($heroMedia['srcset']))
    <link rel="preload" as="image" imagesrcset="{{ $heroMedia['srcset'] }}"
          imagesizes="100vw" fetchpriority="high">
@endif
```

4. **React context**:
```tsx
// src/lib/contexts/AppContentContext.tsx
import { createContext, useContext } from 'react';
export type HomepageContent = { /* mirror PHP shape */ };
const Ctx = createContext<HomepageContent | null>(null);
export const AppContentProvider = ({children}) => (
  <Ctx.Provider value={(window as any).__APP_CONTENT__?.homepage ?? null}>{children}</Ctx.Provider>
);
export const useHomepageSection = <K extends string>(key: K) => {
  const c = useContext(Ctx);
  return c?.[key] ?? null;
};
```

5. Wrap `<App>` bằng `<AppContentProvider>` trong `main.tsx`.

## Todo List

- [ ] `HomepageContentResource::forLocale()` — output shape đúng
- [ ] PageController gọi Resource, pass vào view
- [ ] `app.blade.php` inject `window.__APP_CONTENT__`
- [ ] TypeScript types + React context
- [ ] Verify DevTools Console: `window.__APP_CONTENT__` có 8 keys đúng
- [ ] Verify hiện hình với media URLs thực (sau khi Phase 03 upload)

## Success Criteria

- Page load → `window.__APP_CONTENT__.homepage` có đủ 8 section
- Section `enabled: false` vẫn có trong payload (FE tự skip)
- Media URL đúng `/storage/...` + conversion `webp` tồn tại
- Switch locale (de ↔ en) → content DE/EN đúng

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| `sections` chưa seed → `$content` rỗng | FE fallback i18n (Phase 05 guards) |
| XSS qua content admin nhập | `@json()` auto-escape; text render qua JSX (React escape). Cấm `dangerouslySetInnerHTML`. |
| Media query N+1 | `Page::with('sections.media')` eager load |
| Payload lớn (ảnh URL × conversions × 8) | Vẫn <20KB JSON; OK. Nếu lớn hơn → endpoint `/api/homepage` lazy fetch. |

## Quality Loop

`/ck:code-review` Resource + Controller + Blade → `/simplify` (extract shape transform; không duplicate N+1) → verify DevTools.
