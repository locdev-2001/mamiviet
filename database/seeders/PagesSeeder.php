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

        $home = Page::where('slug->de', 'home')->first();
        if (! $home) {
            $home = Page::create([
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
            ]);
        }

        if (! Page::where('slug->de', 'bilder')->exists()) {
            Page::create([
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
            ]);
        }

        foreach ($this->buildHomepageSections($de, $en) as $order => [$key, $content, $data]) {
            $section = Section::firstOrCreate(
                ['page_id' => $home->id, 'key' => $key],
                [
                    'enabled' => true,
                    'order' => $order + 1,
                    'data' => $data,
                ]
            );

            $changed = false;

            if (blank($section->getTranslation('content', 'de', false))) {
                $section->setTranslation('content', 'de', $content['de']);
                $changed = true;
            }

            if (blank($section->getTranslation('content', 'en', false))) {
                $section->setTranslation('content', 'en', $content['en']);
                $changed = true;
            }

            if ($section->data === null && $data !== null) {
                $section->data = $data;
                $changed = true;
            }

            if ($changed) {
                $section->save();
            }
        }
    }

    private function buildHomepageSections(array $de, array $en): array
    {
        $de = $de['homepage'] ?? [];
        $en = $en['homepage'] ?? [];

        $pair = fn (array $dePath, array $enPath) => [
            'de' => $this->pick($de, $dePath),
            'en' => $this->pick($en, $enPath),
        ];

        return [
            ['hero', $this->sectionPair(
                ['title' => 'hero_title'],
                $de,
                $en,
            ), null],

            ['welcome', $this->sectionPair([
                'brand_name' => 'welcome_section.brand_name',
                'tagline' => 'welcome_section.tagline',
                'cuisine_label' => 'welcome_section.cuisine_title',
                'title' => 'welcome_section.welcome_title',
                'body' => 'welcome_section.welcome_text',
                'cta_label' => 'welcome_section.order_online',
            ], $de, $en), null],

            ['welcome_second', $this->sectionPair([
                'cuisine_label' => 'welcome_section.cuisine_title',
                'title' => 'welcome_section.second_section.title',
                'body' => 'welcome_section.second_section.text',
                'cta_label' => 'welcome_section.second_section.read_more',
            ], $de, $en), null],

            ['order', $this->sectionPair([
                'title' => 'order_section.title',
                'takeaway' => 'order_section.takeaway',
                'delivery' => 'order_section.delivery',
                'reservation' => 'order_section.reservation',
                'free_delivery' => 'order_section.free_delivery',
                'cta_label' => 'order_section.order_button',
            ], $de, $en), null],

            ['reservation', $this->sectionPair([
                'title' => 'reservation_section.title',
                'subtitle' => 'reservation_section.subtitle',
                'note' => 'reservation_section.note',
                'cta_label' => 'reservation_section.submit',
                'overlay_text' => 'reservation_section.overlay_title',
                'overlay_subtitle' => 'reservation_section.overlay_subtitle',
            ], $de, $en), null],

            ['contact', $this->sectionPair([
                'title' => 'contact_section.title',
                'restaurant_name' => 'contact_section.restaurant_name',
                'address' => 'contact_section.address',
                'phone' => 'contact_section.phone',
                'email' => 'contact_section.email',
                'instagram_label' => 'contact_section.ig_name',
            ], $de, $en), [
                'instagram_url' => $de['contact_section']['instagram'] ?? null,
                'map_embed' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2492.5538579536387!2d12.32732896718416!3d51.33772531772187!2m3!1f0!2f0!3f0!3m2!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47a6f79bfe53d701%3A0x89dcff2537a6fce5!2sMami%20Viet%20-%20SUSHI%20-%20Asian%20Cuisine!5e0!3m2!1svi!2s!4v1761356364892!5m2!1svi!2s',
            ]],

            ['gallery_slider', $this->sectionPair([
                'title' => 'gallery_section.title',
                'subtitle' => 'gallery_section.subtitle',
            ], $de, $en), null],

            ['intro', [
                'de' => [
                    'title' => $de['intro_title'] ?? '',
                    'text1' => $de['intro_text1'] ?? '',
                    'text2' => $de['intro_text2'] ?? '',
                ],
                'en' => [
                    'title' => $en['intro_title'] ?? '',
                    'text1' => $en['intro_text1'] ?? '',
                    'text2' => $en['intro_text2'] ?? '',
                ],
            ], null],
        ];
    }

    private function sectionPair(array $fields, array $de, array $en): array
    {
        return [
            'de' => array_map(fn ($path) => $this->pick($de, explode('.', $path)), $fields),
            'en' => array_map(fn ($path) => $this->pick($en, explode('.', $path)), $fields),
        ];
    }

    private function pick(array $source, array|string $path): string
    {
        $segments = is_string($path) ? explode('.', $path) : $path;
        $value = $source;

        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return '';
            }
            $value = $value[$segment];
        }

        return is_string($value) ? $value : '';
    }
}
