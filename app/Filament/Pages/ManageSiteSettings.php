<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageSiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Site Settings';

    protected static ?string $title = 'Site Settings';

    protected static ?int $navigationSort = -1;

    protected static string $view = 'filament.pages.manage-site-settings';

    public array $data = [];

    private const FIELDS = [
        'site' => ['site_name', 'site_email', 'site_phone', 'cuisine', 'price_range'],
        'nap' => ['name', 'street', 'zip', 'city', 'country', 'lat', 'lng'],
        'hours' => ['mon_sun_lunch', 'mon_sun_dinner'],
        'social' => ['instagram', 'facebook'],
        'seo' => ['google_site_verification', 'default_og_image'],
    ];

    public function mount(): void
    {
        $values = [];
        foreach (self::FIELDS as $group => $keys) {
            foreach ($keys as $key) {
                $values["{$group}_{$key}"] = Setting::value($group, $key, '');
            }
        }
        $this->form->fill($values);
    }

    public function form(Form $form): Form
    {
        $napIncomplete = empty(Setting::value('nap', 'street'))
            || str_contains((string) Setting::value('nap', 'street'), 'TODO');

        return $form->statePath('data')->schema([
            Section::make('Site Identity')->schema([
                TextInput::make('site_site_name')->label('Site name')->required(),
                TextInput::make('site_site_email')->label('Email')->email(),
                TextInput::make('site_site_phone')->label('Phone'),
                TextInput::make('site_cuisine')->label('Cuisine'),
                TextInput::make('site_price_range')->label('Price range'),
            ])->columns(2),

            Section::make('NAP (Name / Address / Phone)')
                ->description($napIncomplete ? '⚠ Vui lòng cập nhật địa chỉ thực — schema.org cần cho SEO local' : null)
                ->schema([
                    TextInput::make('nap_name')->label('Business name'),
                    TextInput::make('nap_street')->label('Street'),
                    TextInput::make('nap_zip')->label('ZIP'),
                    TextInput::make('nap_city')->label('City'),
                    TextInput::make('nap_country')->label('Country code')->maxLength(2),
                    TextInput::make('nap_lat')->label('Latitude')->numeric(),
                    TextInput::make('nap_lng')->label('Longitude')->numeric(),
                ])->columns(2),

            Section::make('Opening Hours')->schema([
                TextInput::make('hours_mon_sun_lunch')->label('Mon–Sun lunch')->placeholder('11:30-15:00'),
                TextInput::make('hours_mon_sun_dinner')->label('Mon–Sun dinner')->placeholder('17:30-22:00'),
            ])->columns(2),

            Section::make('Social')->schema([
                TextInput::make('social_instagram')->label('Instagram URL')->url(),
                TextInput::make('social_facebook')->label('Facebook URL')->url(),
            ])->columns(2),

            Section::make('SEO')->schema([
                TextInput::make('seo_google_site_verification')->label('Google site verification token'),
                FileUpload::make('seo_default_og_image')->label('Default OG image')
                    ->image()->directory('seo')->disk('public'),
            ]),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        DB::transaction(function () use ($state) {
            foreach (self::FIELDS as $group => $keys) {
                foreach ($keys as $key) {
                    Setting::updateOrCreate(
                        ['group' => $group, 'key' => $key],
                        ['value' => (string) ($state["{$group}_{$key}"] ?? '')],
                    );
                }
            }
        });

        Notification::make()->title('Settings saved')->success()->send();
    }
}
