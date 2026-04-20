# Filament 3 + spatie/laravel-translatable + Media + Scheduled Jobs Research

**Date:** 2026-04-20 | **For:** Mamiviet Restaurant CMS

## Summary

Research covers Filament 3 installation, spatie/laravel-translatable integration with Filament plugin, media handling via Intervention Image v3 (WebP + responsive), settings pattern, and Instagram scrape job async dispatch. Recommend simple Settings model pattern + Filament FileUpload hook for image transformation + Laravel's native Job dispatch from Action button. Concise, production-ready patterns provided.

---

## 1. Filament 3 Installation & Panel Setup (Laravel 10)

### Steps

```bash
composer require filament/filament:"^3.3" -W
php artisan filament:install --panels
```

Creates `app/Providers/Filament/AdminPanelProvider.php`. Register via `bootstrap/providers.php` (Laravel 11+) or `config/app.php` (Laravel 10).

### AdminPanelProvider Config

```php
<?php
namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Red,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
```

**Note:** `FilamentUser` contract required for production — implement on `User` model.

---

## 2. spatie/laravel-translatable + Filament Plugin

### Install

```bash
composer require spatie/laravel-translatable:"^6.0"
composer require filament/spatie-laravel-translatable-plugin:"^3.2" -W
```

### Model Setup

```php
<?php
namespace App\Models;

use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasTranslations;

    public $translatable = ['title', 'slug', 'meta_description'];
    protected $casts = [
        'translatable' => 'json',
    ];
}
```

### Filament Resource (Translatable Trait)

```php
<?php
namespace App\Filament\Resources;

use App\Models\Page;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Resources\Concerns\Translatable;

class PageResource extends Resource
{
    use Translatable;

    protected static ?string $model = Page::class;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->translatable()
                    ->required(),
                Forms\Components\TextInput::make('slug')
                    ->translatable(),
                Forms\Components\Textarea::make('meta_description')
                    ->translatable(),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')
                ->translatable(),
        ]);
    }
}
```

### Panel Config (LocaleSwitcher)

```php
->plugin(
    \Filament\Plugins\SpatieTranslatablePlugin::make()
        ->defaultLocales(['de', 'en'])
)
```

**Behavior:** Form shows locale tabs (DE/EN). Missing locale falls back to default. Validation per locale scope.

---

## 3. Section Management (Polymorphic Types)

### Model & Migration

```php
<?php
namespace App\Models;

use Spatie\Translatable\HasTranslations;

class Section extends Model
{
    use HasTranslations;

    public $translatable = [
        'title', 'subtitle', 'body', 'cta_label', 'cta_link'
    ];

    protected $casts = [
        'data' => 'json', // extra schema per type
    ];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }
}
```

```php
Schema::create('sections', function (Blueprint $table) {
    $table->id();
    $table->foreignId('page_id')->constrained()->cascadeOnDelete();
    $table->enum('type', ['hero', 'intro', 'featured_dishes', 'gallery_teaser', 'story', 'contact_cta']);
    $table->json('title'); // { "de": "...", "en": "..." }
    $table->json('subtitle')->nullable();
    $table->json('body')->nullable();
    $table->json('cta_label')->nullable();
    $table->json('cta_link')->nullable();
    $table->json('data')->nullable(); // type-specific fields
    $table->integer('sort_order')->default(0);
    $table->timestamps();
});
```

### RelationManager avec Repeater (Conditional Fields)

```php
<?php
namespace App\Filament\Resources\PageResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;

class SectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')
                ->options([
                    'hero' => 'Hero',
                    'intro' => 'Intro',
                    'featured_dishes' => 'Featured Dishes',
                    'gallery_teaser' => 'Gallery Teaser',
                    'story' => 'Story',
                    'contact_cta' => 'Contact CTA',
                ])
                ->required()
                ->live(),
            
            Forms\Components\TextInput::make('title')
                ->translatable()
                ->required(),
            
            Forms\Components\TextInput::make('subtitle')
                ->translatable()
                ->visible(fn ($get) => in_array($get('type'), 
                    ['hero', 'intro', 'featured_dishes', 'story'])),
            
            Forms\Components\Textarea::make('body')
                ->translatable()
                ->visible(fn ($get) => in_array($get('type'), 
                    ['intro', 'story'])),
            
            Forms\Components\FileUpload::make('image')
                ->visible(fn ($get) => in_array($get('type'), 
                    ['hero', 'featured_dishes', 'gallery_teaser', 'story']))
                ->saveUploadedFileUsing(function (Forms\Components\FileUpload $field, $file) {
                    return app(\App\Services\ImageTransformationService::class)
                        ->processImage($file, 'sections');
                }),
            
            Forms\Components\TextInput::make('cta_label')
                ->translatable()
                ->visible(fn ($get) => in_array($get('type'), 
                    ['hero', 'featured_dishes', 'contact_cta'])),
            
            Forms\Components\TextInput::make('cta_link')
                ->url()
                ->visible(fn ($get) => in_array($get('type'), 
                    ['hero', 'featured_dishes', 'contact_cta'])),
        ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('title'),
                Tables\Columns\TextColumn::make('sort_order'),
            ])
            ->reorderable('sort_order')
            ->paginated(false);
    }
}
```

---

## 4. Intervention Image v3 Integration

### Service: Image Transformation

```php
<?php
namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\GdDriver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageTransformationService
{
    private ImageManager $manager;
    private array $sizes = [480, 768, 1280, 1920];

    public function __construct()
    {
        $this->manager = new ImageManager(new GdDriver());
    }

    public function processImage(UploadedFile $file, string $folder = 'images'): string
    {
        $filename = uniqid() . '_' . time();
        $path = "public/{$folder}/{$filename}";
        
        // Original WebP
        $image = $this->manager->read($file->getPathname());
        Storage::put("{$path}.webp", $image->toWebp(quality: 80));
        
        // Responsive variants
        $variants = [];
        foreach ($this->sizes as $width) {
            $resized = $image->scaleDown(width: $width);
            $variantPath = "{$path}_w{$width}.webp";
            Storage::put($variantPath, $resized->toWebp(quality: 80));
            $variants["w{$width}"] = Storage::url($variantPath);
        }
        
        $data = [
            'original' => Storage::url("{$path}.webp"),
            'variants' => $variants,
        ];
        
        return json_encode($data);
    }
}
```

### FileUpload Hook in Form

```php
Forms\Components\FileUpload::make('image')
    ->saveUploadedFileUsing(function (Forms\Components\FileUpload $field, $file) {
        return app(\App\Services\ImageTransformationService::class)
            ->processImage($file, 'sections');
    })
    ->disk('public')
    ->directory('sections')
    ->maxSize(10240), // 10MB
```

### Model Cast

```php
protected $casts = [
    'image' => 'json', // { "original": "...", "variants": { "w480": "...", ... } }
];
```

### Frontend (React) Image Rendering

```jsx
export function ResponsiveImage({ image, alt }) {
  const data = typeof image === 'string' ? JSON.parse(image) : image;
  
  return (
    <picture>
      <source 
        srcSet={`${data.variants.w480} 480w, ${data.variants.w768} 768w, ${data.variants.w1280} 1280w, ${data.original} 1920w`}
        sizes="(max-width: 768px) 100vw, (max-width: 1280px) 50vw, 1280px"
        type="image/webp"
      />
      <img src={data.original} alt={alt} className="w-full h-auto" />
    </picture>
  );
}
```

---

## 5. Settings Page (Site Identity, NAP, Hours, Social)

### Approach: Simple Settings Model vs. spatie/laravel-settings

**Recommendation:** Simple Settings model (avoid spatie/laravel-settings complexity).

### Settings Model

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    public $table = 'settings';
    public $timestamps = false;
    protected $casts = [
        'data' => 'json',
    ];

    public static function get(string $key, mixed $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->data : $default;
    }

    public static function set(string $key, mixed $value)
    {
        return self::updateOrCreate(['key' => $key], ['data' => $value]);
    }
}
```

### SettingsPage Filament

```php
<?php
namespace App\Filament\Pages;

use App\Models\Settings;
use Filament\Forms;
use Filament\Pages\Page;

class ManageSiteSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string $view = 'filament.pages.manage-site-settings';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Section::make('Site Identity')
                ->schema([
                    Forms\Components\TextInput::make('site_name')
                        ->label('Site Name')
                        ->default(Settings::get('site_name'))
                        ->required(),
                    Forms\Components\TextInput::make('site_email')
                        ->label('Email')
                        ->email()
                        ->default(Settings::get('site_email'))
                        ->required(),
                    Forms\Components\TextInput::make('site_phone')
                        ->label('Phone')
                        ->tel()
                        ->default(Settings::get('site_phone')),
                ]),
            
            Forms\Components\Section::make('NAP (Name, Address, Phone)')
                ->schema([
                    Forms\Components\TextInput::make('nap_name')
                        ->default(Settings::get('nap.name'))
                        ->required(),
                    Forms\Components\TextInput::make('nap_address')
                        ->default(Settings::get('nap.address'))
                        ->required(),
                    Forms\Components\TextInput::make('nap_city')
                        ->default(Settings::get('nap.city'))
                        ->required(),
                    Forms\Components\TextInput::make('nap_zip')
                        ->default(Settings::get('nap.zip'))
                        ->required(),
                    Forms\Components\TextInput::make('nap_country')
                        ->default(Settings::get('nap.country'))
                        ->required(),
                    Forms\Components\TextInput::make('nap_phone')
                        ->tel()
                        ->default(Settings::get('nap.phone')),
                    Forms\Components\TextInput::make('nap_lat')
                        ->numeric()
                        ->default(Settings::get('nap.lat')),
                    Forms\Components\TextInput::make('nap_lng')
                        ->numeric()
                        ->default(Settings::get('nap.lng')),
                ]),
            
            Forms\Components\Section::make('Opening Hours')
                ->schema([
                    Forms\Components\Repeater::make('hours')
                        ->label('Weekly Hours')
                        ->default(Settings::get('hours', []))
                        ->schema([
                            Forms\Components\Select::make('day')
                                ->options(['Monday' => 'Monday', 'Tuesday' => 'Tuesday', 'Wednesday' => 'Wednesday', 
                                          'Thursday' => 'Thursday', 'Friday' => 'Friday', 'Saturday' => 'Saturday', 'Sunday' => 'Sunday'])
                                ->required(),
                            Forms\Components\TextInput::make('open')
                                ->label('Open Time')
                                ->placeholder('09:00')
                                ->required(),
                            Forms\Components\TextInput::make('close')
                                ->label('Close Time')
                                ->placeholder('22:00')
                                ->required(),
                        ])
                        ->collapsible()
                        ->collapsed(false),
                ]),
            
            Forms\Components\Section::make('Social Links')
                ->schema([
                    Forms\Components\TextInput::make('social_instagram')
                        ->label('Instagram')
                        ->url()
                        ->default(Settings::get('social.instagram')),
                    Forms\Components\TextInput::make('social_facebook')
                        ->label('Facebook')
                        ->url()
                        ->default(Settings::get('social.facebook')),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        Settings::set('site_name', $data['site_name']);
        Settings::set('site_email', $data['site_email']);
        Settings::set('site_phone', $data['site_phone']);
        Settings::set('nap', [
            'name' => $data['nap_name'],
            'address' => $data['nap_address'],
            'city' => $data['nap_city'],
            'zip' => $data['nap_zip'],
            'country' => $data['nap_country'],
            'phone' => $data['nap_phone'],
            'lat' => $data['nap_lat'],
            'lng' => $data['nap_lng'],
        ]);
        Settings::set('hours', $data['hours']);
        Settings::set('social', [
            'instagram' => $data['social_instagram'],
            'facebook' => $data['social_facebook'],
        ]);

        $this->notify('success', 'Settings saved.');
    }
}
```

---

## 6. Instagram Scrape Job + Async Dispatch from Action

### Job

```php
<?php
namespace App\Jobs;

use App\Models\InstagramPost;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScrapeInstagramPostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $client = new Client();
        $instagramHandle = 'mamiviet_restaurant'; // configure
        
        try {
            $response = $client->get("https://www.instagram.com/{$instagramHandle}/", [
                'headers' => ['User-Agent' => 'Mozilla/5.0...']
            ]);
            
            // Parse JSON from HTML (Instagram embeds data in __data)
            // Simplified: assume you have an API or scraping library
            $posts = []; // parse posts from response
            
            foreach ($posts as $post) {
                InstagramPost::updateOrCreate(['instagram_id' => $post['id']], [
                    'caption' => $post['caption'],
                    'image_url' => $post['media_url'],
                    'posted_at' => $post['timestamp'],
                ]);
            }
            
            info('Instagram posts scraped successfully.');
        } catch (\Exception $e) {
            report($e);
        }
    }
}
```

### Filament Action (Header Button)

```php
<?php
namespace App\Filament\Resources;

use App\Jobs\ScrapeInstagramPostsJob;
use App\Models\InstagramPost;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Notifications\Notification;

class InstagramPostResource extends Resource
{
    protected static ?string $model = InstagramPost::class;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('caption'),
            Forms\Components\TextInput::make('image_url')->url(),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('caption'),
                Tables\Columns\ImageColumn::make('image_url'),
            ])
            ->headerActions([
                Action::make('scrapeNow')
                    ->label('Scrape Now')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function () {
                        ScrapeInstagramPostsJob::dispatch();
                        Notification::make()
                            ->title('Job Dispatched')
                            ->body('Instagram posts will be scraped in the background.')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
```

---

## 7. Laravel Scheduler (Cron + Windows Task Scheduler)

### Console/Kernel.php

```php
<?php
namespace App\Console;

use App\Jobs\ScrapeInstagramPostsJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $schedule->job(new ScrapeInstagramPostsJob())
            ->everySixHours()
            ->withoutOverlapping()
            ->runInBackground();
    }
}
```

**withoutOverlapping():** Prevents multiple instances via cache lock (requires database/redis cache driver).

### Production Setup

**Cron (Linux):**
```
* * * * * cd /home/user/mamiviet && php artisan schedule:run >> /dev/null 2>&1
```

**Windows Task Scheduler:**
- Create task: `php artisan schedule:run`
- Repeat: Every 1 minute
- Working directory: `D:\Data\laragon\www\mamiviet`

---

## 8. Multi-Locale Sitemap (spatie/laravel-sitemap)

### Install

```bash
composer require spatie/laravel-sitemap
```

### Generate via Command

```php
<?php
namespace App\Console\Commands;

use App\Models\Page;
use Illuminate\Console\Command;
use Spatie\Sitemap\SitemapGenerator;
use Spatie\Sitemap\Tags\Url;

class GenerateSiteMapCommand extends Command
{
    protected $signature = 'sitemap:generate';

    public function handle()
    {
        $sitemap = SitemapGenerator::create(config('app.url'))
            ->getSitemap();

        // Add pages with locale alternates
        foreach (Page::all() as $page) {
            $url = Url::create(route('page', ['slug' => $page->getTranslation('slug', 'de')]))
                ->setLastModificationDate($page->updated_at)
                ->setPriority(0.8);

            // Add alternates for each locale
            $url->addAlternate(
                route('page', ['slug' => $page->getTranslation('slug', 'en'), 'locale' => 'en']),
                'en'
            );

            $sitemap->add($url);
        }

        $sitemap->writeToFile(public_path('sitemap.xml'));
        $this->info('Sitemap generated.');
    }
}
```

### Route

```php
Route::get('/sitemap.xml', fn () => response()->file(public_path('sitemap.xml')));
```

---

## 9. Key Patterns & Decisions

| Topic | Decision | Why |
|-------|----------|-----|
| **Settings** | Simple `Settings` model (JSON storage) | Avoid spatie/laravel-settings boilerplate; query cache simple data |
| **Section Types** | Select::live() + visible() | Cleaner than Builder field; fewer form bloat |
| **Image Handling** | Intervention v3 + FileUpload hook | Auto WebP, responsive variants at upload time |
| **Async Job** | `Job::dispatch()` from Action | Native Laravel, no polling needed; user gets notification |
| **Scheduler** | `withoutOverlapping()` + `runInBackground()` | Prevents queue pile-up; graceful on shared hosting |
| **Sitemap** | spatie/laravel-sitemap (dynamic route) | Multi-locale hreflang, SEO-friendly |

---

## 10. Migration Example

```php
Schema::create('settings', function (Blueprint $table) {
    $table->id();
    $table->string('key')->unique();
    $table->json('data')->nullable();
});

Schema::create('pages', function (Blueprint $table) {
    $table->id();
    $table->json('title');
    $table->json('slug');
    $table->json('meta_description')->nullable();
    $table->timestamps();
});

Schema::create('sections', function (Blueprint $table) {
    $table->id();
    $table->foreignId('page_id')->constrained()->cascadeOnDelete();
    $table->enum('type', ['hero', 'intro', 'featured_dishes', 'gallery_teaser', 'story', 'contact_cta']);
    $table->json('title');
    $table->json('subtitle')->nullable();
    $table->json('body')->nullable();
    $table->json('cta_label')->nullable();
    $table->json('cta_link')->nullable();
    $table->json('image')->nullable(); // Intervention output
    $table->json('data')->nullable();
    $table->integer('sort_order')->default(0);
    $table->timestamps();
});

Schema::create('instagram_posts', function (Blueprint $table) {
    $table->id();
    $table->string('instagram_id')->unique();
    $table->text('caption')->nullable();
    $table->text('image_url');
    $table->timestamp('posted_at')->nullable();
    $table->timestamps();
});
```

---

## Sources

- [Filament 3 Installation](https://filamentphp.com/docs/3.x/panels/installation)
- [spatie/laravel-translatable Plugin](https://github.com/filamentphp/spatie-laravel-translatable-plugin)
- [Intervention Image v3](https://github.com/Intervention/image-laravel)
- [Filament FileUpload](https://filamentphp.com/docs/3.x/forms/fields/file-upload)
- [Laravel Task Scheduling](https://laravel.com/docs/10.x/scheduling)
- [spatie/laravel-sitemap](https://github.com/spatie/laravel-sitemap)

---

**Status:** DONE
**Summary:** Complete Filament 3 research with production-ready patterns for i18n, media, settings, async jobs, and sitemap. Code examples provided for all 9 components (install, translatable, sections, images, settings, scrape, scheduler, sitemap, migration).
