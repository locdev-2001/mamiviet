<?php

namespace App\Filament\Resources\PageResource\RelationManagers;

use App\Filament\Concerns\HandlesTranslatableFormData;
use App\Filament\Resources\PageResource;
use App\Models\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SectionsRelationManager extends RelationManager
{
    use HandlesTranslatableFormData;

    protected static string $relationship = 'sections';

    protected static ?string $title = 'Sections';

    private const TRANSLATABLE_FIELDS = ['title', 'subtitle', 'body', 'cta_label', 'cta_link'];

    private const NON_TRANSLATABLE_FIELDS = ['type', 'order', 'image_path', 'data'];

    private const FIELDS_BY_TYPE = [
        'subtitle' => ['hero', 'intro', 'featured_dishes', 'story'],
        'body' => ['intro', 'story'],
        'image' => ['hero', 'featured_dishes', 'gallery_teaser', 'story'],
        'cta' => ['hero', 'featured_dishes', 'contact_cta'],
        'data' => ['featured_dishes'],
    ];

    public function form(Form $form): Form
    {
        $typeOptions = collect(Section::TYPES)
            ->mapWithKeys(fn ($t) => [$t => ucwords(str_replace('_', ' ', $t))])
            ->all();

        $visibleWhen = fn (string $key) => fn ($get) => in_array($get('type'), self::FIELDS_BY_TYPE[$key], true);

        $localeTabs = collect(PageResource::LOCALES)
            ->map(fn ($label, $code) => Tabs\Tab::make($label)->schema([
                TextInput::make("{$code}.title")->label('Title')->required()->maxLength(255),
                TextInput::make("{$code}.subtitle")->label('Subtitle')->maxLength(255)
                    ->visible($visibleWhen('subtitle')),
                Textarea::make("{$code}.body")->label('Body')->rows(4)
                    ->visible($visibleWhen('body')),
                TextInput::make("{$code}.cta_label")->label('CTA label')->maxLength(80)
                    ->visible($visibleWhen('cta')),
                TextInput::make("{$code}.cta_link")->label('CTA link')->maxLength(255)
                    ->visible($visibleWhen('cta')),
            ]))->values()->all();

        return $form->schema([
            Select::make('type')->options($typeOptions)->required()->live(),
            TextInput::make('order')->numeric()->required()->default(0),
            FileUpload::make('image_path')->image()->disk('public')->directory('sections')
                ->visible($visibleWhen('image')),
            KeyValue::make('data')->keyLabel('Key')->valueLabel('Value')
                ->visible($visibleWhen('data')),

            Tabs::make('Locale')->tabs($localeTabs)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        $locales = array_keys(PageResource::LOCALES);

        return $table->columns([
            TextColumn::make('order')->sortable(),
            BadgeColumn::make('type'),
            TextColumn::make('title')->limit(60),
        ])
            ->reorderable('order')
            ->defaultSort('order')
            ->paginated(false)
            ->headerActions([
                CreateAction::make()
                    ->using(fn (array $data, $livewire) => $this->createSection($data, $livewire)),
            ])
            ->actions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data, Section $record) => $this->buildFormData($record))
                    ->using(fn (Section $record, array $data) => $this->updateSection($record, $data)),
                DeleteAction::make(),
            ]);
    }

    private function buildFormData(Section $record): array
    {
        $locales = array_keys(PageResource::LOCALES);
        $base = [
            'type' => $record->type,
            'order' => $record->order,
            'image_path' => $record->image_path,
            'data' => $record->data,
        ];

        return $base + self::unpackTranslations($record, self::TRANSLATABLE_FIELDS, $locales);
    }

    private function createSection(array $data, $livewire): Section
    {
        $section = $livewire->getRelationship()->make($this->extractNonTranslatable($data));
        $this->applySectionTranslations($section, $data);
        $section->save();

        return $section;
    }

    private function updateSection(Section $record, array $data): Section
    {
        $record->fill($this->extractNonTranslatable($data));
        $this->applySectionTranslations($record, $data);
        $record->save();

        return $record;
    }

    private function extractNonTranslatable(array $data): array
    {
        return [
            'type' => $data['type'],
            'order' => $data['order'] ?? 0,
            'image_path' => $data['image_path'] ?? null,
            'data' => $data['data'] ?? null,
        ];
    }

    private function applySectionTranslations(Section $record, array $data): void
    {
        $locales = array_keys(PageResource::LOCALES);
        self::applyTranslations($record, $data, self::TRANSLATABLE_FIELDS, $locales);
    }
}
