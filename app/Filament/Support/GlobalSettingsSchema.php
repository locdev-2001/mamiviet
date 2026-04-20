<?php

namespace App\Filament\Support;

/**
 * Single source of truth for global site settings.
 *
 * Structure: tab => section => [key => definition]
 *
 * Definition keys:
 *  - label: human label
 *  - type: text | textarea | url | image
 *  - translatable: bool (stores {de, en} map)
 *  - rules?: array of Laravel validation rules
 *  - placeholder?: string
 *  - required?: bool
 */
class GlobalSettingsSchema
{
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
                    'seo.home.title' => ['label' => 'Title (≤60 chars)', 'type' => 'text', 'translatable' => true, 'rules' => ['nullable', 'max:60']],
                    'seo.home.description' => ['label' => 'Description (≤160 chars)', 'type' => 'textarea', 'translatable' => true, 'rules' => ['nullable', 'max:160']],
                ],
                'Bilder page (/bilder)' => [
                    'seo.bilder.title' => ['label' => 'Title (≤60 chars)', 'type' => 'text', 'translatable' => true, 'rules' => ['nullable', 'max:60']],
                    'seo.bilder.description' => ['label' => 'Description (≤160 chars)', 'type' => 'textarea', 'translatable' => true, 'rules' => ['nullable', 'max:160']],
                ],
                'Site-wide defaults' => [
                    'seo.og_image' => ['label' => 'Default OG image (1200×630)', 'type' => 'image', 'translatable' => false],
                    'seo.google_site_verification' => ['label' => 'Google site verification token', 'type' => 'text', 'translatable' => false],
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
