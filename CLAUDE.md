# CLAUDE.md

Hướng dẫn cho Claude Code khi làm việc trong repo này.

## Tổng quan dự án

Monorepo cho website nhà hàng **Mamiviet** (Leipzig, Đức). Một codebase chứa cả backend Laravel lẫn frontend React.

**Phạm vi sử dụng thực tế**: chỉ là **landing page + trang `/bilder` hiển thị bài viết Instagram**. Toàn bộ feature cart/checkout/payment/reservation/spin wheel/coupons/auth tồn tại trong code (di sản từ source cũ) nhưng KHÔNG được dùng. Đừng đề xuất cải tiến cho các phần đó trừ khi user yêu cầu rõ.

## Stack

- **Backend**: Laravel 10 + PHP 8.3, MySQL (`mamiviet` DB), serve qua Laragon
- **Frontend**: React 18 + TypeScript + Vite (output build → `public/build/`)
- **Admin (sắp có)**: Filament 3
- **i18n**: Tiếng Đức (chính) + English

## Cấu trúc thư mục

```
app/                  Laravel: controllers, models, services, jobs
bootstrap/            Laravel bootstrap
config/               Laravel config
database/             migrations + seeders
resources/            Laravel views (Blade) + lang
routes/               api.php, web.php, console.php
storage/              Laravel storage (logs, cache, sessions)
tests/                PHPUnit tests
vendor/               Composer deps

src/                  React source code
  pages/              Routes (Index, Menu, Bilder, ...)
  components/         shadcn/ui + custom components
  lib/services/       API client (axios)
  lib/contexts/       React contexts
  lib/locales/        de.json, en.json
  hooks/              Custom hooks
public/               Static assets (videos HLS, images) + Laravel index.php
public/build/         Vite build output (gitignored)
docs/legacy-api/      Docs từ source API cũ (payment, reservation, spin...)
docs/video/           HLS + video optimization scripts
```

## Lệnh dev

### Backend (Laravel)

- Serve qua Laragon: `http://mamiviet.test` (cần config auto-host)
- Hoặc: `php artisan serve` (port 8000)
- `php artisan migrate` — chạy migrations
- `php artisan route:list` — xem routes
- `php artisan tinker` — REPL

### Frontend (React/Vite)

- `npm run dev` — dev server port **8080**, hot reload
- `npm run build` — build production → `public/build/`
- `npm run lint` — ESLint

### Workflow phát triển

- **Dev**: chạy song song Laragon (Laravel) + `npm run dev`. Frontend gọi API qua `VITE_API_BASE_URL` trong `.env`.
- **Production**: `npm run build` rồi Laravel serve `public/build/index.html` qua route fallback.

## Routing

- Laravel `routes/web.php`:
  - `/` và `Route::fallback()` → serve `public/build/index.html` (React SPA)
  - `/admin/*` → các route MVC cũ (sẽ thay bằng Filament)
- Laravel `routes/api.php`: API endpoints (cái đang dùng: `GET /api/user/instagram-posts`)

## Đường dẫn alias

`@/` trỏ tới `src/` (config trong `vite.config.ts` + `tsconfig.json`).

## i18n

Mọi text user-facing PHẢI dùng translation key. Mặc định Đức (de), thêm tiếng Anh (en) trong `src/lib/locales/`.

## Lưu ý

- React 18 với StrictMode disabled
- TypeScript relaxed (`noImplicitAny: false`, `strictNullChecks: false`)
- Lovable component tagger chỉ chạy ở dev mode
