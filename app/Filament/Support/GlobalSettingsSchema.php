<?php

namespace App\Filament\Support;

/**
 * Single source of truth for global site settings.
 *
 * Structure: tab => section => [key => definition]
 *
 * Definition keys:
 *  - label: human label
 *  - type: text | textarea | url | image | select
 *  - translatable: bool (stores {de, en} map)
 *  - rules?: array of Laravel validation rules
 *  - placeholder?: string
 *  - required?: bool
 *  - options?: array (for type=select, [value => label])
 *  - default?: mixed (default when value is empty)
 *  - helperText?: string
 */
class GlobalSettingsSchema
{
    public const ROBOTS_OPTIONS = [
        'index, follow' => 'Index + Follow (default — allow Google to index)',
        'noindex, follow' => 'Noindex + Follow (hide from results, follow links)',
        'noindex, nofollow' => 'Noindex + Nofollow (hide completely)',
    ];

    public const TABS = [
        'header' => [
            'label' => 'Header',
            'sections' => [
                'Top info bar' => [
                    'header.hours' => ['label' => 'Opening hours (header strip)', 'type' => 'text', 'translatable' => true],
                    'header.hotline' => ['label' => 'Hotline', 'type' => 'text', 'translatable' => true],
                    'header.locations' => ['label' => 'Location line', 'type' => 'text', 'translatable' => true],
                ],
                'Navigation labels' => [
                    'header.nav.home' => ['label' => 'Home label', 'type' => 'text', 'translatable' => true],
                    'header.nav.menu' => ['label' => 'Menu label', 'type' => 'text', 'translatable' => true],
                    'header.nav.bilder' => ['label' => 'Bilder label', 'type' => 'text', 'translatable' => true],
                    'header.nav.blog' => ['label' => 'Blog label', 'type' => 'text', 'translatable' => true],
                    'header.nav.kontakt' => ['label' => 'Kontakt label', 'type' => 'text', 'translatable' => true],
                ],
            ],
        ],
        'footer' => [
            'label' => 'Footer',
            'sections' => [
                'Column headings' => [
                    'footer.contact_us' => ['label' => 'Contact us heading', 'type' => 'text', 'translatable' => true],
                    'footer.address' => ['label' => 'Address heading', 'type' => 'text', 'translatable' => true],
                    'footer.opening_hours' => ['label' => 'Opening hours heading', 'type' => 'text', 'translatable' => true],
                ],
                'Contact info' => [
                    'footer.phone' => ['label' => 'Phone', 'type' => 'text', 'translatable' => false],
                    'footer.email' => ['label' => 'Email', 'type' => 'text', 'translatable' => false],
                    'footer.address_line1' => ['label' => 'Address line 1', 'type' => 'text', 'translatable' => true],
                    'footer.address_line2' => ['label' => 'Address line 2', 'type' => 'text', 'translatable' => true],
                ],
                'Opening hours (footer)' => [
                    'footer.hours' => [
                        'label' => 'Hours (one line per display row)',
                        'type' => 'textarea',
                        'translatable' => true,
                        'placeholder' => "Mo. - So.: 11:00 - 14:00\nMo. - So.: 17:00 - 22:00",
                    ],
                ],
                'Brand & social' => [
                    'footer.company_name' => ['label' => 'Company name', 'type' => 'text', 'translatable' => false],
                    'footer.all_rights_reserved' => ['label' => 'All rights reserved text', 'type' => 'text', 'translatable' => true],
                    'social.facebook_url' => ['label' => 'Facebook URL', 'type' => 'url', 'translatable' => false, 'rules' => ['nullable', 'url']],
                    'social.instagram_url' => ['label' => 'Instagram URL', 'type' => 'url', 'translatable' => false, 'rules' => ['nullable', 'url']],
                ],
            ],
        ],
        'seo' => [
            'label' => 'SEO',
            'sections' => [
                'Home page (/)' => [
                    'seo.home.title' => ['label' => 'Title (Google recommends ≤60 chars)', 'type' => 'text', 'translatable' => true, 'rules' => ['nullable', 'max:255']],
                    'seo.home.description' => ['label' => 'Description (Google recommends ≤160 chars)', 'type' => 'textarea', 'translatable' => true, 'rules' => ['nullable', 'max:500']],
                    'seo.home.keywords' => ['label' => 'Keywords (comma separated)', 'type' => 'text', 'translatable' => true, 'rules' => ['nullable', 'max:500'], 'placeholder' => 'vietnamesisches restaurant, leipzig, pho, sushi'],
                    'seo.home.robots' => ['label' => 'Robots directive', 'type' => 'select', 'translatable' => false, 'options' => self::ROBOTS_OPTIONS, 'default' => 'index, follow'],
                    'seo.home.og_image' => ['label' => 'OG image (Home, 1200×630) — optional, fallback to site default', 'type' => 'image', 'translatable' => false],
                ],
                'Bilder page (/bilder)' => [
                    'seo.bilder.title' => ['label' => 'Title (Google recommends ≤60 chars)', 'type' => 'text', 'translatable' => true, 'rules' => ['nullable', 'max:255']],
                    'seo.bilder.description' => ['label' => 'Description (Google recommends ≤160 chars)', 'type' => 'textarea', 'translatable' => true, 'rules' => ['nullable', 'max:500']],
                    'seo.bilder.keywords' => ['label' => 'Keywords (comma separated)', 'type' => 'text', 'translatable' => true, 'rules' => ['nullable', 'max:500'], 'placeholder' => 'bilder, galerie, mamiviet, leipzig'],
                    'seo.bilder.robots' => ['label' => 'Robots directive', 'type' => 'select', 'translatable' => false, 'options' => self::ROBOTS_OPTIONS, 'default' => 'index, follow'],
                    'seo.bilder.og_image' => ['label' => 'OG image (Bilder, 1200×630) — optional, fallback to site default', 'type' => 'image', 'translatable' => false],
                ],
                'Site-wide defaults' => [
                    'seo.og_image' => ['label' => 'Default OG image (1200×630)', 'type' => 'image', 'translatable' => false],
                    'seo.google_site_verification' => ['label' => 'Google site verification token', 'type' => 'text', 'translatable' => false],
                ],
            ],
        ],
        'tracking' => [
            'label' => 'Tracking',
            'sections' => [
                'Google Analytics' => [
                    'tracking.ga4_measurement_id' => [
                        'label' => 'GA4 Measurement ID',
                        'type' => 'text',
                        'translatable' => false,
                        'placeholder' => 'G-XXXXXXXXXX',
                        'helperText' => 'Google Analytics → Admin → Data Streams → copy "Measurement ID". Leave empty to disable.',
                    ],
                    'tracking.gtm_container_id' => [
                        'label' => 'Google Tag Manager container ID (optional)',
                        'type' => 'text',
                        'translatable' => false,
                        'placeholder' => 'GTM-XXXXXXX',
                        'helperText' => 'Use GTM if you need multiple tags. Alternative to GA4 direct. Load in parallel supported.',
                    ],
                ],
                'Google Business Profile' => [
                    'tracking.gbp_place_id' => [
                        'label' => 'Google Business Place ID',
                        'type' => 'text',
                        'translatable' => false,
                        'placeholder' => 'ChIJxxxxxxxxxxxxxxxxxxx',
                        'helperText' => 'Find at https://developers.google.com/maps/documentation/places/web-service/place-id. Used in schema.org LocalBusiness for richer Google results.',
                    ],
                    'tracking.gbp_cid' => [
                        'label' => 'Google Business CID (numeric)',
                        'type' => 'text',
                        'translatable' => false,
                        'placeholder' => '12345678901234567890',
                        'helperText' => 'Optional. Extract from Google Maps URL after "?cid=". Used as secondary identifier.',
                    ],
                ],
                'Social pixels (optional)' => [
                    'tracking.fb_pixel_id' => [
                        'label' => 'Facebook Pixel ID',
                        'type' => 'text',
                        'translatable' => false,
                        'placeholder' => '1234567890123456',
                        'helperText' => 'Facebook Business Manager → Events Manager → Pixel → copy ID. Leave empty to disable.',
                    ],
                ],
            ],
        ],
    ];

    /**
     * Flat list of all keys with their definitions. Used by seeder.
     */
    public static function allKeys(): array
    {
        $out = [];
        foreach (self::TABS as $tab) {
            foreach ($tab['sections'] as $sectionFields) {
                foreach ($sectionFields as $key => $def) {
                    $out[$key] = $def;
                }
            }
        }
        return $out;
    }

    public static function isTranslatable(string $key): bool
    {
        return (bool) (self::allKeys()[$key]['translatable'] ?? false);
    }
}
