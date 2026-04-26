<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class GlobalSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $de = json_decode(file_get_contents(base_path('src/lib/locales/de.json')), true);
        $en = json_decode(file_get_contents(base_path('src/lib/locales/en.json')), true);

        $map = [
            // Header bar
            'header.hours' => $this->pair($de, $en, 'header.hours'),
            'header.hotline' => $this->pair($de, $en, 'header.hotline'),
            'header.locations' => $this->pair($de, $en, 'header.locations'),

            // Nav
            'header.nav.home' => $this->pair($de, $en, 'header.home'),
            'header.nav.menu' => $this->pair($de, $en, 'header.menu'),
            'header.nav.bilder' => $this->pair($de, $en, 'header.bilder'),
            'header.nav.blog' => $this->pair($de, $en, 'header.blog'),
            'header.nav.kontakt' => $this->pair($de, $en, 'header.kontakt'),

            // Footer headings
            'footer.contact_us' => $this->pair($de, $en, 'footer.contact_us'),
            'footer.address' => $this->pair($de, $en, 'footer.address'),
            'footer.opening_hours' => $this->pair($de, $en, 'footer.opening_hours'),

            // Footer content
            'footer.phone' => $this->single($de, 'footer.phone'),
            'footer.email' => $this->single($de, 'footer.email'),
            'footer.address_line1' => $this->pair($de, $en, 'footer.address_line1'),
            'footer.address_line2' => $this->pair($de, $en, 'footer.address_line2'),

            'footer.hours' => [
                'de' => ($de['footer']['hours_mon_thu'] ?? '') . "\n" . ($de['footer']['hours_mon_thu_evening'] ?? ''),
                'en' => ($en['footer']['hours_mon_thu'] ?? '') . "\n" . ($en['footer']['hours_mon_thu_evening'] ?? ''),
            ],

            'footer.company_name' => $this->single($de, 'footer.company_name'),
            'footer.all_rights_reserved' => $this->pair($de, $en, 'footer.all_rights_reserved'),

            // Social (fallback to contact_section.instagram from homepage for instagram)
            'social.facebook_url' => null,
            'social.instagram_url' => $this->single($de, 'homepage.contact_section.instagram'),

            // SEO
            'seo.home.title' => [
                'de' => 'Mamiviet — Vietnamesische Küche & Sushi in Leipzig',
                'en' => 'Mamiviet — Vietnamese Cuisine & Sushi in Leipzig',
            ],
            'seo.home.description' => [
                'de' => 'Authentische vietnamesische Küche und Sushi in Leipzig. Frische Zutaten, traditionelle Rezepte. Reservieren Sie Ihren Tisch.',
                'en' => 'Authentic Vietnamese cuisine and sushi in Leipzig. Fresh ingredients, traditional recipes. Reserve your table.',
            ],
            'seo.bilder.title' => [
                'de' => 'Bilder — Mamiviet Restaurant Leipzig',
                'en' => 'Gallery — Mamiviet Restaurant Leipzig',
            ],
            'seo.bilder.description' => [
                'de' => 'Eindrücke aus dem Restaurant Mamiviet — Gerichte, Atmosphäre, Momente.',
                'en' => 'Impressions from Restaurant Mamiviet — dishes, atmosphere, moments.',
            ],
            'seo.home.keywords' => [
                'de' => 'vietnamesisches restaurant, leipzig, pho, sushi, asiatische küche',
                'en' => 'vietnamese restaurant, leipzig, pho, sushi, asian cuisine',
            ],
            'seo.bilder.keywords' => [
                'de' => 'bilder, galerie, mamiviet, leipzig, restaurant',
                'en' => 'gallery, images, mamiviet, leipzig, restaurant',
            ],
            'seo.home.robots' => 'index, follow',
            'seo.bilder.robots' => 'index, follow',
            'seo.home.og_image' => null,
            'seo.bilder.og_image' => null,
            'seo.og_image' => null,
            'seo.google_site_verification' => null,

            // Tracking & Analytics (leave empty to disable)
            'tracking.ga4_measurement_id' => null,
            'tracking.gtm_container_id' => null,
            'tracking.gbp_place_id' => null,
            'tracking.gbp_cid' => null,
            'tracking.fb_pixel_id' => null,

            // Optional full JSON-LD override. Leave null to use the generated LocalBusiness fallback.
            'schema.local_business_json' => null,
        ];

        foreach ($map as $key => $value) {
            Setting::firstOrCreate(
                ['key' => $key],
                ['value' => $this->normalize($value)]
            );
        }
    }

    private function pair(array $de, array $en, string $path): array
    {
        return [
            'de' => (string) (data_get($de, $path) ?? ''),
            'en' => (string) (data_get($en, $path) ?? ''),
        ];
    }

    private function single(array $source, string $path): ?string
    {
        $value = data_get($source, $path);
        return is_string($value) && $value !== '' ? $value : null;
    }

    private function normalize(mixed $value): mixed
    {
        if (is_array($value)) {
            $cleaned = array_filter($value, fn ($v) => $v !== '' && $v !== null);
            return $cleaned ?: null;
        }
        return $value;
    }
}
