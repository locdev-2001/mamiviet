# Code Review — Phase 05 Inertia + SSR

**Scope**: 14 file (BE controllers/middleware/config/blade + FE entries/layout/pages/section renderer + vite). SSR đã verify hoạt động, content render server-side cho cả 4 routes.

## Đánh giá tổng quan
Pragmatic, đúng phạm vi minimal CMS. Code sạch, type-safe, hydration pattern an toàn. SSR-correctness OK. Một vài điểm cần lưu ý trước khi mở rộng.

## Critical
Không có vấn đề critical (security/data loss/breaking).

## High Priority

1. **N+1 tiềm ẩn trong `HandleInertiaRequests::loadSettings()` chạy MỌI request** (`HandleInertiaRequests.php:52`)
   - Mỗi request (kể cả XHR partial reload, asset 404 fallback nếu lọt qua web group) đều query `settings` table. Cần cache:
     ```php
     Cache::remember('inertia.settings', 3600, fn () => $this->loadSettings());
     ```
   - Khi admin Filament sửa setting → invalidate bằng model observer. Bắt buộc trước production.

2. **SSR fetch trong `Bilder.tsx` — đúng pattern nhưng làm SEO content rỗng** (`Bilder.tsx:21-32`)
   - `useEffect` không chạy server-side → SSR HTML chỉ có skeleton + `<h1>Bilder</h1>`. Googlebot không thấy ảnh Instagram (chấp nhận được vì nội dung Instagram không cần index, nhưng nên ghi rõ trong decisions).
   - Pattern `mounted` + `useEffect` ở đây dư thừa: `useEffect` đã chỉ chạy client. Có thể bỏ state `mounted`, gộp logic — giảm 1 render cycle.

3. **SSR cache key version (`config/inertia.php`)**: chưa set `version` callback dùng asset manifest hash. Khi deploy mới mà user đang giữ tab cũ → Inertia không force full reload, có thể load page component không tồn tại. Cân nhắc dùng `md5_file(public_path('build/manifest.json'))`.

## Medium Priority

4. **DRY trong `SectionRenderer.tsx`**: 4 component (Hero/TextBlock/GalleryTeaser/ContactCta) lặp lại block CTA button giống hệt nhau (4 lần cùng class `inline-block ... bg-primary ...`). Extract:
   ```tsx
   const CtaButton = ({ label, link }: { label: string; link: string }) => (
     <a href={link} className="inline-block bg-primary ...">{label}</a>
   );
   ```
   Single point of modification cho styling button sau này.

5. **`GalleryTeaser` hardcode `["/image1.jpg" ... "/image4.jpg"]`** (`SectionRenderer.tsx:62`) — bỏ qua `section.image_path` từ CMS. Nên đọc từ `section.image_path ?? defaultImages` để admin có thể đổi qua Filament.

6. **DRY label song ngữ** trong `AppLayout.tsx` — pattern `locale === "en" ? X : Y` lặp 6 lần. Khi thêm tiếng thứ 3 (ja?) phải sửa khắp nơi. Tách thành dict `t = { home: { de: "Startseite", en: "Home" } }` hoặc dùng vue-i18n-style helper. Không cần thiết bây giờ nhưng lưu ý.

7. **`IndexController` + `BilderController` lặp logic** load page-by-slug + map sections. Extract `PageRepository::findBySlug(string $slug, string $locale)` hoặc trait. Tránh duplicate khi thêm Menu/Kontakt/Impressum.

8. **Accessibility**:
   - `Hero` background `<img alt="">` OK (decorative). Tốt.
   - `GalleryTeaser` images `alt=""` cũng decorative — OK.
   - **Logo** (`AppLayout.tsx:17`): `alt={settings.site_name}` + adjacent text duplicate cùng nội dung → screen reader đọc 2 lần. Đặt `alt=""` cho `<img>` decorative khi đã có text label kề bên.
   - `<html lang>` có set theo `app()->getLocale()` — tốt.

## Low Priority

9. `Bilder.tsx:29` — `data?.data ?? data` chấp nhận 2 shape response tuỳ tiện, có thể che bug. Chuẩn hoá API trả `{ data: [...] }`.
10. `vite.config.ts` giữ alias `@` → `./src` (legacy). Khi xoá legacy nhớ remove alias.
11. `app.tsx:13-17` dev/prod branch dùng `createRoot` cho dev → mất khả năng test SSR hydration ở dev. Có thể chấp nhận để tránh hydration mismatch warning lúc dev với HMR.
12. `app.blade.php` thiếu `<meta name="robots">` mặc định và Open Graph fallback — bổ sung khi đến phase SEO.

## Security
- Inertia auto-escape props ✓. `cta_link` từ DB render thẳng vào `href` — nếu admin nhập `javascript:...` sẽ thực thi. Validate URL scheme ở Filament form (allow `http(s)://`, `tel:`, `mailto:`, `/path`).
- `target="_blank"` luôn kèm `rel="noopener noreferrer"` ✓.
- CSRF token có trong meta ✓.

## Positive
- SSR sidecar setup đúng chuẩn Inertia v1.
- `noExternal: ["@inertiajs/react"]` chuẩn cho SSR bundle.
- Type-safe `SharedProps` / `PageProps`.
- Hydration-safe pattern cho Instagram fetch (dù dư thừa).
- `published()` scope + `firstOrFail()` → 404 gracefully.
- `ensure_pages_exist: true` trong testing config — bắt missing page sớm.

## Recommended Actions (priority order)
1. Cache `loadSettings()` + observer invalidation (High #1)
2. Validate `cta_link` scheme tại Filament form (Security)
3. Set asset version callback (High #3)
4. Extract `CtaButton` + `PageRepository` (Medium #4, #7)
5. `GalleryTeaser` đọc `image_path` từ CMS (Medium #5)
6. Simplify `Bilder.tsx` — bỏ `mounted` state (High #2)
7. Fix logo alt duplication (Medium #8)
