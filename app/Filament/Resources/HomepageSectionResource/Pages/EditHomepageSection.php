<?php

namespace App\Filament\Resources\HomepageSectionResource\Pages;

use App\Filament\Resources\HomepageSectionResource;
use App\Filament\Support\HomepageSectionSchema;
use App\Models\Section;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;

class EditHomepageSection extends EditRecord
{
    protected static string $resource = HomepageSectionResource::class;

    public function getTitle(): string
    {
        /** @var Section $record */
        $record = $this->record;

        return 'Edit: ' . HomepageSectionSchema::labelFor($record->key);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function form(Form $form): Form
    {
        /** @var Section $record */
        $record = $this->record ?? $this->getRecord();

        return $form->schema(HomepageSectionSchema::editForm($record->key))
            ->statePath('data');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Section $record */
        $record = $this->record;

        $data['enabled'] = (bool) $record->enabled;
        $data['data'] = $record->data ?? [];
        $data['content_de'] = $record->getTranslation('content', 'de', false) ?? [];
        $data['content_en'] = $record->getTranslation('content', 'en', false) ?? [];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return [
            'enabled' => (bool) ($data['enabled'] ?? false),
            'data' => $data['data'] ?? null,
            'content' => [
                'de' => $data['content_de'] ?? [],
                'en' => $data['content_en'] ?? [],
            ],
        ];
    }
}
