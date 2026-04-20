---
title: "Phase 05 — Migrate Index.tsx sang content động, guard enabled"
status: pending
priority: P1
effort: 1h
blockedBy: [04]
---

## Overview

Refactor `src/pages/Index.tsx` từ `t('homepage.xxx')` sang `useHomepageSection('xxx')`. Mỗi section wrap `if (!section?.enabled) return null`. Fallback về `t()` khi DB content thiếu.

## Related Code Files

**Modify:**
- `src/pages/Index.tsx` — tách mỗi section thành sub-component đọc context

**Create:**
- `src/components/sections/HeroSection.tsx`
- `src/components/sections/WelcomeSection.tsx`
- `src/components/sections/WelcomeSecondSection.tsx`
- `src/components/sections/OrderSection.tsx`
- `src/components/sections/ReservationSection.tsx`
- `src/components/sections/ContactSection.tsx`
- `src/components/sections/GallerySliderSection.tsx`
- `src/components/sections/IntroSection.tsx`
- `src/components/MediaImage.tsx` — render `<picture>` với WebP srcset

## Pattern

```tsx
// HeroSection.tsx
import { useHomepageSection } from '@/lib/contexts/AppContentContext';
import { useTranslation } from 'react-i18next';
import MediaImage from '@/components/MediaImage';

export function HeroSection() {
  const section = useHomepageSection('hero');
  const { t } = useTranslation();
  if (section && !section.enabled) return null;

  const title = section?.content.title ?? t('homepage.hero_title');
  const bg = section?.media.bg;

  return (
    <section className="relative w-full min-h-[90vh] ...">
      <MediaImage media={bg} fallback="/primaryRestaurant.jpg" alt="Restaurant interior"
                  className="absolute inset-0 w-full h-full object-cover opacity-90 z-0" priority />
      {/* ... overlay + title */}
      <h1 className="text-[32px] ...">{title}</h1>
    </section>
  );
}
```

```tsx
// MediaImage.tsx — responsive <picture> với WebP srcset + alt/dimensions per SEO
type Media = {
  src: string;
  srcset?: string;
  type?: string;
  alt?: string;
  width?: number;
  height?: number;
} | null;

export default function MediaImage({
  media, fallback, fallbackAlt, className, priority, sizes = '100vw',
}: {
  media: Media;
  fallback: string;              // static asset path fallback
  fallbackAlt: string;           // alt khi không có media từ DB
  className?: string;
  priority?: boolean;            // true cho LCP (hero)
  sizes?: string;                // srcset sizes hint (vd "(min-width:1024px) 50vw, 100vw")
}) {
  if (!media) {
    return (
      <img src={fallback} alt={fallbackAlt} className={className}
           loading={priority ? 'eager' : 'lazy'} decoding="async"
           {...(priority ? { fetchpriority: 'high' as const } : {})} />
    );
  }

  return (
    <picture>
      {media.srcset && (
        <source type={media.type ?? 'image/webp'} srcSet={media.srcset} sizes={sizes} />
      )}
      <img
        src={media.src}
        alt={media.alt || fallbackAlt}
        className={className}
        width={media.width || undefined}
        height={media.height || undefined}
        loading={priority ? 'eager' : 'lazy'}
        decoding="async"
        {...(priority ? { fetchpriority: 'high' as const } : {})}
      />
    </picture>
  );
}
```

### `sizes` hint per section (quan trọng cho đúng variant)

| Section | `sizes` |
|---|---|
| hero (bg, full-bleed) | `100vw` |
| welcome/welcome_second `main` | `(min-width:1024px) 50vw, 100vw` |
| welcome `overlay` (thumbnail) | `(min-width:768px) 160px, 144px` |
| order `left`/`right` | `(min-width:1024px) 33vw, 100vw` |
| reservation/contact `image` | `(min-width:1024px) 50vw, 100vw` |
| gallery_slider `images` | `(min-width:1280px) 20vw, (min-width:1024px) 25vw, (min-width:768px) 33vw, 50vw` |

Pass `sizes` prop từ mỗi section — browser chọn đúng variant, tiết kiệm bandwidth + tối ưu LCP.

## Todo List

- [ ] Create 8 section sub-components
- [ ] Create `MediaImage` component
- [ ] Refactor `Index.tsx` dùng sub-components — file < 80 dòng
- [ ] Guard `enabled === false` → return null mỗi section
- [ ] Fallback i18n keys khi content undefined (backward compat)
- [ ] Gallery slider: loop `section?.media.images ?? default9` để render Swiper
- [ ] Build `npm run build` — không TS error
- [ ] Smoke: toggle 1 section off admin → refresh UI → section biến mất
- [ ] Smoke: upload ảnh mới → refresh UI → ảnh mới hiển thị

## Success Criteria

- Admin toggle `hero.enabled = false` → UI không render hero
- Admin sửa `welcome.title` DE → UI German hiện text mới
- Admin upload ảnh mới cho `welcome.main` → UI render ảnh mới (WebP)
- Không còn regression visual so với current UI khi DB có data đầy đủ
- Index.tsx < 80 dòng (chỉ orchestrate sub-sections)

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| CSS layout break khi section tách component | Copy class chính xác, không đổi markup structure |
| Fallback i18n mismatch với content DB | Giữ đồng bộ: seeder nạp từ locale → match; sau đó admin edit DB ưu tiên |
| Gallery slider cần exact 9 images | Nếu DB < 9 → lặp lại, hoặc accept ít hơn (Swiper autoplay vẫn ok) |

## Quality Loop

`/ck:code-review` sub-components + MediaImage → `/simplify` (DRY patterns, extract section `enabled` HOC nếu lặp) → test cả 3 scenarios (toggle / edit text / upload image).
