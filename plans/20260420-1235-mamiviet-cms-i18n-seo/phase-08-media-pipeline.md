---
title: "Phase 08 — Media pipeline (Intervention v3 + responsive)"
status: pending
priority: P1
effort: 3h
blockedBy: [07]
---

## Context Links

- Report: `plans/reports/researcher-20260420-filament-i18n-media.md` §4

## Overview

ImageTransformationService process upload → WebP original + 4 variants (480/768/1280/1920). Filament FileUpload hook đã ref ở Phase 07. Frontend `<ResponsiveImage>` React component dùng `<picture>` + srcset + width/height (chống CLS).

## Key Insights

- Intervention v3 dùng `ImageManager` + driver explicit (GD prod thường có sẵn; Imagick faster nhưng cần ext)
- `scaleDown(width:)` keep aspect ratio + không upscale (an toàn)
- Lưu cả `width`/`height` original vào JSON output để FE set `<img width height>` chống CLS
- File naming: `uniqid()_time()` đảm bảo unique; tránh extension collision
- Lưu vào `storage/app/public/{folder}/` + `php artisan storage:link` (chạy 1 lần)
- Disk `public` URL serve qua `Storage::url()` → `/storage/...`

## Requirements

**Functional:**
- Upload bất kỳ ảnh (jpg/png/webp) → 5 files: original.webp + 4 variants .webp
- Output JSON: `{original, variants:{w480,w768,w1280,w1920}, width, height}`
- Idempotent: re-upload same file không overwrite cũ (uniqid nên OK)
- FE render `<picture>` với srcset, lazy load, width/height attrs

**Non-functional:** process <2s cho ảnh 5MB; queue nếu >2s.

## Architecture

```
Filament FileUpload
   │
   ▼ saveUploadedFileUsing hook
ImageTransformationService::processImage($file, $folder)
   │
   ├── read() → Intervention Image instance
   ├── original.webp (quality 80)
   ├── for each [480,768,1280,1920]:
   │     scaleDown(width)
   │     save as {name}_w{N}.webp
   ├── extract width/height
   └── return JSON {original, variants, width, height}
                    │
                    ▼
            stored as Section.image_path JSON column
                    │
                    ▼ Inertia props
            <ResponsiveImage> renders <picture>
```

## Related Code Files

**Create:**
- `app/Services/ImageTransformationService.php`
- `resources/js/components/ResponsiveImage.tsx`

**Modify:**
- Section components in `resources/js/components/sections/*` to use `<ResponsiveImage>`
- `app/Filament/Resources/PageResource/RelationManagers/SectionsRelationManager.php` (already ref'd Phase 07; verify hook works)

**One-time:**
- `php artisan storage:link`

## Implementation Steps

1. **ImageTransformationService**:
```php
namespace App\Services;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ImageTransformationService {
    private ImageManager $manager;
    private array $sizes = [480, 768, 1280, 1920];

    public function __construct() {
        $this->manager = new ImageManager(new GdDriver());
    }

    public function processImage(UploadedFile $file, string $folder): array {
        $name = uniqid() . '_' . time();
        $base = "{$folder}/{$name}";
        $img = $this->manager->read($file->getPathname());
        [$w, $h] = [$img->width(), $img->height()];

        Storage::disk('public')->put("{$base}.webp", (string) $img->toWebp(80));

        $variants = [];
        foreach ($this->sizes as $size) {
            if ($size > $w) continue; // không upscale
            $resized = clone $img;
            $resized->scaleDown(width: $size);
            $path = "{$base}_w{$size}.webp";
            Storage::disk('public')->put($path, (string) $resized->toWebp(80));
            $variants["w{$size}"] = Storage::disk('public')->url($path);
        }

        return [
            'original' => Storage::disk('public')->url("{$base}.webp"),
            'variants' => $variants,
            'width' => $w,
            'height' => $h,
        ];
    }
}
```

2. **storage:link**:
```bash
php artisan storage:link
```

3. **ResponsiveImage React component**:
```tsx
type Img = { original:string; variants:Record<string,string>; width:number; height:number };
export function ResponsiveImage({ image, alt, sizes='100vw', priority=false }: {
  image: Img | string | null; alt: string; sizes?: string; priority?: boolean;
}) {
  if (!image) return null;
  const data: Img = typeof image === 'string' ? JSON.parse(image) : image;
  const srcset = Object.entries(data.variants)
    .map(([k,url]) => `${url} ${k.replace('w','')}w`).join(', ');
  return (
    <picture>
      <source srcSet={srcset} sizes={sizes} type="image/webp" />
      <img
        src={data.original}
        alt={alt}
        width={data.width}
        height={data.height}
        loading={priority ? 'eager' : 'lazy'}
        decoding="async"
        fetchPriority={priority ? 'high' : 'auto'}
      />
    </picture>
  );
}
```

4. Update Hero/Featured/Gallery/Story section components → replace `<img>` với `<ResponsiveImage image={image} alt={title} priority={type==='hero'} />`. LCP image (hero) phải `priority`.

5. Add preload LCP hint trong Blade root (Phase 06 file):
```blade
@if(!empty($preloadLcp))
  <link rel="preload" as="image" href="{{ $preloadLcp }}" fetchpriority="high">
@endif
```
Controller pass `$preloadLcp = $heroSection->image_path['variants']['w1280'] ?? null;`

6. Test:
- Upload 1 ảnh JPG 4MB qua admin
- Verify storage: `ls storage/app/public/sections/` → 5 files
- View FE → DevTools Network: WebP loaded, đúng variant theo viewport
- Lighthouse Image audit pass

## Todo List

- [ ] ImageTransformationService class
- [ ] `storage:link`
- [ ] ResponsiveImage component
- [ ] Section components dùng ResponsiveImage
- [ ] LCP preload trong Blade
- [ ] Test upload ảnh thật từ admin
- [ ] Verify 5 webp files generated
- [ ] DevTools verify đúng srcset variant served
- [ ] Lighthouse Image score check

## Success Criteria

- Admin upload → 5 .webp files in `storage/app/public/{folder}`
- JSON column `image_path` có structure đúng
- FE `<picture>` render WebP, đúng size cho viewport
- No CLS (img có width/height)
- LCP image preloaded

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| GD ext missing trên server | Check `php -m | grep gd`; install nếu thiếu; fallback Imagick driver |
| Process slow (>2s) trên ảnh huge | Queue: dispatch job trong hook, return placeholder; Filament hỗ trợ async upload |
| File orphan khi delete Section | Section model `deleted` event → unlink files (defer to follow-up; out of MVP) |
| Re-upload cùng filename trong cùng giây | uniqid + time đủ unique, nhưng add `random_int` for safety |
| FE pass image as already-decoded array vs JSON string | Component handle cả 2 cases (typeof check) |

## Quality Loop

`/ck:code-review` Service + Component → `/simplify` (extract `webp80` helper, DRY toWebp calls) → upload test ảnh thật.

## Next Steps

→ Phase 09 cron + scrape button. → Phase 10 Lighthouse verify image score.
