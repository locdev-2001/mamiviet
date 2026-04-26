<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use FilamentTiptapEditor\TiptapEditor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class PostResource extends Resource
{
    use Translatable;

    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getTranslatableLocales(): array
    {
        return Post::LOCALES;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Post')
                ->columnSpanFull()
                ->tabs([
                    Forms\Components\Tabs\Tab::make('Content')
                        ->schema(static::contentSchema()),

                    Forms\Components\Tabs\Tab::make('SEO')
                        ->schema(static::seoSchema()),

                    Forms\Components\Tabs\Tab::make('Publishing')
                        ->schema(static::publishingSchema()),
                ]),
        ]);
    }

    protected static function contentSchema(): array
    {
        $requiredOnPrimary = static fn (\Livewire\Component $livewire): bool => (
            ($livewire->activeLocale ?? Post::PRIMARY_LOCALE) === Post::PRIMARY_LOCALE
        );

        return [
            Forms\Components\TextInput::make('title')
                ->required($requiredOnPrimary)
                ->maxLength(500)
                ->live(onBlur: true)
                ->afterStateUpdated(function (?string $state, Forms\Set $set, Forms\Get $get) {
                    if (! $state) {
                        return;
                    }
                    if (! $get('slug')) {
                        $set('slug', Str::slug($state));
                    }
                })
                ->helperText(static fn (\Livewire\Component $livewire): string => (
                    ($livewire->activeLocale ?? Post::PRIMARY_LOCALE) === Post::PRIMARY_LOCALE
                        ? 'Required for primary locale (DE).'
                        : 'Optional — leave empty if not translated yet.'
                ))
                ->columnSpanFull(),

            Forms\Components\TextInput::make('slug')
                ->required($requiredOnPrimary)
                ->maxLength(300)
                ->regex('/^[a-z0-9-]+$/')
                ->helperText('Lowercase letters, numbers, hyphens only. Auto-generated from title if empty.')
                ->rule(static::uniqueSlugRule())
                ->columnSpanFull(),

            Forms\Components\Textarea::make('excerpt')
                ->maxLength(300)
                ->rows(3)
                ->columnSpanFull(),

            TiptapEditor::make('content')
                ->profile('default')
                ->required($requiredOnPrimary)
                ->maxContentWidth('5xl')
                ->extraInputAttributes(['style' => 'min-height: 400px'])
                ->columnSpanFull(),
        ];
    }

    protected static function seoSchema(): array
    {
        // Soft-recommendation counter: shows char count + Google-recommended target
        // DB columns are JSON (4GB max), so no hard server-side limit needed
        $softCounter = static fn (int $recommended, string $prefix) =>
            static function (?string $state) use ($recommended, $prefix): string {
                $len = mb_strlen((string) $state);
                $indicator = $len === 0 ? '' : ($len <= $recommended ? ' ✓' : ' ⚠ over Google limit');
                return "{$prefix} {$len}/{$recommended} recommended{$indicator}";
            };

        return [
            Forms\Components\TextInput::make('seo_title')
                ->live(debounce: 400)
                ->helperText($softCounter(60, 'Leave empty to use the post title. Google truncates >60.'))
                ->columnSpanFull(),

            Forms\Components\Textarea::make('seo_description')
                ->rows(3)
                ->live(debounce: 400)
                ->helperText($softCounter(160, 'Leave empty to use the excerpt. Google truncates >160.'))
                ->columnSpanFull(),

            Forms\Components\Textarea::make('seo_keywords')
                ->rows(2)
                ->live(debounce: 400)
                ->helperText($softCounter(255, 'Comma separated. Google ignores meta keywords, but Bing/Yandex use them.'))
                ->columnSpanFull(),

            Forms\Components\SpatieMediaLibraryFileUpload::make('og')
                ->label('Open Graph image')
                ->collection('og')
                ->image()
                ->imageEditor()
                ->helperText('Optional. 1200×630 recommended. Falls back to cover image.')
                ->columnSpanFull(),
        ];
    }

    protected static function publishingSchema(): array
    {
        return [
            Forms\Components\Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
                    'scheduled' => 'Scheduled',
                ])
                ->default('draft')
                ->required()
                ->native(false),

            Forms\Components\DateTimePicker::make('published_at')
                ->helperText('Leave empty to publish immediately when status is Published.')
                ->seconds(false),

            Forms\Components\SpatieMediaLibraryFileUpload::make('cover')
                ->collection('cover')
                ->image()
                ->imageEditor()
                ->required()
                ->helperText('Required. 1200×630 recommended.')
                ->columnSpanFull(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('cover')
                    ->collection('cover')
                    ->conversion('thumb')
                    ->label(''),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(60),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => 'draft',
                        'success' => 'published',
                        'warning' => 'scheduled',
                    ]),

                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reading_time')
                    ->suffix(' min')
                    ->label('Read')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'scheduled' => 'Scheduled',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    protected static function uniqueSlugRule(): \Closure
    {
        return static function (\Filament\Forms\Get $get, ?Post $record) {
            return static function (string $attribute, $value, \Closure $fail) use ($record) {
                if (! is_string($value) || $value === '') {
                    return;
                }

                $locale = str_contains($attribute, '.')
                    ? substr($attribute, strrpos($attribute, '.') + 1)
                    : Post::PRIMARY_LOCALE;

                if (! in_array($locale, Post::LOCALES, true)) {
                    return;
                }

                $exists = Post::query()
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(slug, ?)) = ?", ["$.{$locale}", $value])
                    ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                    ->whereNull('deleted_at')
                    ->exists();

                if ($exists) {
                    $fail("The slug ({$locale}) is already taken by another post.");
                }
            };
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
