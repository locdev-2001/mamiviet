---
title: "Phase 10 — Performance pass (Lighthouse, CWV)"
status: pending
priority: P1
effort: 4h
blockedBy: [06, 08]
---

## Context Links

- Brainstorm success metrics: SEO 100, Perf ≥90, LCP <2.5s, INP <200ms, CLS <0.1
- Report Inertia §7 (asset handling), §10 (SSR prod)

## Overview

Audit Lighthouse + WebPageTest. Optimize: code split per page, image lazy + LCP preload, CSS critical inline, font-display: swap, preconnect cleanup, third-party defer.

## Key Insights

- Inertia v2 + Vite tự động code split per page (dynamic import)
- CLS chính: image không có width/height (Phase 08 fixed) + font swap (FOUT acceptable)
- LCP usually hero image → preload (Phase 08 done)
- INP: heavy useEffect / scroll handlers → debounce
- CSS critical: Tailwind purge + Vite extracts critical above-fold automatically nếu single CSS bundle nhỏ

## Requirements

**Functional:** Lighthouse mobile test pass thresholds trên `/`, `/en`, `/bilder`, `/en/bilder`.
**Non-functional thresholds:**
- Performance ≥ 90 (mobile)
- SEO = 100
- Best Practices ≥ 95
- Accessibility ≥ 90
- LCP < 2.5s
- INP < 200ms
- CLS < 0.1
- TBT < 200ms

## Audit Checklist

### Images
- [ ] Hero LCP có `<link rel="preload" as="image" fetchpriority="high">`
- [ ] All non-LCP `<img loading="lazy">`
- [ ] All images có width/height
- [ ] WebP served (verify Network tab)
- [ ] Responsive srcset hit đúng variant cho mobile (Network: w480 trên mobile)

### CSS
- [ ] Tailwind production build (purge unused) — verify CSS bundle size <50KB gzipped
- [ ] Critical CSS inline trong `<head>` (Vite plugin nếu cần `vite-plugin-critical`)
- [ ] No render-blocking external CSS

### JS
- [ ] Code split per Inertia page (verify Network: chỉ load page bundle hiện tại)
- [ ] Defer non-critical 3rd party (analytics, Instagram embed)
- [ ] No `eval`, no large polyfills (target modern browsers)

### Fonts
- [ ] `font-display: swap` trong @font-face
- [ ] Preload primary font WOFF2: `<link rel="preload" as="font" crossorigin>`
- [ ] Subset font (remove Cyrillic/CJK nếu chỉ dùng Latin)

### Network
- [ ] `<link rel="preconnect">` chỉ cho 3rd party thực sự dùng (Google Fonts, Instagram CDN); xóa preconnect orphan
- [ ] HTTP/2 enabled (Laragon Apache config)
- [ ] gzip/brotli compression

### Third-party
- [ ] Instagram feed lazy load (chỉ fetch khi scroll vào view) — `IntersectionObserver`
- [ ] No analytics script blocking (defer or load on idle)

### SSR
- [ ] SSR sidecar healthy → first byte HTML có content (curl test)
- [ ] No hydration warnings in console

## Implementation Steps

1. Run baseline Lighthouse:
```bash
npx lighthouse http://mamiviet.test/ --preset=mobile --output=html --output-path=./lh-baseline.html
```

2. Identify top 3 issues từ report.

3. Common fixes:

**Image lazy + LCP** — verify Phase 08 setup correct trên ALL section types.

**Font preload** — `resources/views/app.blade.php`:
```blade
<link rel="preload" href="/fonts/inter-var.woff2" as="font" type="font/woff2" crossorigin>
```

**Tailwind purge** — `tailwind.config.js`:
```js
content: ['./resources/**/*.{tsx,blade.php}', './src/**/*.tsx']
```

**Inertia preload Link** — auto, but verify `<Link prefetch>` for nav.

**Defer Instagram** — `Bilder.tsx`:
```tsx
const observer = new IntersectionObserver(...);
// fetch only when section visible
```

4. Re-run Lighthouse, iterate đến đạt thresholds.

5. WebPageTest cross-check:
```
https://www.webpagetest.org/ → URL https://restaurant-mamiviet.com (after deploy)
Mobile 4G profile, run 3x, median
```

6. Confirm CWV trên field data (sau deploy):
- Search Console → Core Web Vitals report
- PageSpeed Insights field data sau 28 ngày

## Todo List

- [ ] Run baseline Lighthouse cả 4 URLs
- [ ] Document top 3 issues per URL
- [ ] Fix images (lazy, sizes, preload LCP)
- [ ] Fix fonts (swap, preload, subset)
- [ ] Fix CSS (purge, critical inline nếu cần)
- [ ] Fix JS (code split verify, defer 3rd party)
- [ ] Fix preconnect cleanup
- [ ] Re-run Lighthouse → verify all thresholds
- [ ] WebPageTest 3x median check
- [ ] Document final scores trong `plans/.../perf-results.md`

## Success Criteria

- All 4 URLs hit thresholds (Perf ≥90, SEO 100, BP ≥95, A11y ≥90)
- LCP <2.5s, INP <200ms, CLS <0.1 trên mobile profile
- No console errors

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| Instagram external image hurt LCP nếu dùng làm hero | Don't use Instagram for above-fold; chỉ dưới scroll |
| Filament admin route bị Lighthouse audit (intent test FE only) | Disallow `/admin` trong robots; explicitly test only public URLs |
| SSR latency tăng FCP | Cache controller responses 60s với `Cache-Control: s-maxage`; Inertia render nhanh, vấn đề thường ở DB queries — eager load sections |
| Hot reload dev mode → Lighthouse fail | Always test `npm run build` + production-like serve |

## Quality Loop

`/ck:code-review` các fix (component changes, blade) → `/simplify` (DRY image preload helper) → re-Lighthouse.

## Next Steps

→ Phase 11 production deploy + final validation.
