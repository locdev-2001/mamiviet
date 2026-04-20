---
title: "Phase 03 — Filament Homepage Editor (tabs per section)"
status: pending
priority: P1
effort: 3h
blockedBy: [02]
---

## Overview

Thay `SectionsRelationManager` bằng custom Page `HomepageEditor` (hoặc custom EditPage trong PageResource) hiển thị **1 tab cho mỗi section** với đúng field cần. Admin không cần hiểu metadata, không còn dropdown `type`.

## UX

- Navigation: "Homepage" (icon home), group "Content"
- Page hiển thị 8 tabs: Hero / Welcome / Welcome 2 / Order / Reservation / Contact / Gallery / Intro
- Mỗi tab:
  - Toggle `enabled` ở đầu
  - Tabs locale (DE / EN) cho các field text
  - `SpatieMediaLibraryFileUpload` cho media (1 hoặc N theo section)
  - Reorder `order` ẩn — dùng default theo catalog
- Button "Save" duy nhất — save all sections 1 lần.

## Related Code Files

**Create:**
- `app/Filament/Pages/HomepageEditor.php`
- `resources/views/filament/pages/homepage-editor.blade.php` (dùng Filament's `form` component)
- `app/Filament/Support/HomepageSectionSchema.php` — map `key → field schema` (DRY source of truth)

**Delete:**
- `app/Filament/Resources/PageResource/RelationManagers/SectionsRelationManager.php`

**Modify:**
- `app/Filament/Resources/PageResource.php` — `getRelations()` trả `[]` (giữ SEO/slug editor riêng)
- Có thể giấu `PageResource` navigation nếu chỉ có 1 page "home" → `shouldRegisterNavigation = false`

## Implementation

### HomepageSectionSchema (single source of truth)

```php
class HomepageSectionSchema {
    public const CATALOG = [
        'hero' => [
            'label' => 'Hero',
            'fields' => ['title'],
            'media' => ['bg' => ['multiple' => false]],
        ],
        'welcome' => [
            'label' => 'Welcome',
            'fields' => ['brand_name','tagline','cuisine_label','title','body','cta_label'],
            'media'  => ['main' => ['multiple'=>false], 'overlay' => ['multiple'=>false]],
        ],
        // ... 8 entries
    ];

    public static function formSchema(string $key): array { /* build Filament fields */ }
}
```

### HomepageEditor page

```php
class HomepageEditor extends Page implements HasForms {
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationGroup = 'Content';
    protected static string $view = 'filament.pages.homepage-editor';

    public array $data = [];

    public function mount(): void {
        $page = Page::firstWhere(fn($q)=>$q->whereJsonContains('slug->de','home'));
        foreach ($page->sections as $s) {
            $this->data[$s->key] = [
                'enabled' => $s->enabled,
                'content_de' => $s->getTranslation('content','de',false) ?? [],
                'content_en' => $s->getTranslation('content','en',false) ?? [],
                'data' => $s->data ?? [],
            ];
        }
        $this->form->fill(['sections' => $this->data]);
    }

    protected function getFormSchema(): array {
        return [Tabs::make('Sections')->tabs(
            collect(HomepageSectionSchema::CATALOG)->map(fn($def,$key)=>
                Tabs\Tab::make($def['label'])->schema([
                    Toggle::make("sections.{$key}.enabled")->label('Hiển thị section này'),
                    Tabs::make('Locale')->tabs([
                        Tabs\Tab::make('Deutsch')->schema(self::textFields($key,'de')),
                        Tabs\Tab::make('English')->schema(self::textFields($key,'en')),
                    ])->visible(fn(Get $get)=>$get("sections.{$key}.enabled")),
                    ...self::mediaFields($key),  // SpatieMediaLibraryFileUpload per collection
                ])
            )->values()->all()
        )->columnSpanFull()];
    }

    public function save(): void {
        $data = $this->form->getState()['sections'];
        $page = Page::firstWhere(...);
        foreach ($data as $key => $sectionData) {
            $section = $page->sections()->firstOrCreate(['key'=>$key]);
            $section->enabled = $sectionData['enabled'];
            $section->setTranslation('content','de',$sectionData['content_de']);
            $section->setTranslation('content','en',$sectionData['content_en']);
            $section->data = $sectionData['data'] ?? null;
            $section->save();
        }
        Notification::make()->success()->title('Homepage đã lưu')->send();
    }
}
```

### Media fields — dùng Spatie's Filament plugin

```bash
composer require filament/spatie-laravel-media-library-plugin:"^3"
```

```php
SpatieMediaLibraryFileUpload::make('bg')
    ->collection('bg')
    ->image()
    ->imageEditor()
    ->reorderable(fn()=>$record->key==='gallery_slider')   // drag reorder cho gallery
    ->multiple(fn()=>$record->key==='gallery_slider')
    ->customProperties(fn(Get $get) => [
        'alt' => [
            'de' => $get('_alt_de'),   // bind qua hidden fields
            'en' => $get('_alt_en'),
        ],
    ])
    ->acceptedFileTypes(['image/jpeg','image/png','image/webp'])
    ->maxSize(8 * 1024)                                   // 8MB
    ->helperText('Tự nén WebP + sinh 4 kích thước 480/768/1280/1920 cho SEO')
```

### Alt text per-image (SEO requirement)

Mỗi media item phải có alt DE + EN riêng. Cách đơn giản: **KeyValue field phụ** bên cạnh upload (cho single-image sections), hoặc **custom form component** cho gallery (mỗi ảnh 1 card có 2 input alt). Khi save → loop `$section->getMedia($collection)` → `setCustomProperty('alt', ['de'=>..., 'en'=>...])` → `save()`.

Placeholder alt (nếu admin bỏ trống) = `"{section.content.title} - Mamiviet"` cho đỡ trống, nhưng warning trong UI yêu cầu nhập thủ công để SEO tốt.

**Challenge**: 1 form quản lý 8 sections — media upload cần bind per-section record. Cách xử lý: dùng `Repeater` với custom records, hoặc dễ hơn là tạo riêng page **per-section** (`/admin/homepage/hero`, `/admin/homepage/welcome`, ...).

### Simpler alternative (recommended)

Thay vì 1 page editor monster → tạo **1 Filament Resource `HomepageSectionResource`** với listing 8 rows (không create/delete), edit page custom form theo `key`:

- List view: 8 rows readonly → "Hero", "Welcome", ... với badge enabled/disabled + edit action
- Edit view: form động theo `$record->key` dùng `HomepageSectionSchema::formSchema($key)`
- Media fields bind `$record` trực tiếp → upload works out-of-box
- Seed luôn 8 rows, không cho create/delete

Đây là cách DRY + đơn giản nhất. Dùng approach này.

## Todo List

- [ ] Install `filament/spatie-laravel-media-library-plugin`
- [ ] Create `HomepageSectionSchema` class — CATALOG + `formSchema(key)` + `mediaFields(key)`
- [ ] Create `HomepageSectionResource` — list readonly, edit page custom form
- [ ] Delete `SectionsRelationManager`
- [ ] `PageResource::getRelations()` → `[]`; optional hide page navigation
- [ ] Translatable tabs DE/EN cho content fields
- [ ] SpatieMediaLibraryFileUpload theo `$section->key` — single/multiple
- [ ] `enabled` toggle + `visible()` trên content tabs
- [ ] Smoke test: edit Hero title, upload image, toggle disable, save, refresh admin → persisted

## Success Criteria

- Admin `/admin` thấy nav "Homepage Sections" list 8 rows
- Edit Hero → form chỉ có `title` + upload `bg` (không có fields thừa)
- Edit Gallery → multiple image upload + reorder
- Toggle enabled off → UI không render section đó (verified ở Phase 05)
- Save → DB `sections` row cập nhật + media library files tạo conversions

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| Translatable content JSON nested không work với `SpatieTranslatablePlugin` | Không dùng plugin; self-manage như PageResource hiện tại (locale tabs thủ công). |
| Media upload size / timeout | `php.ini upload_max_filesize / post_max_size`. Dev OK. Conversion `nonQueued` để preview ngay, production có thể queue. |
| Filament v3 field names với dot (`sections.hero.content_de.title`) | Dùng `statePath` hoặc nested form — test kỹ. |

## Quality Loop

`/ck:code-review` Schema class + Resource edit page → `/simplify` (đảm bảo CATALOG là single source, không hardcode duplicate) → smoke test end-to-end.
