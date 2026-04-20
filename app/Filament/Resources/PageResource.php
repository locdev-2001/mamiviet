<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HandlesTranslatableFormData;
use App\Filament\Resources\PageResource\Pages;
use App\Filament\Resources\PageResource\RelationManagers\SectionsRelationManager;
use App\Models\Page;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PageResource extends Resource
{
    use HandlesTranslatableFormData;

    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Content';

    public const LOCALES = ['de' => 'Deutsch', 'en' => 'English'];

    private const TRANSLATABLE_FIELDS = ['slug', 'seo'];

    public static function form(Form $form): Form
    {
        $localeTabs = collect(self::LOCALES)->map(fn ($label, $code) => Tabs\Tab::make($label)->schema([
            TextInput::make("{$code}.slug")->label('Slug')->required()->maxLength(150),
            Section::make('SEO')->schema([
                TextInput::make("{$code}.seo.title")->label('SEO Title')->maxLength(60),
                Textarea::make("{$code}.seo.description")->label('SEO Description')->maxLength(160)->rows(2),
                FileUpload::make("{$code}.seo.og_image")->label('OG Image')
                    ->image()->directory('seo')->disk('public'),
            ])->collapsible(),
        ]))->values()->all();

        return $form->schema([
            Select::make('status')
                ->options(['draft' => 'Draft', 'published' => 'Published'])
                ->default('draft')
                ->required(),
            Tabs::make('Locale')->tabs($localeTabs)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('slug')->searchable(),
            BadgeColumn::make('status')->colors([
                'success' => 'published',
                'gray' => 'draft',
            ]),
            TextColumn::make('updated_at')->dateTime()->sortable(),
        ])->actions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [SectionsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }

    public static function fillFormDataFromRecord(Page $record): array
    {
        $locales = array_keys(self::LOCALES);

        return ['status' => $record->status]
            + self::unpackTranslations($record, self::TRANSLATABLE_FIELDS, $locales);
    }

    public static function applyFormDataToRecord(Page $record, array $data): Page
    {
        $locales = array_keys(self::LOCALES);

        $record->status = $data['status'] ?? $record->status ?? 'draft';
        self::applyTranslations($record, $data, self::TRANSLATABLE_FIELDS, $locales);
        $record->save();

        return $record;
    }
}
