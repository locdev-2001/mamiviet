---
title: "Phase 05 — Migrate pages từ React Router → Inertia"
status: pending
priority: P1
effort: 6h
blockedBy: [01, 04]
---

## Context Links

- Report: `plans/reports/researcher-20260420-inertia-ssr-setup.md` §2,§3,§5,§6,§8,§9
- Existing FE: `src/App.tsx`, `src/main.tsx`, `src/pages/Index.tsx`, `src/pages/Bilder.tsx`

## Overview

Convert SPA (React Router) sang Inertia pages. Tạo SSR + client entries. Migrate Index + Bilder. Layout giữ nguyên shadcn components.

## Key Insights

- Inertia pages đặt tại `resources/js/pages/*.tsx` (chuẩn convention; không phải `src/pages`)
- Giữ `src/components`, `src/lib` — chỉ alias `@/` resolve cũ vẫn dùng được
- `Head` component thay React Helmet
- Instagram feed (`Bilder`) phải dùng `useEffect` mounted pattern để tránh hydration mismatch
- Translations gửi qua Inertia shared data, không bundle JSON vào client (giảm bundle)

## Requirements

**Functional:**
- 2 pages: Index, Bilder render qua Inertia
- Shared data: `locale`, `translations`, `settings` (cần cho footer NAP)
- `<Link>` giữa pages dùng Inertia (no full reload)
- SSR build success, hydrate không mismatch

**Non-functional:** bundle size không tăng > 30% so với SPA cũ.

## Architecture

```
Browser request /
  │
  ▼
Laravel route → IndexController::show
  │
  ▼ Inertia::render('Index', ['page' => Page+sections, 'settings'=>...])
  │
  ▼ HandleInertiaRequests middleware adds shared {locale, translations, flash, settings}
  │
  ▼ SSR sidecar (Node) renders HTML  OR  client hydrate
  │
  ▼ resources/views/app.blade.php wraps with @inertia
```

## Related Code Files

**Create:**
- `resources/js/app.tsx` (client entry)
- `resources/js/ssr.tsx` (SSR entry)
- `resources/js/pages/Index.tsx`
- `resources/js/pages/Bilder.tsx`
- `resources/js/Layouts/AppLayout.tsx`
- `resources/views/app.blade.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `app/Http/Controllers/IndexController.php`
- `app/Http/Controllers/BilderController.php`

**Modify:**
- `vite.config.ts` (Laravel plugin + SSR entry)
- `app/Http/Kernel.php` (add HandleInertiaRequests to web group)
- `tsconfig.json` (`@/` alias both `src` + `resources/js`)

**Delete (after verify):**
- `src/App.tsx`
- `src/main.tsx`
- `src/pages/Index.tsx` (logic moved to `resources/js/pages/Index.tsx`)
- `src/pages/Bilder.tsx`
- `react-router-dom` removed from package.json

## Implementation Steps

1. **vite.config.ts** — copy from research §2:
```ts
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
export default defineConfig({
  plugins: [
    laravel({ input: ['resources/js/app.tsx'], ssr: 'resources/js/ssr.tsx', refresh: true }),
    react({ jsxRuntime: 'automatic' }),
  ],
  resolve: { alias: { '@': '/src' } },
});
```

2. **resources/views/app.blade.php**:
```blade
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  @routes
  @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
  @inertiaHead
</head>
<body>@inertia</body>
</html>
```
(Phase 06 sẽ thêm `<x-seo>` thay `<title>`.)

3. **app.tsx** + **ssr.tsx** — copy from research §3, install `@inertiajs/react/server`. Use `resolvePageComponent` from `laravel-vite-plugin/inertia-helpers`.

4. **HandleInertiaRequests**:
```php
class HandleInertiaRequests extends Middleware {
    public function share(Request $req): array {
        return array_merge(parent::share($req), [
            'locale' => app()->getLocale(),
            'settings' => fn() => [
                'nap' => Settings::get('nap'),
                'social' => Settings::get('social'),
                'hours' => Settings::get('hours'),
                'site_name' => Settings::get('site_name'),
            ],
            'flash' => fn() => ['success'=>session('success'),'error'=>session('error')],
        ]);
    }
}
```
Register trong Kernel `web` group.

5. **IndexController**:
```php
public function show() {
    $page = Page::with('sections')->whereJsonContains('slug->de','home')->firstOrFail();
    $locale = app()->getLocale();
    return Inertia::render('Index', [
        'page' => [
            'seo' => $page->getTranslation('seo', $locale),
            'sections' => $page->sections->map(fn($s) => [
                'type' => $s->type,
                'order' => $s->order,
                'title' => $s->getTranslation('title', $locale),
                'subtitle' => $s->getTranslation('subtitle', $locale),
                'body' => $s->getTranslation('body', $locale),
                'cta_label' => $s->getTranslation('cta_label', $locale),
                'cta_link' => $s->getTranslation('cta_link', $locale),
                'image' => $s->image_path,
                'data' => $s->data,
            ]),
        ],
    ]);
}
```

6. **BilderController** — similar, render `Bilder` page với shared Instagram fetch client-side.

7. **resources/js/pages/Index.tsx**:
```tsx
import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import HeroSection from '@/components/sections/HeroSection';
// ... other section components

const SECTION_MAP = { hero: HeroSection, intro: IntroSection, /* ... */ };

export default function Index({ page }) {
  return (
    <AppLayout>
      <Head title={page.seo?.title}><meta name="description" content={page.seo?.description}/></Head>
      {page.sections.map(s => {
        const Comp = SECTION_MAP[s.type];
        return Comp ? <Comp key={s.order} {...s} /> : null;
      })}
    </AppLayout>
  );
}
```

8. **Bilder.tsx** với hydration-safe pattern (research §9):
```tsx
const [mounted, setMounted] = useState(false);
useEffect(() => setMounted(true), []);
if (!mounted) return <SkeletonGrid/>;
return <InstagramGrid/>;
```

9. Build:
```bash
npm run build      # client + SSR
php artisan inertia:start-ssr   # dev test
```

10. Smoke test:
```bash
curl http://mamiviet.test/ | grep -i 'mamiviet'  # SSR HTML present
curl http://mamiviet.test/en | grep 'lang="en"'
```

11. Cleanup: remove `react-router-dom`, delete old `src/App.tsx`, `src/main.tsx`, `src/pages/*` SAU khi verify pages mới hoạt động.

## Todo List

- [ ] vite.config.ts updated
- [ ] app.blade.php created
- [ ] app.tsx + ssr.tsx
- [ ] AppLayout component
- [ ] HandleInertiaRequests middleware + register
- [ ] IndexController + BilderController
- [ ] Index.tsx + Bilder.tsx pages
- [ ] Section components extracted into `resources/js/components/sections/`
- [ ] `npm run build` success (client + SSR bundles)
- [ ] SSR sidecar starts, curl returns SSR'd HTML
- [ ] Hydration no console warnings
- [ ] Bilder hydration-safe pattern verified
- [ ] React Router removed
- [ ] Old src/App.tsx, src/main.tsx, src/pages/* deleted

## Success Criteria

- `/` returns SSR'd HTML containing section content (view source, không phải just `<div id="app">`)
- `/en` shows EN content
- Click `<Link>` không trigger full page reload
- Lighthouse Accessibility ≥90, no hydration warnings in console
- Bundle size delta ≤+30%

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| Hydration mismatch (Instagram, time, locale) | useEffect mounted pattern; pass server time via props nếu cần |
| SSR fail vì component dùng `window` | Guard `typeof window !== 'undefined'` hoặc dynamic import |
| Vite alias conflict `@/` giữa src + resources/js | Đặt 2 aliases khác (`@src` cũ, `@/` resources/js) hoặc move tất cả về resources/js |
| Translations bundle phình | Inertia share lazy load + chỉ load keys cần |
| SSR sidecar crash → page trắng | Fallback: Laravel render base shell, client hydrate bù; monitor logs |

## Quality Loop

Sau phase: `/ck:code-review` (controllers + middleware + Vite config) → `/simplify` (DRY section mapping, extract `transformSection()` helper) → manual smoke `/`, `/en`, `/bilder`, `/en/bilder` → check console no errors.

## Next Steps

→ Phase 06 SEO layer thay `<Head>` đơn giản bằng `<x-seo>` Blade + JSON-LD. → Phase 07 Filament admin để edit sections render qua flow này.
