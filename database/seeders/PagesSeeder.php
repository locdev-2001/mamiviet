<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Section;
use Illuminate\Database\Seeder;

class PagesSeeder extends Seeder
{
    public function run(): void
    {
        $de = json_decode(file_get_contents(base_path('src/lib/locales/de.json')), true);
        $en = json_decode(file_get_contents(base_path('src/lib/locales/en.json')), true);

        $home = Page::updateOrCreate(
            ['id' => 1],
            [
                'slug' => ['de' => 'home', 'en' => 'home'],
                'status' => 'published',
                'seo' => [
                    'de' => [
                        'title' => 'Mamiviet — Vietnamesische Küche & Sushi in Leipzig',
                        'description' => 'Authentische vietnamesische Küche und Sushi in Leipzig. Frische Zutaten, traditionelle Rezepte. Reservieren Sie Ihren Tisch im Restaurant Mamiviet.',
                    ],
                    'en' => [
                        'title' => 'Mamiviet — Vietnamese Cuisine & Sushi in Leipzig',
                        'description' => 'Authentic Vietnamese cuisine and sushi in Leipzig. Fresh ingredients, traditional recipes. Reserve your table at Restaurant Mamiviet.',
                    ],
                ],
            ]
        );

        $bilder = Page::updateOrCreate(
            ['id' => 2],
            [
                'slug' => ['de' => 'bilder', 'en' => 'gallery'],
                'status' => 'published',
                'seo' => [
                    'de' => [
                        'title' => 'Bilder — Mamiviet Restaurant Leipzig',
                        'description' => 'Eindrücke aus dem Restaurant Mamiviet — Gerichte, Atmosphäre, Momente. Folgen Sie uns auf Instagram @mami.viet.',
                    ],
                    'en' => [
                        'title' => 'Gallery — Mamiviet Restaurant Leipzig',
                        'description' => 'Impressions from Restaurant Mamiviet — dishes, atmosphere, moments. Follow us on Instagram @mami.viet.',
                    ],
                ],
            ]
        );

        $sections = $this->buildHomeSections($de, $en);

        foreach ($sections as $order => $section) {
            Section::updateOrCreate(
                ['page_id' => $home->id, 'type' => $section['type']],
                array_merge($section, ['page_id' => $home->id, 'order' => $order + 1])
            );
        }
    }

    private function buildHomeSections(array $de, array $en): array
    {
        $pick = fn (string $path, $fallback = '') => [
            'de' => data_get($de, $path, $fallback),
            'en' => data_get($en, $path, $fallback),
        ];

        $introBody = [
            'de' => trim(($de['homepage']['intro_text1'] ?? '') . "\n\n" . ($de['homepage']['intro_text2'] ?? '')),
            'en' => trim(($en['homepage']['intro_text1'] ?? '') . "\n\n" . ($en['homepage']['intro_text2'] ?? '')),
        ];

        return [
            [
                'type' => 'hero',
                'title' => $pick('homepage.hero_title'),
                'subtitle' => $pick('homepage.welcome_section.tagline'),
                'cta_label' => $pick('homepage.welcome_section.order_online'),
                'cta_link' => ['de' => '#menu', 'en' => '#menu'],
            ],
            [
                'type' => 'intro',
                'title' => $pick('homepage.welcome_section.welcome_title'),
                'body' => $pick('homepage.welcome_section.welcome_text'),
            ],
            [
                'type' => 'featured_dishes',
                'title' => $pick('homepage.welcome_section.cuisine_title'),
                'subtitle' => $pick('homepage.welcome_section.brand_name'),
                'body' => $introBody,
            ],
            [
                'type' => 'gallery_teaser',
                'title' => $pick('homepage.gallery_section.title'),
                'subtitle' => $pick('homepage.gallery_section.subtitle'),
                'cta_label' => $pick('header.bilder', 'Bilder'),
                'cta_link' => ['de' => '/bilder', 'en' => '/en/gallery'],
            ],
            [
                'type' => 'story',
                'title' => $pick('homepage.welcome_section.second_section.title'),
                'body' => $pick('homepage.welcome_section.second_section.text'),
            ],
            [
                'type' => 'contact_cta',
                'title' => $pick('homepage.contact_section.title'),
                'subtitle' => $pick('homepage.contact_section.address'),
                'cta_label' => $pick('header.kontakt', 'Kontakt'),
                'cta_link' => ['de' => 'mailto:info@merseburger-hof.eu', 'en' => 'mailto:info@merseburger-hof.eu'],
            ],
        ];
    }
}
