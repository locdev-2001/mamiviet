<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\JsonLdBuilder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;

class JsonLdSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-code-bracket-square';

    protected static ?string $navigationLabel = 'JSON-LD Schema';

    protected static ?string $title = 'JSON-LD Schema';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.json-ld-settings';

    public array $data = [];

    public function mount(): void
    {
        $stored = Setting::raw('schema.local_business_json');

        $this->form->fill([
            'editable_json' => $this->encode(is_array($stored) && $stored !== []
                ? $stored
                : JsonLdBuilder::generatedLocalBusiness('de')),
            'reference_json' => $this->encode(JsonLdBuilder::generatedLocalBusiness('de')),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->statePath('data')->schema([
            Section::make('Editable JSON-LD')
                ->description('This full LocalBusiness JSON-LD object is editable. Use the generated reference below as a starting point when dynamic settings change.')
                ->schema([
                    Textarea::make('editable_json')
                        ->label('Editable LocalBusiness JSON-LD')
                        ->rows(24)
                        ->required()
                        ->helperText('Saved exactly as JSON-LD output, after JSON syntax validation. Leave no comments; JSON does not support comments.')
                        ->columnSpanFull(),
                ]),

            Section::make('Generated reference from dynamic settings')
                ->description('Read-only reference generated from Global Settings: name, phone, address, hours, image, social URLs, and Google Business links.')
                ->schema([
                    Textarea::make('reference_json')
                        ->label('Reference JSON')
                        ->rows(22)
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public function save(): void
    {
        $raw = (string) ($this->form->getState()['editable_json'] ?? '');
        $decoded = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            throw ValidationException::withMessages([
                'data.editable_json' => 'Invalid JSON: ' . json_last_error_msg(),
            ]);
        }

        Setting::set('schema.local_business_json', $decoded);

        $this->form->fill([
            'editable_json' => $this->encode($decoded),
            'reference_json' => $this->encode(JsonLdBuilder::generatedLocalBusiness('de')),
        ]);

        Notification::make()->title('JSON-LD schema saved')->success()->send();
    }

    private function encode(array $value): string
    {
        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
