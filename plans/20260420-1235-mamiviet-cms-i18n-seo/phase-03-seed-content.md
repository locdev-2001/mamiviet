---
title: "Phase 03 — Seed initial content từ locale JSON"
status: completed
priority: P1
effort: 2h
blockedBy: [02]
completedAt: 2026-04-20
---

## Completion notes

- `database/seeders/PagesSeeder.php`: 2 pages (home id=1, bilder id=2 with EN slug=gallery) + 6 sections home theo thứ tự hero/intro/featured_dishes/gallery_teaser/story/contact_cta
- Key mapping THỰC TẾ (khác pseudo trong plan): hero→`homepage.hero_title`, intro→`homepage.welcome_section.welcome_title/welcome_text`, story→`homepage.welcome_section.second_section.*`, contact→`homepage.contact_section.*`. SEO meta hardcode trong seeder vì JSON FE không có sẵn
- `database/seeders/SiteSettingsSeeder.php`: 18 rows across 5 groups (site, nap, hours, social, seo). KHÔNG động 2 rows IG scraper (instagram_post_scrapper/username=mami.viet, token=...) — preserved
- Đăng ký trong `DatabaseSeeder::run()`
- Idempotent verified: chạy 2x → vẫn 2 pages, 6 sections, 20 settings (18 mới + 2 IG cũ)
- Tinker verify: home translate DE/EN OK, 6 sections đúng order, IG handle preserved
- Bilingual data fallback: nếu DE key thiếu → empty string (theo `data_get` default), không null

## Context Links

- Source: `src/lib/locales/de.json`, `src/lib/locales/en.json`
- Report: `plans/reports/researcher-20260420-filament-i18n-media.md` §3 (section types)

## Overview

Seeder đọc `de.json` + `en.json` của FE hiện tại, convert thành Settings rows + 1 Page (home) + N Sections theo schema 6 types.

## Key Insights

- FE dùng nested keys (e.g. `nav.home`, `hero.title`). Seeder phải map cụ thể keys → section fields.
- Settings phải seed NAP placeholder (user sẽ điền sau qua admin). Brainstorm yêu cầu warning trong admin.
- Seeder idempotent: dùng `updateOrCreate`/`firstOrCreate` để re-run không duplicate.

## Requirements

**Functional:**
- 1 Page slug `home` published
- Sections theo thứ tự FE: hero → intro → featured_dishes → gallery_teaser → story → contact_cta
- Settings: site_name, site_email, site_phone, nap (placeholder), hours (placeholder), social, geo, cuisine="Vietnamese", price_range="€€"

**Non-functional:** chạy nhiều lần không tạo duplicate.

## Related Code Files

**Read:**
- `d:\Data\laragon\www\mamiviet\src\lib\locales\de.json`
- `d:\Data\laragon\www\mamiviet\src\lib\locales\en.json`

**Create:**
- `database/seeders/PagesSeeder.php`
- `database/seeders/SettingsSeeder.php`

**Modify:**
- `database/seeders/DatabaseSeeder.php` (gọi 2 seeders mới)

## Implementation Steps

1. Đọc de.json + en.json, identify keys cho từng section type. Map trong seeder:
```php
// Pseudo
$de = json_decode(file_get_contents(base_path('src/lib/locales/de.json')), true);
$en = json_decode(file_get_contents(base_path('src/lib/locales/en.json')), true);
```

2. PagesSeeder:
```php
$page = Page::updateOrCreate(
    ['id' => 1],
    ['slug' => ['de'=>'home','en'=>'home'], 'status'=>'published',
     'seo' => [
        'de' => ['title'=>$de['seo']['title']??'Mamiviet','description'=>$de['seo']['description']??''],
        'en' => ['title'=>$en['seo']['title']??'Mamiviet','description'=>$en['seo']['description']??''],
     ]]
);

$sections = [
  ['type'=>'hero','order'=>1,'fields'=>['title'=>'hero.title','subtitle'=>'hero.subtitle','cta_label'=>'hero.cta','cta_link'=>'#menu']],
  ['type'=>'intro','order'=>2,'fields'=>['title'=>'intro.title','body'=>'intro.body']],
  ['type'=>'featured_dishes','order'=>3,'fields'=>['title'=>'featured.title','subtitle'=>'featured.subtitle']],
  ['type'=>'gallery_teaser','order'=>4,'fields'=>['title'=>'gallery.title','cta_label'=>'gallery.cta','cta_link'=>'/bilder']],
  ['type'=>'story','order'=>5,'fields'=>['title'=>'story.title','body'=>'story.body']],
  ['type'=>'contact_cta','order'=>6,'fields'=>['title'=>'contact.title','cta_label'=>'contact.cta','cta_link'=>'mailto:info@...']],
];
foreach ($sections as $s) {
    $payload = ['page_id'=>$page->id,'type'=>$s['type'],'order'=>$s['order']];
    foreach ($s['fields'] as $col => $key) {
        $payload[$col] = ['de'=>data_get($de,$key,''),'en'=>data_get($en,$key,'')];
    }
    Section::updateOrCreate(['page_id'=>$page->id,'type'=>$s['type']], $payload);
}
```

3. SettingsSeeder:
```php
$pairs = [
  'site_name' => 'Mamiviet',
  'site_email' => 'info@restaurant-mamiviet.com',
  'site_phone' => '+49 000 0000000',
  'nap' => ['name'=>'Mamiviet','address'=>'TODO','city'=>'Leipzig','zip'=>'TODO','country'=>'DE','phone'=>'+49','lat'=>null,'lng'=>null],
  'hours' => [], // user fills via admin
  'social' => ['instagram'=>'https://instagram.com/mamiviet_restaurant','facebook'=>null],
  'cuisine' => 'Vietnamese',
  'price_range' => '€€',
];
foreach ($pairs as $k=>$v) Settings::set($k,$v);
```

4. DatabaseSeeder:
```php
$this->call([SettingsSeeder::class, PagesSeeder::class]);
```

5. Run:
```bash
php artisan db:seed
php artisan db:seed   # 2nd run — verify idempotent, no duplicates
```

6. Verify:
```bash
php artisan tinker
>>> \App\Models\Page::first()->sections()->count(); // 6
>>> \App\Models\Settings::get('nap');
```

## Todo List

- [ ] Inspect de.json + en.json, document key mapping (1 file ngắn `seeder-key-map.md` trong phase folder if cần)
- [ ] PagesSeeder
- [ ] SettingsSeeder
- [ ] Register trong DatabaseSeeder
- [ ] `db:seed` 2x test idempotent
- [ ] Tinker verify count + translations

## Success Criteria

- 1 page + 6 sections
- 8 settings keys
- Re-run seeder không tạo duplicate
- Tất cả translations DE+EN có data (không null/empty)

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| Locale JSON keys không khớp pseudo map | Đọc thực tế JSON, adjust mapping trước khi code |
| `data_get` return null cho key thiếu → empty translation | Default fallback string `''` + log warning trong seeder |
| NAP placeholder bị deploy lên prod | Phase 07 thêm warning banner trong Filament SettingsPage |

## Quality Loop

`/ck:code-review` seeders → `/simplify` (DRY mapping loop) → smoke test admin show data sau Phase 07.

## Next Steps

→ Phase 04 routing dùng Page model. → Phase 07 Filament hiển thị data đã seed.
