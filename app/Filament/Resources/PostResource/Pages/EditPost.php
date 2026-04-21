<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use App\Models\Post;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\URL;

class EditPost extends EditRecord
{
    use EditRecord\Concerns\Translatable;

    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\Action::make('preview')
                ->label('Preview draft')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn (Post $record): string => URL::temporarySignedRoute(
                    'blog.preview',
                    now()->addHour(),
                    [
                        'post' => $record->id,
                        'locale' => $this->activeLocale ?? Post::PRIMARY_LOCALE,
                    ],
                ))
                ->openUrlInNewTab()
                ->visible(fn (Post $record): bool => $record->status !== 'published'),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
