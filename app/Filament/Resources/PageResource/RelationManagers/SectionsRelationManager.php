<?php

namespace App\Filament\Resources\PageResource\RelationManagers;

use App\Models\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\Concerns\Translatable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SectionsRelationManager extends RelationManager
{
    use Translatable;

    protected static string $relationship = 'sections';

    protected static ?string $title = 'Sections';

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

        return $form->schema([
            Select::make('type')->options($typeOptions)->required()->live(),
            TextInput::make('order')->numeric()->required()->default(0),

            TextInput::make('title')->translatable()->required()->maxLength(255),
            TextInput::make('subtitle')->translatable()->maxLength(255)
                ->visible($visibleWhen('subtitle')),
            Textarea::make('body')->translatable()->rows(4)
                ->visible($visibleWhen('body')),
            FileUpload::make('image_path')->image()->directory('sections')->disk('public')
                ->visible($visibleWhen('image')),
            TextInput::make('cta_label')->translatable()->maxLength(80)
                ->visible($visibleWhen('cta')),
            TextInput::make('cta_link')->translatable()->maxLength(255)
                ->visible($visibleWhen('cta')),
            KeyValue::make('data')->keyLabel('Key')->valueLabel('Value')
                ->visible($visibleWhen('data')),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('order')->sortable(),
            BadgeColumn::make('type'),
            TextColumn::make('title')->translatable()->limit(60),
        ])
            ->reorderable('order')
            ->defaultSort('order')
            ->paginated(false)
            ->headerActions([CreateAction::make()])
            ->actions([EditAction::make(), DeleteAction::make()]);
    }
}
