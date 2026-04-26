<?php

namespace App\Filament\Pages;

use App\Models\Page;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page as FilamentPage;
use FilamentTiptapEditor\TiptapEditor;

class AboutPage extends FilamentPage implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-information-circle';

    protected static ?string $navigationLabel = 'About Page';

    protected static ?string $title = 'About Page — Über uns';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.about-page';

    public array $data = [];

    private const LOCALES = ['de', 'en'];

    public function mount(): void
    {
        $page = $this->getOrCreatePage();

        $formData = [];
        foreach (self::LOCALES as $locale) {
            $content = $page->getTranslation('content', $locale, false);
            $seo = $page->getTranslation('seo', $locale, false);

            $formData[$locale] = [
                'title'           => $content['title'] ?? '',
                'body'            => $content['body'] ?? '',
                'hero_image'      => $content['hero_image'] ?? null,
                'seo_title'       => $seo['title'] ?? '',
                'seo_description' => $seo['description'] ?? '',
                'seo_keywords'    => $seo['keywords'] ?? '',
            ];
        }

        $this->form->fill($formData);
    }

    public function form(Form $form): Form
    {
        return $form->statePath('data')->schema([
            Tabs::make('Locales')
                ->tabs([
                    Tabs\Tab::make('Deutsch (DE)')
                        ->schema($this->localeSchema('de')),

                    Tabs\Tab::make('English (EN)')
                        ->schema($this->localeSchema('en')),
                ])
                ->columnSpanFull(),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $page = $this->getOrCreatePage();

        foreach (self::LOCALES as $locale) {
            $localeData = $state[$locale] ?? [];

            $page->setTranslation('content', $locale, [
                'title'      => trim((string) ($localeData['title'] ?? '')),
                'body'       => (string) ($localeData['body'] ?? ''),
                'hero_image' => $localeData['hero_image'] ?? null,
            ]);

            $seoTitle       = trim((string) ($localeData['seo_title'] ?? ''));
            $seoDescription = trim((string) ($localeData['seo_description'] ?? ''));
            $seoKeywords    = trim((string) ($localeData['seo_keywords'] ?? ''));

            $page->setTranslation('seo', $locale, array_filter([
                'title'       => $seoTitle,
                'description' => $seoDescription,
                'keywords'    => $seoKeywords,
            ]));
        }

        $page->save();

        Notification::make()->title('About page saved')->success()->send();
    }

    private function localeSchema(string $locale): array
    {
        $isDE = $locale === 'de';

        return [
            Section::make('Content')
                ->schema([
                    TextInput::make("{$locale}.title")
                        ->label('Page title')
                        ->required($isDE)
                        ->maxLength(180)
                        ->helperText($isDE ? 'Required.' : 'Leave empty to fall back to DE.')
                        ->columnSpanFull(),

                    TiptapEditor::make("{$locale}.body")
                        ->label('Content')
                        ->profile('default')
                        ->required($isDE)
                        ->maxContentWidth('5xl')
                        ->extraInputAttributes(['style' => 'min-height: 400px'])
                        ->helperText($isDE ? 'Required.' : 'Leave empty to fall back to DE.')
                        ->columnSpanFull(),

                    FileUpload::make("{$locale}.hero_image")
                        ->label('Hero image')
                        ->image()
                        ->disk('public')
                        ->directory('about')
                        ->imageEditor()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(8 * 1024)
                        ->helperText($isDE ? 'Shown at the top of the About page.' : 'Leave empty to fall back to DE hero image.')
                        ->columnSpanFull(),
                ]),

            Section::make('SEO')
                ->schema([
                    TextInput::make("{$locale}.seo_title")
                        ->label('SEO title')
                        ->maxLength(60)
                        ->helperText('Leave empty to use the page title. Google truncates >60 chars.')
                        ->columnSpanFull(),

                    Textarea::make("{$locale}.seo_description")
                        ->label('SEO description')
                        ->rows(3)
                        ->maxLength(160)
                        ->helperText('Leave empty to auto-generate. Google truncates >160 chars.')
                        ->columnSpanFull(),

                    Textarea::make("{$locale}.seo_keywords")
                        ->label('SEO keywords')
                        ->rows(2)
                        ->helperText('Comma separated. Google ignores meta keywords, but Bing/Yandex use them.')
                        ->columnSpanFull(),
                ])
                ->collapsed(),
        ];
    }

    private function getOrCreatePage(): Page
    {
        return Page::firstOrCreate(
            ['slug->de' => 'ueber-uns'],
            [
                'slug'   => ['de' => 'ueber-uns', 'en' => 'about'],
                'status' => 'published',
            ]
        );
    }
}
