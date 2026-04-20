<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['site', 'site_name', 'Mamiviet'],
            ['site', 'site_email', 'info@merseburger-hof.eu'],
            ['site', 'site_phone', '+49 341 49252244'],
            ['site', 'cuisine', 'Vietnamese'],
            ['site', 'price_range', '€€'],
            ['nap', 'name', 'Restaurant Mamiviet'],
            ['nap', 'street', 'Merseburger Straße 107'],
            ['nap', 'zip', '04177'],
            ['nap', 'city', 'Leipzig'],
            ['nap', 'country', 'DE'],
            ['nap', 'lat', ''],
            ['nap', 'lng', ''],
            ['hours', 'mon_sun_lunch', '11:00-14:00'],
            ['hours', 'mon_sun_dinner', '17:00-22:00'],
            ['social', 'instagram', 'https://www.instagram.com/mami.viet/'],
            ['social', 'facebook', ''],
            ['seo', 'default_og_image', ''],
            ['seo', 'google_site_verification', ''],
        ];

        foreach ($settings as [$group, $key, $value]) {
            Setting::updateOrCreate(
                ['group' => $group, 'key' => $key],
                ['value' => $value]
            );
        }
    }
}
