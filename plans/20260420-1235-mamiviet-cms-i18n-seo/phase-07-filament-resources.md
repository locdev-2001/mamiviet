---
title: "Phase 07 — Filament resources (Settings, Page, Section, InstagramPost)"
status: pending
priority: P1
effort: 6h
blockedBy: [02, 03]
---

## Context Links

- Report: `plans/reports/researcher-20260420-filament-i18n-media.md` §1, §2, §3, §5, §6

## Overview

Filament panel `/admin` config + 3 Resources + 1 Page (Settings). Translatable plugin tab DE/EN. Section RelationManager với conditional fields theo type. InstagramPost read-only + header action "Scrape Now".

## Key Insights

- Plugin `SpatieTranslatablePlugin::make()->defaultLocales(['de','en'])` panel-level (apply tất cả Resources)
- Resource phải `use Translatable;` trait + field `->translatable()`
- Section conditional fields: `Select::live() + visible(fn($get)=>...)` — không dùng Builder field (overkill)
- Settings dùng custom Page (không Resource vì chỉ 1 record per key)
- InstagramPost form không cần edit text (auto-scrape) — chỉ show table + delete + manual scrape button

## Requirements

**Functional:**
- `/admin/login` → User authenticate (existing User model + FilamentUser contract)
- Settings page với sections: Site Identity, NAP (warning banner nếu placeholder), Hours (Repeater), Social
- PageResource: list + edit (slug, status, seo) translatable tabs
- SectionRelationManager bên trong PageResource (Repeater hoặc nested CRUD), reorderable, conditional fields theo type
- InstagramPostResource: table read-only + delete + header action "Scrape now" dispatch job + notification

**Non-functional:** all forms validate, no PHP errors, panel responsive.

## Related Code Files

**Modify:**
- `app/Providers/Filament/AdminPanelProvider.php` (register plugin, color, login)
- `app/Models/User.php` (implement `FilamentUser`)

**Create:**
- `app/Filament/Resources/PageResource.php`
- `app/Filament/Resources/PageResource/Pages/{ListPages,CreatePage,EditPage}.php`
- `app/Filament/Resources/PageResource/RelationManagers/SectionsRelationManager.php`
- `app/Filament/Resources/InstagramPostResource.php`
- `app/Filament/Resources/InstagramPostResource/Pages/ListInstagramPosts.php`
- `app/Filament/Pages/ManageSiteSettings.php`
- `resources/views/filament/pages/manage-site-settings.blade.php`

## Implementation Steps

1. **AdminPanelProvider**:
```php
->plugin(\Filament\SpatieLaravelTranslatablePlugin::make()->defaultLocales(['de','en']))
->colors(['primary'=>Color::Red])
->login()
```

2. **User implements FilamentUser**:
```php
class User extends Authenticatable implements FilamentUser {
    public function canAccessPanel(Panel $panel): bool {
        return str_ends_with($this->email, '@restaurant-mamiviet.com'); // or simpler: true
    }
}
```

3. **PageResource**:
```php
class PageResource extends Resource {
    use Translatable;
    protected static ?string $model = Page::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function form(Form $form): Form {
        return $form->schema([
            TextInput::make('slug')->translatable()->required(),
            Select::make('status')->options(['draft'=>'Draft','published'=>'Published'])->required(),
            Section::make('SEO')->schema([
                TextInput::make('seo.title')->translatable()->maxLength(60),
                Textarea::make('seo.description')->translatable()->maxLength(160),
                FileUpload::make('seo.og_image_path')->image()
                  ->saveUploadedFileUsing(fn($file)=>app(ImageTransformationService::class)->processImage($file,'og')),
            ])->collapsible(),
        ]);
    }

    public static function table(Table $table): Table {
        return $table->columns([
            TextColumn::make('slug')->translatable(),
            BadgeColumn::make('status'),
            TextColumn::make('updated_at')->dateTime(),
        ])->actions([EditAction::make()]);
    }

    public static function getRelations(): array { return [SectionsRelationManager::class]; }
}
```

4. **SectionsRelationManager** — copy from research §3:
```php
class SectionsRelationManager extends RelationManager {
    use Translatable;
    protected static string $relationship = 'sections';
    public function form(Form $form): Form {
        return $form->schema([
            Select::make('type')->options([
                'hero'=>'Hero','intro'=>'Intro','featured_dishes'=>'Featured Dishes',
                'gallery_teaser'=>'Gallery Teaser','story'=>'Story','contact_cta'=>'Contact CTA',
            ])->required()->live(),
            TextInput::make('order')->numeric()->required(),

            TextInput::make('title')->translatable()->required(),
            TextInput::make('subtitle')->translatable()
                ->visible(fn($get)=>in_array($get('type'),['hero','intro','featured_dishes','story'])),
            Textarea::make('body')->translatable()
                ->visible(fn($get)=>in_array($get('type'),['intro','story'])),
            FileUpload::make('image_path')->image()
                ->visible(fn($get)=>in_array($get('type'),['hero','featured_dishes','gallery_teaser','story']))
                ->saveUploadedFileUsing(fn($file)=>app(ImageTransformationService::class)->processImage($file,'sections')),
            TextInput::make('cta_label')->translatable()
                ->visible(fn($get)=>in_array($get('type'),['hero','featured_dishes','contact_cta'])),
            TextInput::make('cta_link')
                ->visible(fn($get)=>in_array($get('type'),['hero','featured_dishes','contact_cta'])),

            // Type-specific data fields (json):
            KeyValue::make('data')->visible(fn($get)=>$get('type')==='featured_dishes'),
        ]);
    }
    public function table(Table $table): Table {
        return $table->columns([TextColumn::make('order'),TextColumn::make('type')->badge(),TextColumn::make('title')])
            ->reorderable('order')->defaultSort('order')->paginated(false);
    }
}
```

5. **ManageSiteSettings** — copy from research §5, add NAP warning:
```php
Section::make('NAP')->description(fn()=>str_contains(Settings::get('nap.address',''),'TODO')
    ? '⚠ Vui lòng cập nhật địa chỉ thực — schema.org cần cho SEO local'
    : null)
->schema([...]);
```

6. **InstagramPostResource**:
```php
class InstagramPostResource extends Resource {
    protected static ?string $model = InstagramPost::class;
    public static function form(Form $form): Form {
        return $form->schema([
            TextInput::make('caption')->disabled(),
            TextInput::make('image_url')->disabled(),
        ]);
    }
    public static function table(Table $table): Table {
        return $table->columns([
            ImageColumn::make('image_url'),
            TextColumn::make('caption')->limit(50),
            TextColumn::make('posted_at')->dateTime(),
        ])->headerActions([
            Action::make('scrapeNow')->label('Scrape Now')->icon('heroicon-o-arrow-path')
                ->action(function() {
                    \App\Jobs\ScrapeInstagramPostsJob::dispatch();
                    Notification::make()->title('Job dispatched')
                        ->body('Posts will appear in 1-2 minutes')->success()->send();
                }),
        ])->actions([DeleteAction::make()]);
    }
}
```

7. Create admin user nếu chưa có:
```bash
php artisan make:filament-user
```

8. Smoke test:
```bash
php artisan serve
# visit http://mamiviet.test/admin → login → see 3 resources + Settings page
```

## Todo List

- [ ] AdminPanelProvider plugin + color
- [ ] User implements FilamentUser
- [ ] PageResource + 3 Pages
- [ ] SectionsRelationManager với conditional visibility
- [ ] InstagramPostResource read-only + scrape action
- [ ] ManageSiteSettings page + NAP warning
- [ ] make:filament-user
- [ ] Smoke test all CRUD: create page, add 6 sections, edit DE+EN tab, save Settings
- [ ] Verify FE re-render sau khi edit (Phase 05 controllers đọc updated data)

## Success Criteria

- Admin login works
- Switch DE↔EN tab in form preserves state
- Section conditional fields show/hide đúng theo type
- Save Section → image processed (Phase 08 verify), DB updated
- Settings save → FE NAP/social cập nhật

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| Translatable plugin conflict với SEO nested fields (`seo.title`) | Test thực tế; nếu fail dùng nested form: `Section('SEO')->schema(TextInput::make('title')->translatable())` với separate `seo_title` column hoặc keep nested via plugin docs |
| FileUpload hook fire trước service Phase 08 ready | Phase 08 phải xong trước user upload thật; OK upload "tạm" plain Phase 07 |
| NAP warning hardcoded `TODO` string | Refactor: check `nap.address === null || nap.zip === 'TODO'` cleaner |
| Filament panel xung đột middleware setLocale | Filament dùng panel middleware riêng, không apply web `setLocale` |

## Quality Loop

`/ck:code-review` Resources + RelationManager + ManageSiteSettings → `/simplify` (extract section visibility map vào constant `SECTION_FIELDS_MAP`) → end-to-end smoke (create page → add section → edit FE refresh).

## Next Steps

→ Phase 08 wire ImageTransformationService (FileUpload hook đã ref nhưng service chưa exist). → Phase 09 ScrapeInstagramPostsJob class.
