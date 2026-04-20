---
title: "Phase 02 — DB schema (pages, sections, settings)"
status: completed
priority: P1
effort: 2h
blockedBy: [01]
completedAt: 2026-04-20
---

## Completion notes

- Tạo `pages` (id, json slug, enum status draft/published, json seo) + `sections` (page_id FK cascade, enum type 6 giá trị, order int, json title/subtitle/body/cta_label/cta_link/image_path/data, index page_id+order)
- KHÔNG động `settings` table — giữ schema cũ group/key/value vì có credentials IG scraper live (instagram_post_scrapper/username=mami.viet, token=...). Phase 07 SettingsPage sẽ dùng group-based namespacing
- `app/Models/Page.php` + `app/Models/Section.php` với HasTranslations, fillable, Section::TYPES const
- Bỏ `seo => array` cast trong Page (spatie/translatable v6 tự xử lý JSON per-locale, cast gây double-decode) — fix theo code-review
- Tinker verify: Page+Section translate DE/EN OK, SEO array per-locale OK
- Code review report: `plans/20260420-1235-mamiviet-cms-i18n-seo/reports/code-review-phase-02-db-schema.md`
- Defer (YAGNI): slug uniqueness → Phase 04 validation, factories → khi cần test, soft deletes → khi admin UX yêu cầu

## Context Links

- Report: `plans/reports/researcher-20260420-filament-i18n-media.md` §3, §10
- Report: `plans/reports/brainstorm-20260420-1235-cms-i18n-seo-filament.md` "Data model"

## Overview

Migrations + Eloquent models cho `pages`, `sections`, `settings` (mở rộng). Translatable fields dùng JSON columns.

## Key Insights

- spatie/laravel-translatable lưu mỗi translatable field thành JSON `{"de":"...","en":"..."}`
- `sections.data` dùng JSON cho extra schema theo type (không translatable, gồm cấu hình như `dishes_count`, `gallery_layout`)
- `image_path` lưu JSON output từ ImageTransformationService `{ original, variants: { w480, ... } }`
- `settings.key` unique, `data` JSON; helper `Settings::get/set` static

## Requirements

**Functional:**
- pages: `id, slug (translatable), status (draft|published), seo (JSON translatable: title/description/og_image_path), timestamps`
- sections: `id, page_id FK cascade, type ENUM(6), order int, title/subtitle/body/cta_label/cta_link (translatable), image_path JSON, data JSON, timestamps`
- settings: `id, key UNIQUE, data JSON` (đã có thì alter; chưa có thì create)

**Non-functional:** all FK cascade on delete; indexes trên `(page_id, order)`.

## Architecture

```
pages (1) ──< (N) sections
                    │
                    └─ image_path → ImageTransformationService output
settings (singleton key→value JSON)
instagram_posts (đã có)
```

## Related Code Files

**Create:**
- `database/migrations/2026_04_20_120000_create_pages_table.php`
- `database/migrations/2026_04_20_120100_create_sections_table.php`
- `database/migrations/2026_04_20_120200_create_or_update_settings_table.php`
- `app/Models/Page.php`
- `app/Models/Section.php`
- `app/Models/Settings.php`

**Check existing:** grep `database/migrations/` cho `settings`, `pages` để tránh duplicate.

## Implementation Steps

1. Pages migration:
```php
Schema::create('pages', function (Blueprint $table) {
    $table->id();
    $table->json('slug'); // {"de":"home","en":"home"}
    $table->enum('status', ['draft','published'])->default('draft');
    $table->json('seo')->nullable(); // {"de":{"title":"...","description":"...","og_image_path":"..."},"en":{...}}
    $table->timestamps();
});
```

2. Sections migration:
```php
Schema::create('sections', function (Blueprint $table) {
    $table->id();
    $table->foreignId('page_id')->constrained()->cascadeOnDelete();
    $table->enum('type', ['hero','intro','featured_dishes','gallery_teaser','story','contact_cta']);
    $table->integer('order')->default(0);
    $table->json('title')->nullable();
    $table->json('subtitle')->nullable();
    $table->json('body')->nullable();
    $table->json('cta_label')->nullable();
    $table->json('cta_link')->nullable();
    $table->json('image_path')->nullable();
    $table->json('data')->nullable();
    $table->timestamps();
    $table->index(['page_id','order']);
});
```

3. Settings migration (check existing trước):
```php
if (!Schema::hasTable('settings')) {
    Schema::create('settings', function (Blueprint $table) {
        $table->id();
        $table->string('key')->unique();
        $table->json('data')->nullable();
    });
}
```

4. Page model:
```php
use Spatie\Translatable\HasTranslations;
class Page extends Model {
    use HasTranslations;
    public $translatable = ['slug','seo'];
    protected $casts = ['seo'=>'array'];
    public function sections(){ return $this->hasMany(Section::class)->orderBy('order'); }
}
```

5. Section model:
```php
use Spatie\Translatable\HasTranslations;
class Section extends Model {
    use HasTranslations;
    public $translatable = ['title','subtitle','body','cta_label','cta_link'];
    protected $casts = ['image_path'=>'array','data'=>'array'];
    public function page(){ return $this->belongsTo(Page::class); }
}
```

6. Settings model — copy from report §5 (static `get/set`).

7. Run migrations:
```bash
php artisan migrate
```

8. Verify:
```bash
php artisan tinker
>>> \App\Models\Page::create(['slug'=>['de'=>'home','en'=>'home'],'status'=>'published']);
>>> \App\Models\Page::first()->getTranslation('slug','en');
```

## Todo List

- [ ] Migration pages
- [ ] Migration sections
- [ ] Migration settings (conditional)
- [ ] Models Page/Section/Settings
- [ ] `php artisan migrate` success
- [ ] Tinker verify translatable read/write
- [ ] Rollback test: `migrate:rollback --step=3`

## Success Criteria

- `php artisan migrate` clean
- Tinker create + read translation works DE & EN
- Rollback không lỗi FK

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| Existing `settings` table conflict | Conditional `Schema::hasTable` + ALTER nếu schema khác |
| `slug` translatable → conflict route lookup | Phase 04 dùng `Page::whereJsonContains('slug->de', $slug)` |
| ENUM type cứng nhắc | OK cho 6 types fixed; cần thêm thì migration mới |

## Quality Loop

`/ck:code-review` migrations + models → `/simplify` (DRY casts) → tinker smoke test.

## Next Steps

→ Phase 03 (seed) cần models. → Phase 04 routing + Phase 07 Filament resources cùng dùng schema này.
