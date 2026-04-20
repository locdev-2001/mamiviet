<?php

namespace App\Filament\Support;

use App\Models\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section as FormSection;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;

class HomepageSectionSchema
{
    /**
     * Field definitions per section key.
     * Structure: [fieldName => ['label' => string, 'type' => 'text'|'textarea', 'required' => bool]]
     */
    public const FIELDS = [
        'hero' => [
            'title' => ['label' => 'Title', 'type' => 'text', 'required' => true],
        ],
        'welcome' => [
            'brand_name' => ['label' => 'Brand name', 'type' => 'text'],
            'tagline' => ['label' => 'Tagline', 'type' => 'text'],
            'cuisine_label' => ['label' => 'Cuisine label (faint)', 'type' => 'text'],
            'title' => ['label' => 'Title', 'type' => 'text', 'required' => true],
            'body' => ['label' => 'Body', 'type' => 'textarea'],
            'cta_label' => ['label' => 'CTA label', 'type' => 'text'],
        ],
        'welcome_second' => [
            'cuisine_label' => ['label' => 'Cuisine label (faint)', 'type' => 'text'],
            'title' => ['label' => 'Title', 'type' => 'text', 'required' => true],
            'body' => ['label' => 'Body', 'type' => 'textarea'],
            'cta_label' => ['label' => 'CTA label (→ /bilder)', 'type' => 'text'],
        ],
        'order' => [
            'title' => ['label' => 'Title', 'type' => 'text', 'required' => true],
            'takeaway' => ['label' => 'Takeaway line', 'type' => 'text'],
            'delivery' => ['label' => 'Delivery line', 'type' => 'text'],
            'reservation' => ['label' => 'Reservation line', 'type' => 'text'],
            'free_delivery' => ['label' => 'Free delivery note', 'type' => 'text'],
            'cta_label' => ['label' => 'CTA label', 'type' => 'text'],
        ],
        'reservation' => [
            'title' => ['label' => 'Title (large faint)', 'type' => 'text'],
            'subtitle' => ['label' => 'Subtitle', 'type' => 'text'],
            'note' => ['label' => 'Note', 'type' => 'text'],
            'cta_label' => ['label' => 'CTA label', 'type' => 'text'],
            'overlay_text' => ['label' => 'Overlay text', 'type' => 'text'],
            'overlay_subtitle' => ['label' => 'Overlay subtitle', 'type' => 'text'],
        ],
        'contact' => [
            'title' => ['label' => 'Title', 'type' => 'text'],
            'restaurant_name' => ['label' => 'Restaurant name', 'type' => 'text'],
            'address' => ['label' => 'Address', 'type' => 'text'],
            'phone' => ['label' => 'Phone', 'type' => 'text'],
            'email' => ['label' => 'Email', 'type' => 'text'],
            'instagram_label' => ['label' => 'Instagram handle (display)', 'type' => 'text'],
        ],
        'gallery_slider' => [
            'title' => ['label' => 'Title', 'type' => 'text'],
            'subtitle' => ['label' => 'Subtitle', 'type' => 'text'],
        ],
        'intro' => [
            'title' => ['label' => 'Title', 'type' => 'text'],
            'text1' => ['label' => 'Paragraph 1', 'type' => 'textarea'],
            'text2' => ['label' => 'Paragraph 2', 'type' => 'textarea'],
        ],
    ];

    public const LABELS = [
        'hero' => 'Hero Banner',
        'welcome' => 'Cuisine Welcome',
        'welcome_second' => 'Second Cuisine Block',
        'order' => 'Order Section',
        'reservation' => 'Reservation Section',
        'contact' => 'Contact Section',
        'gallery_slider' => 'Gallery Slider',
        'intro' => 'Intro Outro',
    ];

    /**
     * Non-translatable data fields (stored in Section::$data).
     */
    public const DATA_FIELDS = [
        'contact' => [
            'instagram_url' => [
                'label' => 'Instagram URL',
                'type' => 'text',
                'rules' => ['nullable', 'url'],
            ],
            'map_embed' => [
                'label' => 'Google Maps embed URL (must start with https://www.google.com/maps/embed?)',
                'type' => 'textarea',
                'rules' => ['nullable', 'regex:#^https://www\.google\.com/maps/embed\?#'],
            ],
        ],
    ];

    public static function labelFor(string $key): string
    {
        return self::LABELS[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    public static function editForm(string $key): array
    {
        return [
            Toggle::make('enabled')
                ->label('Show this section on the homepage')
                ->helperText('Toggle off to hide this section from the UI without deleting data.')
                ->inline(false),

            FormSection::make('Content')
                ->visible(fn (Get $get) => (bool) $get('enabled'))
                ->schema([
                    Tabs::make('Locale')->tabs([
                        Tabs\Tab::make('Deutsch')->schema(self::localeFields($key, 'de')),
                        Tabs\Tab::make('English')->schema(self::localeFields($key, 'en')),
                    ])->columnSpanFull(),
                ]),

            ...self::dataFieldsSection($key),
            ...self::mediaSections($key),
        ];
    }

    private static function localeFields(string $key, string $locale): array
    {
        $fields = self::FIELDS[$key] ?? [];

        return collect($fields)
            ->map(fn (array $def, string $name) => self::buildField("content_{$locale}.{$name}", $def))
            ->values()
            ->all();
    }

    private static function buildField(string $statePath, array $def): TextInput|Textarea
    {
        $component = $def['type'] === 'textarea'
            ? Textarea::make($statePath)->rows(4)
            : TextInput::make($statePath)->maxLength(500);

        $component->label($def['label']);

        if ($def['required'] ?? false) {
            $component->required();
        }

        if (! empty($def['rules'])) {
            $component->rules($def['rules']);
        }

        return $component;
    }

    private static function dataFieldsSection(string $key): array
    {
        $fields = self::DATA_FIELDS[$key] ?? [];
        if (! $fields) {
            return [];
        }

        return [
            FormSection::make('Additional settings')
                ->visible(fn (Get $get) => (bool) $get('enabled'))
                ->schema(
                    collect($fields)
                        ->map(fn (array $def, string $name) => self::buildField("data.{$name}", $def))
                        ->values()
                        ->all()
                ),
        ];
    }

    private static function mediaSections(string $key): array
    {
        $collections = Section::mediaCollectionsFor($key);

        if (! $collections['single'] && ! $collections['multi']) {
            return [];
        }

        $components = [];

        foreach ($collections['single'] as $collection) {
            $components[] = self::mediaUpload($collection, multiple: false);
        }
        foreach ($collections['multi'] as $collection) {
            $components[] = self::mediaUpload($collection, multiple: true);
        }

        return [
            FormSection::make('Images')
                ->visible(fn (Get $get) => (bool) $get('enabled'))
                ->description('Auto-compressed + 4 responsive WebP sizes (480/768/1280/1920) for SEO and CLS prevention.')
                ->schema($components)
                ->columns(2),
        ];
    }

    private static function mediaUpload(string $collection, bool $multiple): SpatieMediaLibraryFileUpload
    {
        $upload = SpatieMediaLibraryFileUpload::make($collection)
            ->collection($collection)
            ->label(ucfirst(str_replace('_', ' ', $collection)))
            ->image()
            ->imageEditor()
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->maxSize(8 * 1024)
            ->columnSpan($multiple ? 2 : 1)
            ->helperText('JPEG / PNG / WebP, max 8 MB. Auto-converted to responsive WebP.');

        if ($multiple) {
            $upload->multiple()
                ->reorderable()
                ->appendFiles()
                ->panelLayout('grid');
        }

        return $upload;
    }
}
