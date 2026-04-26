<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\JsonLdBuilder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
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

    private const SCHEMAS = [
        'website' => [
            'label' => 'WebSite',
            'setting' => 'schema.website_json',
            'editable' => 'website_json',
            'reference' => 'website_reference_json',
            'generated' => 'generatedWebsite',
        ],
        'organization' => [
            'label' => 'Organization',
            'setting' => 'schema.organization_json',
            'editable' => 'organization_json',
            'reference' => 'organization_reference_json',
            'generated' => 'generatedOrganization',
        ],
        'local_business' => [
            'label' => 'Restaurant',
            'setting' => 'schema.local_business_json',
            'editable' => 'local_business_json',
            'reference' => 'local_business_reference_json',
            'generated' => 'generatedLocalBusiness',
        ],
    ];

    public function mount(): void
    {
        $this->form->fill($this->formData());
    }

    public function form(Form $form): Form
    {
        return $form->statePath('data')->schema([
            Tabs::make('JSON-LD Schema')
                ->tabs(collect(self::SCHEMAS)
                    ->map(fn (array $schema) => Tabs\Tab::make($schema['label'])->schema($this->schemaTab($schema)))
                    ->values()
                    ->all())
                ->columnSpanFull(),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach (self::SCHEMAS as $schema) {
            $raw = trim((string) ($state[$schema['editable']] ?? ''));
            $decoded = json_decode($raw, true);

            if ($raw === '') {
                Setting::set($schema['setting'], null);
                continue;
            }

            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                throw ValidationException::withMessages([
                    "data.{$schema['editable']}" => "Invalid {$schema['label']} JSON: " . json_last_error_msg(),
                ]);
            }

            Setting::set($schema['setting'], $decoded);
        }

        $this->form->fill($this->formData());

        Notification::make()->title('JSON-LD schema saved')->success()->send();
    }

    private function schemaTab(array $schema): array
    {
        return [
            Section::make("Editable {$schema['label']} JSON-LD")
                ->description('Saved exactly as JSON-LD output after JSON syntax validation. Leave empty to use the generated fallback.')
                ->schema([
                    Textarea::make($schema['editable'])
                        ->label("Editable {$schema['label']} JSON")
                        ->rows(20)
                        ->helperText('Use valid JSON only. Comments and trailing commas are not allowed.')
                        ->columnSpanFull(),
                ]),

            Section::make('Generated reference')
                ->description($schema['label'] === 'Restaurant'
                    ? 'Read-only German reference generated from Global Settings: name, phone, address, hours, image, social URLs, and Google Business links.'
                    : 'Read-only reference generated from Global Settings.')
                ->schema([
                    Textarea::make($schema['reference'])
                        ->label('Reference JSON')
                        ->rows(16)
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ]),
        ];
    }

    private function formData(): array
    {
        $data = [];

        foreach (self::SCHEMAS as $schema) {
            $stored = Setting::raw($schema['setting']);
            $reference = $this->generated($schema);

            $data[$schema['editable']] = $this->encode(is_array($stored) && $stored !== []
                ? $stored
                : $reference);
            $data[$schema['reference']] = $this->encode($reference);
        }

        return $data;
    }

    private function generated(array $schema): array
    {
        $method = $schema['generated'];

        return $method === 'generatedLocalBusiness'
            ? JsonLdBuilder::generatedLocalBusiness('de')
            : JsonLdBuilder::{$method}();
    }

    private function encode(array $value): string
    {
        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
