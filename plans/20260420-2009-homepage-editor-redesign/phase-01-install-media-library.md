---
title: "Phase 01 — Cài spatie/laravel-medialibrary + image-optimizer"
status: pending
priority: P1
effort: 0.5h
blockedBy: []
---

## Overview

Cài `spatie/laravel-medialibrary` v11 + `spatie/image-optimizer` làm nền cho auto-compression + responsive conversions.

## Related Code Files

**Create:**
- `database/migrations/{ts}_create_media_table.php` (publish từ package)
- `config/media-library.php` (publish)

**Modify:**
- `composer.json`

## Implementation Steps

1. Install:
```bash
composer require "spatie/laravel-medialibrary:^11.0"
```

2. Publish migration + config:
```bash
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-config"
php artisan migrate
```

3. Verify image-optimizer binaries trên Windows dev (Laragon):
```bash
# Check có sẵn không (không bắt buộc — package sẽ skip nếu thiếu)
where jpegoptim pngquant optipng svgo gifsicle webp
```
Nếu thiếu trên Windows dev → OK, skip (package tự fallback). Production Linux cần cài qua apt.

4. Config `config/media-library.php`:
```php
'image_driver' => env('IMAGE_DRIVER', 'gd'),
'image_optimizers' => [
    Jpegoptim::class => ['-m85', '--strip-all', '--all-progressive'],
    Pngquant::class => ['--force', '--quality=85-90'],
    // giữ defaults còn lại
],
```

5. Ensure `APP_URL` đúng trong `.env` (media URLs dựa vào đó).

## Todo List

- [ ] `composer require spatie/laravel-medialibrary:^11.0`
- [ ] Publish migrations + config
- [ ] `php artisan migrate` → bảng `media` tạo thành công
- [ ] Verify `php artisan about` hiện package loaded

## Success Criteria

- Bảng `media` tồn tại trong DB
- `config/media-library.php` tồn tại
- Không có composer error
- Laravel boot không lỗi

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| Image-optimizer binaries thiếu trên Windows dev | Skip optimizer dev, chỉ quality JPEG/PNG via GD — vẫn giảm size đáng kể. Production install binaries. |
| Package version conflict với Laravel 10 | v11 support Laravel 10.x; nếu conflict fallback v10.x |

## Quality Loop

`/ck:code-review` config + migration → `/simplify` — không có code logic nên nhẹ.
