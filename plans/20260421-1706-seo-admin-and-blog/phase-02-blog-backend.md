# Phase 02 — Blog backend (Post model + Filament resource)

## Context Links

- [plan.md](plan.md)
- [phase-01-seo-admin-homepage.md](phase-01-seo-admin-homepage.md)
- Pattern tham khảo:
  - [app/Filament/Resources/HomepageSectionResource.php](../../app/Filament/Resources/HomepageSectionResource.php)
  - [app/Models/Page.php](../../app/Models/Page.php) (translatable slug pattern)

## Overview

- **Priority**: Cao
- **Status**: Pending
- Tạo Post model + Filament resource để admin viết bài blog song ngữ de/en với rich editor, SEO per-post, media gallery.

## Key Insights

- `spatie/laravel-translatable` đã cài → dùng `HasTranslations` cho title/slug/excerpt/content/seo_*
- `filament/spatie-laravel-translatable-plugin` đã cài → dùng `LocaleSwitcher` + `->translatable()` trên form/table
- `spatie-laravel-media-library-plugin` đã cài → cover image + gallery qua `SpatieMediaLibraryFileUpload`
- Rich editor: **`awcodes/filament-tiptap-editor:^3.0`** (branch 3.x) — xác nhận stable với Filament 3.3 qua context7
- **Output HTML** (default) — không dùng custom blocks (chỉ JSON output mới support) → đơn giản, đủ cho MVP
- **Yêu cầu phụ**: `postcss.config.js` phải có `tailwindcss/nesting` plugin (tiptap CSS compile)
- Slug translatable: cần **generated column + UNIQUE INDEX per locale** (MySQL 8), không dùng functional index (không enforce unique)
- Status (draft/published) + `published_at` để lên lịch bài
- **Draft preview**: temporary signed URL từ Filament EditPost header action

## Requirements

**Functional**
- CRUD bài viết qua Filament (list, create, edit, delete, restore)
- Fields translatable: title, slug, excerpt, content, seo_title, seo_description, seo_keywords
- Fields non-translatable: status (draft/published/scheduled), published_at, author_id, cover_image, og_image, reading_time (auto-computed)
- Auto-generate slug từ title khi trống (slugify per locale)
- Rich editor với: bold/italic/heading/list/quote/link/image/video embed/table/code
- Upload ảnh inline qua tiptap (disk `public`, directory `posts/content`, URL lưu tương đối)
- Filter bài theo status, search theo title
- **Draft preview** qua signed URL (header action "Preview" trong Filament EditPost → generate `URL::temporarySignedRoute` expire 1h)
- **Reading time**: single value (int, đơn vị phút) tính theo content locale `de` làm primary (de thường dài hơn en)

**Non-functional**
- Query list post < 100ms với 1000 bài (index status + published_at)
- Sanitize HTML content trước khi lưu (allowlist tags)
- Reading time tự tính: `ceil(word_count / 200)` phút
- Cover image tối ưu: 1200×630 (OG ratio), auto resize qua media library conversions

## Architecture

```
Admin Filament
  └─> PostResource (form + table)
       ├─> Tab "Nội dung"
       │    ├─ title, slug (translatable)
       │    ├─ excerpt (translatable textarea)
       │    └─ content (tiptap editor, translatable)
       ├─> Tab "SEO"
       │    ├─ seo_title, seo_description, seo_keywords (translatable)
       │    └─ og_image (media upload)
       └─> Tab "Xuất bản"
            ├─ status (draft/published/scheduled)
            ├─ published_at (DateTimePicker)
            ├─ author_id (Select users)
            └─ cover_image (media upload)
                ↓ save
            posts table
            ├─ id, author_id
            ├─ status, published_at
            ├─ title, slug, excerpt, content (JSON translatable)
            ├─ seo_title, seo_description, seo_keywords (JSON translatable)
            ├─ og_image, reading_time
            ├─ timestamps, deleted_at
            └─ media (spatie media library — cover + og + inline)
```

## Related Code Files

**Create:**
- `database/migrations/2026_04_21_XXXXXX_create_posts_table.php`
- `app/Models/Post.php`
- `app/Filament/Resources/PostResource.php`
- `app/Filament/Resources/PostResource/Pages/ListPosts.php`
- `app/Filament/Resources/PostResource/Pages/CreatePost.php`
- `app/Filament/Resources/PostResource/Pages/EditPost.php`
- `app/Support/HtmlSanitizer.php` — wrapper quanh `mews/purifier` hoặc DOMDocument allowlist
- `database/seeders/PostSeeder.php` — 2-3 bài demo

**Modify:**
- `composer.json` — thêm `awcodes/filament-tiptap-editor:^3.0`, `mews/purifier`
- `config/purifier.php` — allowlist tags (bao gồm tiptap blocks)
- `postcss.config.js` — thêm `tailwindcss/nesting` plugin
- `app/Providers/Filament/AdminPanelProvider.php` — register tiptap plugin nếu cần
- `routes/web.php` — thêm route `/blog/preview/{post}` middleware `signed`

## Implementation Steps

### 1. Cài dependencies

```bash
composer require awcodes/filament-tiptap-editor:"^3.0"
composer require mews/purifier
php artisan vendor:publish --tag="filament-tiptap-editor-config"
php artisan vendor:publish --provider="Mews\Purifier\PurifierServiceProvider"

npm i html-react-parser dompurify
npm i -D @types/dompurify
```

**Update `postcss.config.js`**:
```js
module.exports = {
  plugins: {
    'tailwindcss/nesting': {},
    tailwindcss: {},
    autoprefixer: {},
  },
}
```

### 2. Migration `create_posts_table`

Columns:
- `id`
- `status` enum: `draft | published | scheduled` default `draft`, **index**
- `published_at` timestamp nullable, **index**
- `title`, `slug`, `excerpt`, `content`, `seo_title`, `seo_description`, `seo_keywords` — JSON (translatable)
- `og_image` string nullable
- `reading_time` unsignedSmallInteger default 0
- `timestamps`, `softDeletes`

**Ghi chú**: bỏ `author_id` — chỉ owner viết, tác giả hiển thị hard-code "Mamiviet" (hoặc `Setting::raw('footer.company_name')`).

Indexes:
- Composite: `(status, published_at)` cho list published
- **Generated column + UNIQUE INDEX cho slug per locale** (MySQL 8):

```php
DB::statement("
    ALTER TABLE posts
    ADD COLUMN slug_de VARCHAR(200) GENERATED ALWAYS AS (JSON_UNQUOTE(slug->'$.de')) VIRTUAL,
    ADD COLUMN slug_en VARCHAR(200) GENERATED ALWAYS AS (JSON_UNQUOTE(slug->'$.en')) VIRTUAL
");
DB::statement("ALTER TABLE posts ADD UNIQUE INDEX uniq_slug_de (slug_de, deleted_at)");
DB::statement("ALTER TABLE posts ADD UNIQUE INDEX uniq_slug_en (slug_en, deleted_at)");
```

**Ghi chú soft delete**: index bao gồm `deleted_at` để soft-deleted post không block slug (khi restore có thể conflict nhưng admin restore phải handle trước).

**Lưu ý**: bỏ `view_count` (racy GET + dễ bot inflate) — nếu cần analytics, dùng Plausible/Umami ngoài.

### 3. `Post` model

- `use HasTranslations, HasFactory, SoftDeletes, InteractsWithMedia`
- `public $translatable = ['title', 'slug', 'excerpt', 'content', 'seo_title', 'seo_description', 'seo_keywords'];`
- `casts`: `published_at => datetime`, `reading_time => int`
- Scopes:
  - `scopePublished()`: status=published AND published_at <= now()
  - `scopeForLocale($locale)`: `whereRaw("JSON_UNQUOTE(slug->'$.{$locale}') IS NOT NULL")`
- Helper:
  - `saving` event: compute `reading_time` từ `content['de']` word count / 200
  - `saving` event: sanitize `content` per locale qua `HtmlSanitizer`
  - `saving` event: normalize tiptap media URL — regex replace `config('app.url') . '/storage/'` → `/storage/` (URL tương đối)
  - `registerMediaCollections()`: `cover` (single, 1200×630), `og` (single)
  - `registerMediaConversions()`: `thumb` 400×250, `card` 800×500, `hero` 1600×900
  - (Không có relation author — chỉ owner)

### 4. `PostResource` (Filament)

**Form** (3 tabs):

Tab 1 — Nội dung:
```php
TextInput::make('title')->required()->maxLength(180)->translatable()
    ->live(onBlur: true)
    ->afterStateUpdated(fn($state, $set, $get) => $set('slug', Str::slug($state)))

TextInput::make('slug')->required()->maxLength(200)->translatable()
    ->rules(['regex:/^[a-z0-9-]+$/', 'unique_translatable_slug'])

Textarea::make('excerpt')->maxLength(300)->rows(3)->translatable()

TiptapEditor::make('content')->profile('default')->required()
    ->extraInputAttributes(['style' => 'min-height: 400px'])
    ->translatable()
    ->afterStateUpdated(fn($state, $set) => $set('reading_time', self::calcReadingTime($state)))
```

Tab 2 — SEO:
```php
TextInput::make('seo_title')->maxLength(60)->helperText('Để trống = dùng title')->translatable()
TextInput::make('seo_description')->maxLength(160)->translatable()
TextInput::make('seo_keywords')->maxLength(255)->translatable()
SpatieMediaLibraryFileUpload::make('og')->collection('og')->image()->imageEditor()
```

Tab 3 — Xuất bản:
```php
Select::make('status')->options(['draft'=>'Nháp','published'=>'Xuất bản','scheduled'=>'Hẹn giờ'])->required()
DateTimePicker::make('published_at')->helperText('Để trống = xuất bản ngay')
SpatieMediaLibraryFileUpload::make('cover')->collection('cover')->image()->imageEditor()->required()
```

**Header action "Preview draft"** (EditPost page):
```php
Actions\Action::make('preview')
    ->icon('heroicon-o-eye')
    ->url(fn (Post $record) => URL::temporarySignedRoute(
        'blog.preview', now()->addHour(), ['post' => $record->id]
    ))
    ->openUrlInNewTab()
    ->visible(fn (Post $record) => $record->status === 'draft'),
```

Route: `Route::get('/blog/preview/{post}', [PostController::class, 'preview'])->name('blog.preview')->middleware('signed')` — controller render bài (kể cả draft) với header `X-Robots-Tag: noindex`.

**Table**:
- Columns: title (de), status badge, published_at, author, view_count
- Filters: status, author, trashed
- Actions: edit, view (public URL), delete, restore

### 5. `HtmlSanitizer` helper

- Wrap `mews/purifier` với config allowlist:
  - Tags: `p, h2, h3, h4, ul, ol, li, a, strong, em, u, s, blockquote, img, figure, figcaption, iframe, table, thead, tbody, tr, td, th, code, pre, hr, br, div, span`
  - Attrs: `a[href|title|rel|target]`, `img[src|alt|title|width|height|loading]`, `iframe[src|width|height|allow|allowfullscreen|frameborder]`, `div[class|data-type]`, `span[class]`, `figure[class]`
  - `HTML.SafeIframe => true`, `URI.SafeIframeRegexp => /^https:\/\/(www\.youtube(-nocookie)?\.com\/embed\/|player\.vimeo\.com\/video\/)/`
- Model `saving` event: sanitize content per locale trước khi lưu
- **Integration test bắt buộc**: tạo fixture output từ tiptap (đủ all blocks: heading, image, video, table, list, quote, code, link) → run qua HtmlSanitizer → assert không bị strip sai

### 6. Tiptap editor profile

Config `config/filament-tiptap-editor.php`:
- Profile `default` tools: `heading, bold, italic, underline, strike, link, bullet-list, ordered-list, blockquote, media, video, code-block, table, hr, align-left, align-center, align-right`
- Media: `disk => 'public'`, `directory => 'posts/content'`, `max_file_size => 5120` (5MB), `accepted_file_types => ['image/jpeg', 'image/png', 'image/webp']`
- Video: cho phép YouTube/Vimeo (regex `youtube.com|vimeo.com`)
- Output: HTML (default)
- `preserve_file_names => false` (slugify tự động, tránh ký tự lạ)

### 7. Unique translatable slug validator

Validator closure (generated column UNIQUE INDEX đã enforce ở DB, validator cho UX friendly message):
```php
fn ($attribute, $value, $fail) use ($locale) =>
    Post::whereRaw("JSON_UNQUOTE(slug->'$.{$locale}') = ?", [$value])
        ->where('id', '!=', $this->record?->id)
        ->whereNull('deleted_at')
        ->exists() ? $fail("Slug ({$locale}) đã tồn tại") : null
```

### 8. Register resource

Đã auto-discover qua `AdminPanelProvider::discoverResources()`. Verify navigation group = "Content".

### 9. Seeder demo

2 bài: 1 về "Phở tại Leipzig", 1 về "Vietnamesisches Neujahr" — song ngữ đầy đủ, published.

### 10. Compile check

```bash
php artisan migrate
php artisan db:seed --class=PostSeeder
php artisan filament:cache-components
# vào /admin → Content → Posts → tạo bài thử
```

## Todo List

- [ ] `composer require awcodes/filament-tiptap-editor:"^3.0" mews/purifier`
- [ ] `npm i html-react-parser dompurify` + `-D @types/dompurify`
- [ ] Update `postcss.config.js` thêm `tailwindcss/nesting`
- [ ] Publish configs tiptap + purifier
- [ ] Viết migration `create_posts_table` với generated columns + unique index
- [ ] Tạo `Post` model với traits + scopes + media conversions + saving events (sanitize, URL normalize, reading_time)
- [ ] Tạo `HtmlSanitizer` helper
- [ ] Tạo `PostResource` với 3 tabs + header action "Preview draft"
- [ ] Implement slug auto-generate từ title (Str::slug)
- [ ] Unique translatable slug validator
- [ ] `PostResource/Pages/*` (List/Create/Edit)
- [ ] Config tiptap profile `default` + media disk/directory
- [ ] Tạo `PostPolicy` (admin-only CRUD)
- [ ] Route `blog.preview` với middleware `signed` + `PostController::preview`
- [ ] Seeder 2-3 bài demo
- [ ] Run migrate + seed + verify admin
- [ ] Tạo bài thử với ảnh inline → check media library lưu đúng + URL tương đối
- [ ] **Integration test**: HtmlSanitizer với full tiptap block output
- [ ] Test translate tab DE → EN (plugin translatable)
- [ ] Test draft preview signed URL (expires, noindex header)

## Success Criteria

- Admin tạo/sửa/xoá/restore bài qua Filament
- Upload ảnh trong tiptap → lưu vào media library, URL render đúng
- Slug auto-gen khi nhập title
- Sanitize content loại bỏ `<script>`, inline `onclick`, tag không allowlist
- Reading time hiển thị đúng (200 từ/phút)
- 1000 bài seed test → list page load < 200ms

## Risk Assessment

| Risk | Mitigation |
|------|-----------|
| Slug trùng per locale | **Generated column + UNIQUE INDEX per locale** enforce ở DB + validator UX-friendly |
| Tiptap ảnh URL tuyệt đối phá khi đổi APP_URL | Saving event regex replace → URL tương đối `/storage/posts/content/...` |
| Content XSS qua tiptap block custom | Sanitize 2 tầng: Purifier lưu + DOMPurify render; config `HTML.SafeIframe` + whitelist YouTube/Vimeo |
| Media library orphan files khi xoá post | Spatie auto-delete qua `InteractsWithMedia::deleted` |
| Filament translatable plugin + tiptap xung đột locale state | Test kỹ switch tab DE/EN, log issue nếu cần patch |
| `mews/purifier` allowlist quá chặt cắt mất block tiptap | **Integration test bắt buộc** + allowlist bao gồm `div[class|data-type]` cho tiptap wrapper |
| Generated column + soft delete conflict khi restore | Unique index bao gồm `deleted_at`; admin restore phải check slug free trước |
| Preview signed URL rò rỉ | Expire 1h, `X-Robots-Tag: noindex`, không log query string |
| `postcss.config.js` thiếu nesting plugin → tiptap CSS vỡ | Step 1 đã include config update |

## Security Considerations

- Authorization: `PostPolicy` — chỉ user role `admin` hoặc `editor` CRUD (phase sau nếu cần đa role, hiện chỉ 1 loại admin)
- CSRF: Filament auto
- XSS: 2-tầng sanitize (Purifier backend + DOMPurify frontend ở Phase 03)
- SQL injection: Eloquent + cast JSON
- File upload: media library giới hạn MIME image/* + size 5MB
- Preview route draft: signed URL (expires 1h) — không public path `/blog/{slug}` cho draft

## Next Steps

- Phase 03: Frontend render `/blog`, `/blog/{slug}` + SEO per-post + hydrate React
- Phase 04: Sitemap post URLs + RSS feed + Article JSON-LD
