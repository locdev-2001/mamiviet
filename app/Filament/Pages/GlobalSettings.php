<?php

namespace App\Filament\Pages;

use App\Filament\Support\GlobalSettingsSchema;
use App\Models\Setting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section as FormSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class GlobalSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Global Settings';

    protected static ?string $title = 'Global Settings — Header / Footer / SEO';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.global-settings';

    public array $data = [];

    public function mount(): void
    {
        $values = [];
        foreach (GlobalSettingsSchema::allKeys() as $key => $def) {
            $stored = Setting::raw($key);
            $fieldKey = self::keyToField($key);

            if ($def['translatable']) {
                $values[$fieldKey . '__de'] = is_array($stored) ? ($stored['de'] ?? '') : (string) ($stored ?? '');
                $values[$fieldKey . '__en'] = is_array($stored) ? ($stored['en'] ?? '') : '';
            } else {
                if ($stored === null && isset($def['default'])) {
                    $values[$fieldKey] = $def['default'];
                } else {
                    $values[$fieldKey] = is_string($stored) ? $stored : (string) ($stored ?? '');
                }
            }
        }
        $this->form->fill($values);
    }

    public function form(Form $form): Form
    {
        return $form->statePath('data')->schema([
            Tabs::make('Global Settings')->tabs(
                collect(GlobalSettingsSchema::TABS)->map(
                    fn (array $tab) => Tabs\Tab::make($tab['label'])->schema(self::buildSections($tab['sections']))
                )->values()->all()
            )->columnSpanFull(),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        DB::transaction(function () use ($state) {
            foreach (GlobalSettingsSchema::allKeys() as $key => $def) {
                $fieldKey = self::keyToField($key);

                if ($def['translatable']) {
                    $value = [
                        'de' => trim((string) ($state[$fieldKey . '__de'] ?? '')),
                        'en' => trim((string) ($state[$fieldKey . '__en'] ?? '')),
                    ];
                    $value = array_filter($value, fn ($v) => $v !== '');
                    Setting::set($key, $value ?: null);
                } else {
                    $raw = $state[$fieldKey] ?? null;
                    Setting::set($key, $raw === '' || $raw === null ? null : $raw);
                }
            }
        });

        Notification::make()->title('Settings saved')->success()->send();
    }

    private static function buildSections(array $sections): array
    {
        return collect($sections)->map(
            fn (array $fields, string $title) => FormSection::make($title)
                ->schema(self::buildFields($fields))
                ->columns(2)
        )->values()->all();
    }

    private static function buildFields(array $fields): array
    {
        $components = [];
        foreach ($fields as $key => $def) {
            $fieldKey = self::keyToField($key);
            $components[] = $def['translatable']
                ? self::translatableField($fieldKey, $def)
                : self::singleField($fieldKey, $def);
        }
        return $components;
    }

    private static function translatableField(string $fieldKey, array $def): Tabs
    {
        return Tabs::make($def['label'])
            ->tabs([
                Tabs\Tab::make('Deutsch')->schema([
                    self::buildComponent($fieldKey . '__de', $def)->label($def['label'] . ' (DE)'),
                ]),
                Tabs\Tab::make('English')->schema([
                    self::buildComponent($fieldKey . '__en', $def)->label($def['label'] . ' (EN)'),
                ]),
            ])
            ->columnSpanFull();
    }

    private static function singleField(string $fieldKey, array $def): TextInput|Textarea|FileUpload|Select
    {
        return self::buildComponent($fieldKey, $def)->columnSpanFull();
    }

    private static function buildComponent(string $statePath, array $def): TextInput|Textarea|FileUpload|Select
    {
        $component = match ($def['type']) {
            'textarea' => Textarea::make($statePath)->rows(3),
            'image' => FileUpload::make($statePath)->image()->disk('public')->directory('seo')->imageEditor(),
            'url' => TextInput::make($statePath)->url(),
            'select' => Select::make($statePath)
                ->options($def['options'] ?? [])
                ->default($def['default'] ?? null)
                ->selectablePlaceholder(false),
            default => TextInput::make($statePath)->maxLength(500),
        };

        $component->label($def['label']);
        if ($def['required'] ?? false) {
            $component->required();
        }
        if (! empty($def['rules'])) {
            $component->rules($def['rules']);
        }
        if (! empty($def['placeholder'])) {
            $component->placeholder($def['placeholder']);
        }
        if (! empty($def['helperText'])) {
            $component->helperText($def['helperText']);
        }

        return $component;
    }

    private static function keyToField(string $key): string
    {
        return str_replace('.', '_', $key);
    }
}
