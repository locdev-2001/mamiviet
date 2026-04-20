<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstagramPostResource\Pages;
use App\Jobs\ScrapeInstagramPostsJob;
use App\Models\InstagramPost;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InstagramPostResource extends Resource
{
    protected static ?string $model = InstagramPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-camera';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $label = 'Instagram Post';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('short_code')->disabled(),
            TextInput::make('owner_username')->disabled(),
            Textarea::make('caption')->disabled()->rows(4),
            TextInput::make('display_url')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            ImageColumn::make('display_url')->label('Image')->square()
                ->defaultImageUrl(asset('logo.png'))
                ->extraImgAttributes([
                    'referrerpolicy' => 'no-referrer',
                    'loading' => 'lazy',
                    'onerror' => "this.onerror=null;this.src='" . asset('logo.png') . "';",
                ]),
            TextColumn::make('caption')->limit(60)->wrap(),
            TextColumn::make('url')->label('Post')
                ->formatStateUsing(fn ($state) => '↗ Instagram')
                ->url(fn ($record) => $record->url, true)
                ->color('primary'),
            TextColumn::make('likes_count')->sortable()->label('Likes'),
            TextColumn::make('comments_count')->sortable()->label('Comments'),
            TextColumn::make('timestamp')->dateTime()->sortable(),
        ])
            ->defaultSort('timestamp', 'desc')
            ->headerActions([
                Action::make('scrapeNow')
                    ->label('Scrape Now')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->action(function (): void {
                        ScrapeInstagramPostsJob::dispatch();
                        Notification::make()
                            ->title('Scrape job dispatched')
                            ->body('New posts will appear within 1–2 minutes.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstagramPosts::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
