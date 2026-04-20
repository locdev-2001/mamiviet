<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HomepageSectionResource\Pages;
use App\Filament\Support\HomepageSectionSchema;
use App\Models\Section;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HomepageSectionResource extends Resource
{
    protected static ?string $model = Section::class;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Homepage Sections';

    protected static ?string $modelLabel = 'Homepage Section';

    protected static ?string $pluralModelLabel = 'Homepage Sections';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('page', fn (Builder $q) => $q->whereJsonContains('slug->de', 'home'))
            ->orderBy('order');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('order')
            ->paginated(false)
            ->columns([
                TextColumn::make('order')->label('#')->sortable(),
                TextColumn::make('key')
                    ->label('Section')
                    ->formatStateUsing(fn (string $state) => HomepageSectionSchema::labelFor($state))
                    ->description(fn (Section $record) => $record->key)
                    ->searchable(),
                IconColumn::make('enabled')->boolean()->label('Visible'),
                TextColumn::make('media_count')
                    ->label('Images')
                    ->state(fn (Section $record) => $record->media()->count()),
                TextColumn::make('updated_at')->since()->label('Updated'),
            ])
            ->actions([EditAction::make()]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHomepageSections::route('/'),
            'edit' => Pages\EditHomepageSection::route('/{record}/edit'),
        ];
    }
}
