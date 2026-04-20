---
title: "Phase 02 — Redesign `sections` schema + seed từ locale JSON"
status: pending
priority: P1
effort: 1.5h
blockedBy: [01]
---

## Overview

Drop bảng `sections` cũ, recreate với schema key-based (thay vì type-based). Section model integrate spatie media library. Seed data từ `src/lib/locales/de.json` + `en.json` cho đủ 8 section keys.

## Schema mới

```
sections
├─ id
├─ page_id (FK pages)
├─ key (string, unique per page)        # hero, welcome, welcome_second, order, reservation, contact, gallery_slider, intro
├─ enabled (bool, default true)
├─ order (int)
├─ content (json, translatable via HasTranslations với array cast)
├─ data (json, non-translatable — ví dụ map_embed_url, instagram_url)
├─ timestamps
└─ unique(page_id, key)
```

Bỏ các column rời (`title`/`subtitle`/`body`/`cta_label`/`cta_link`/`image_path`) — tất cả text gộp vào `content` JSON. Ảnh quản lý qua spatie media collections, không còn `image_path` column.

### Content shape theo key

```php
// hero
['title' => 'string']

// welcome + welcome_second
['brand_name','tagline','cuisine_label','title','body','cta_label']

// order
['title','takeaway','delivery','reservation','free_delivery','cta_label']

// reservation
['title','subtitle','note','cta_label','overlay_text']

// contact
['title','restaurant_name','address','phone','email','instagram_label','overlay_text']

// gallery_slider
['title','subtitle']

// intro
['title','text1','text2']
```

### Media collections + responsive conversions (SEO)

Mỗi ảnh upload → auto-sinh **5 WebP variants** ở breakpoints chuẩn Tailwind cho `<picture srcset>`:

| Conversion | Width | Use case |
|---|---|---|
| `w480`  | 480px  | mobile portrait |
| `w768`  | 768px  | tablet |
| `w1280` | 1280px | desktop |
| `w1920` | 1920px | large / retina / LCP |
| `thumb` | 400px  | admin preview |

Không upscale (skip conversion nếu source < breakpoint). Tất cả output `webp` quality 82–85 + `image_optimizer` strip metadata.

```php
// Section model
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Section extends Model implements HasMedia {
    use HasMedia, InteractsWithMedia, HasTranslations;

    private const SINGLE_COLLECTIONS_BY_KEY = [
        'hero'           => ['bg'],
        'welcome'        => ['main', 'overlay'],
        'welcome_second' => ['main', 'overlay'],
        'order'          => ['left', 'right'],
        'reservation'    => ['image'],
        'contact'        => ['image'],
    ];
    private const MULTI_COLLECTIONS_BY_KEY = [
        'gallery_slider' => ['images'],
    ];

    public function registerMediaCollections(): void {
        foreach (self::SINGLE_COLLECTIONS_BY_KEY[$this->key] ?? [] as $name) {
            $this->addMediaCollection($name)
                ->singleFile()
                ->acceptsMimeTypes(['image/jpeg','image/png','image/webp']);
        }
        foreach (self::MULTI_COLLECTIONS_BY_KEY[$this->key] ?? [] as $name) {
            $this->addMediaCollection($name)
                ->acceptsMimeTypes(['image/jpeg','image/png','image/webp']);
        }
    }

    public function registerMediaConversions(?Media $media = null): void {
        foreach ([480, 768, 1280, 1920] as $width) {
            $this->addMediaConversion("w{$width}")
                ->format('webp')->quality(85)
                ->width($width)->withoutEnlarging()
                ->optimize()->nonQueued();
        }
        $this->addMediaConversion('thumb')
            ->format('webp')->quality(82)
            ->width(400)->withoutEnlarging()
            ->optimize()->nonQueued();
    }
}
```

### Alt text + SEO metadata per media

Alt text **phải editable per locale** (không dùng section title fallback — không đủ cụ thể cho SEO). Dùng spatie custom properties:

```php
// Khi upload qua Filament:
$section->addMedia($file)->toMediaCollection('bg');
// Sau đó user edit alt trong form → save vào custom_properties:
$media->setCustomProperty('alt', ['de' => '...', 'en' => '...']);
$media->save();
```

Filament form có field `TextInput::make('custom_properties.alt.de')` + `custom_properties.alt.en` (dùng `customProperties()` helper của plugin).

### Dimensions (chống CLS)

Spatie tự lưu `manipulations`/`custom_properties` `width` + `height` gốc qua `Media::$generated_conversions` + width/height của file gốc có sẵn ở `$media->getCustomProperty('width')` (nếu config `remember_media_dimensions = true`). Nếu không có → tính 1 lần lúc upload bằng observer và lưu vào `custom_properties`.

## Related Code Files

**Create:**
- `database/migrations/{ts}_redesign_sections_table.php`
- `database/seeders/HomepageSectionsSeeder.php`

**Modify:**
- `app/Models/Section.php` — bỏ old fields, add `HasMedia`, `content`/`data` JSON translatable
- `app/Models/Page.php` — không đổi

**Delete later** (Phase 03):
- `app/Filament/Resources/PageResource/RelationManagers/SectionsRelationManager.php`

## Implementation Steps

1. **Migration**: drop & recreate sections (data chỉ là test nên OK)
```php
Schema::dropIfExists('sections');
Schema::create('sections', function (Blueprint $t) {
    $t->id();
    $t->foreignId('page_id')->constrained()->cascadeOnDelete();
    $t->string('key');
    $t->boolean('enabled')->default(true);
    $t->integer('order')->default(0);
    $t->json('content')->nullable();
    $t->json('data')->nullable();
    $t->timestamps();
    $t->unique(['page_id','key']);
    $t->index(['page_id','order']);
});
```

2. **Section model** refactor:
```php
use HasTranslations, InteractsWithMedia;
implements HasMedia

protected $fillable = ['page_id','key','enabled','order','content','data'];
public array $translatable = ['content'];
protected $casts = ['content'=>'array','data'=>'array','enabled'=>'bool','order'=>'int'];

public const KEYS = ['hero','welcome','welcome_second','order','reservation','contact','gallery_slider','intro'];
```

3. **HomepageSectionsSeeder** — đọc trực tiếp từ `src/lib/locales/de.json` + `en.json`, map theo cấu trúc `homepage.xxx` hiện tại, tạo 8 sections cho page `home`.

4. Chạy:
```bash
php artisan migrate:refresh --seed --path=database/migrations/{ts}_redesign_sections_table.php
# hoặc
php artisan migrate
php artisan db:seed --class=HomepageSectionsSeeder
```

5. Verify qua tinker:
```bash
php artisan tinker
>>> Page::first()->sections->pluck('key')
# ['hero','welcome','welcome_second','order','reservation','contact','gallery_slider','intro']
>>> Section::where('key','welcome')->first()->getTranslation('content','de')
```

## Todo List

- [ ] Migration drop + recreate `sections`
- [ ] Refactor `Section.php` — HasMedia + single/multi collections + 5 conversions (w480/w768/w1280/w1920/thumb)
- [ ] Observer/hook lưu `width`+`height` gốc vào `Media::custom_properties` (chống CLS)
- [ ] HomepageSectionsSeeder đọc locale JSON → insert 8 sections translatable
- [ ] `php artisan migrate` + seed
- [ ] Tinker verify 8 keys + content DE/EN đầy đủ
- [ ] Upload test 1 ảnh → verify `/storage/{id}/conversions/` có đủ 5 WebP + optimized size thật sự nhỏ hơn source

## Success Criteria

- 8 rows tồn tại với đúng `key`, `order`, `content` DE+EN
- Model `Section::media()` relation available, conversions register được
- Không còn reference tới column cũ (`type`, `title`, `image_path`, ...)

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| `src/lib/locales/*.json` structure thay đổi sau này | Seeder chỉ dùng 1 lần; sau seed admin sửa qua UI. Seeder để lại cho reset. |
| Translatable JSON trong JSON (content là array + wrapped locale) | Spatie translatable lưu `{"de":{...},"en":{...}}`. `content` cast `array` + trait xử lý tự động. Test kỹ ở tinker. |
| Media collection `gallery_slider.images` không single → unlimited uploads | OK, ý đồ như vậy. Reorder qua `order_column` của spatie. |

## Quality Loop

`/ck:code-review` migration + Section model + seeder → `/simplify` (extract KEYS constant, DRY conversion def) → verify tinker.
