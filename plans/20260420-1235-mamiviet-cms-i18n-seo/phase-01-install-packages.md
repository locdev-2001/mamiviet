---
title: "Phase 01 — Install packages"
status: completed
priority: P1
effort: 1h
blockedBy: []
completedAt: 2026-04-20
---

## Completion notes

- Composer: inertia-laravel 2.0.24, filament 3.3.50 + translatable plugin 3.3.50, spatie/laravel-translatable 6.11.4, spatie/laravel-sitemap 7.3.4, intervention/image 3.11.7
- NPM: @inertiajs/react 2.3.21, laravel-vite-plugin 1.3.0 (v1 do vite 5 compat, v3 cần vite 8)
- Filament panel ID `admin` → `app/Providers/Filament/AdminPanelProvider.php` created
- Intervention v3: no publishable config (skip vendor:publish)
- `.gitignore` thêm `/public/css/filament`, `/public/js/filament` (Filament re-publishes asset mỗi `filament:upgrade`)
- Giữ `@vitejs/plugin-react-swc` (đã cài, faster, tương thích Inertia)
- package.json scripts CHƯA update `vite build --ssr` — chờ Phase 05 khi có SSR entry

## Context Links

- Report: `plans/reports/researcher-20260420-inertia-ssr-setup.md` §1
- Report: `plans/reports/researcher-20260420-filament-i18n-media.md` §1, §2

## Overview

Install Composer + npm dependencies. Verify version constraints. Không config gì sâu, chỉ scaffold.

## Key Insights

- Inertia v2 stable, requires PHP 8.1+ (đã OK 8.3) + React 18 (đã OK)
- Filament 3.3 + plugin 3.2 cùng release line
- Intervention Image v3 dùng `ImageManager` + driver explicit (GD hoặc Imagick)
- spatie/laravel-translatable v6 cho Laravel 10

## Requirements

**Functional:** install all deps, không lỗi resolve.
**Non-functional:** lock versions trong composer.json + package.json, no `*` constraints.

## Related Code Files

**Modify:**
- `d:\Data\laragon\www\mamiviet\composer.json`
- `d:\Data\laragon\www\mamiviet\package.json`

**Create:** none

## Implementation Steps

1. Composer:
```bash
composer require inertiajs/inertia-laravel:^2.0
composer require filament/filament:^3.3 -W
composer require spatie/laravel-translatable:^6.0
composer require filament/spatie-laravel-translatable-plugin:^3.2 -W
composer require intervention/image:^3.0
composer require spatie/laravel-sitemap:^7.0
```

2. NPM:
```bash
npm install @inertiajs/react@^2.0 react react-dom
npm install -D @vitejs/plugin-react
```

3. Filament install scaffold:
```bash
php artisan filament:install --panels
```
Tạo `app/Providers/Filament/AdminPanelProvider.php` — config kỹ ở Phase 07.

4. Intervention publish (optional v3 không bắt buộc):
```bash
php artisan vendor:publish --provider="Intervention\Image\Laravel\ServiceProvider"
```

5. Verify:
```bash
composer show inertiajs/inertia-laravel filament/filament spatie/laravel-translatable
npm ls @inertiajs/react
```

6. Update `package.json` scripts:
```json
"scripts": {
  "dev": "vite",
  "build": "vite build && vite build --ssr",
  "lint": "eslint . --ext .tsx,.ts"
}
```

## Todo List

- [ ] Composer require 6 packages
- [ ] NPM install Inertia + plugin
- [ ] `filament:install --panels`
- [ ] Update package.json scripts
- [ ] Verify `composer show` + `npm ls` không lỗi
- [ ] Commit lock files

## Success Criteria

- `composer install` clean exit
- `npm install` clean exit
- `php artisan about` show Filament + Inertia providers
- Không có version conflict warning

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| GD vs Imagick driver missing | Check `php -m`, install ext-gd nếu thiếu |
| Filament conflict với existing controllers | Filament dùng namespace riêng `App\Filament\*`, không conflict |
| Inertia v3 lỡ tay install | Lock `^2.0` strict |

## Quality Loop

Sau phase: `/ck:code-review` → `/simplify` → smoke test `php artisan route:list | grep admin`

## Next Steps

→ Phase 02 (DB schema). Cần Filament class để Resource generate sau.
