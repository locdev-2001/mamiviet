<?php

namespace App\Support;

use App\Models\Setting;

class JsonLdBuilder
{
    public const LOCAL_BUSINESS_DEFAULT_OVERRIDES = [
        '@type' => 'Restaurant',
        'servesCuisine' => 'Vietnamese',
        'priceRange' => '€€',
        'addressCountry' => 'DE',
        'geo' => [
            'latitude' => 51.337725,
            'longitude' => 12.327329,
        ],
    ];

    public static function localBusiness(?string $locale = null): array
    {
        $stored = Setting::raw('schema.local_business_json');

        if (is_array($stored) && $stored !== []) {
            return $stored;
        }

        return self::generatedLocalBusiness($locale);
    }

    public static function website(): array
    {
        $stored = Setting::raw('schema.website_json');

        if (is_array($stored) && $stored !== []) {
            return $stored;
        }

        return self::generatedWebsite();
    }

    public static function organization(): array
    {
        $stored = Setting::raw('schema.organization_json');

        if (is_array($stored) && $stored !== []) {
            return $stored;
        }

        return self::generatedOrganization();
    }

    public static function generatedWebsite(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => Setting::get('footer.company_name') ?: config('app.name'),
            'url' => rtrim(config('app.url'), '/'),
            'inLanguage' => ['de-DE', 'en-US'],
        ];
    }

    public static function generatedOrganization(): array
    {
        $url = rtrim(config('app.url'), '/');
        $sameAs = array_values(array_filter([
            Setting::get('social.instagram_url'),
            Setting::get('social.facebook_url'),
        ]));

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => Setting::get('footer.company_name') ?: 'Mamiviet',
            'url' => $url,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $url . '/logo.png',
            ],
            'sameAs' => $sameAs ?: null,
        ], fn ($value) => $value !== null);
    }

    public static function generatedLocalBusiness(?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $url = rtrim(config('app.url'), '/');
        $static = self::LOCAL_BUSINESS_DEFAULT_OVERRIDES;

        $streetLine = Setting::get('footer.address_line1', $locale);
        $localityLine = Setting::get('footer.address_line2', $locale);
        [$postalCode, $city] = self::parsePostalCodeAndCity($localityLine);

        $placeId = trim((string) (Setting::raw('tracking.gbp_place_id') ?? ''));
        $gbpCid = trim((string) (Setting::raw('tracking.gbp_cid') ?? ''));

        $sameAs = array_values(array_filter([
            Setting::get('social.instagram_url'),
            Setting::get('social.facebook_url'),
            $placeId !== '' ? "https://www.google.com/maps/place/?q=place_id:{$placeId}" : null,
            $gbpCid !== '' && preg_match('/^\d+$/', $gbpCid) ? "https://maps.google.com/?cid={$gbpCid}" : null,
        ]));

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => $static['@type'],
            '@id' => $url . '/#restaurant',
            'name' => Setting::get('footer.company_name') ?: 'Mami Viet',
            'url' => $url,
            'telephone' => Setting::get('footer.phone') ?: null,
            'email' => Setting::get('footer.email') ?: null,
            'servesCuisine' => $static['servesCuisine'] ?? null,
            'priceRange' => $static['priceRange'] ?? null,
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => rtrim((string) $streetLine, ','),
                'addressLocality' => $city,
                'postalCode' => $postalCode,
                'addressCountry' => $static['addressCountry'] ?? null,
            ]),
            'geo' => array_filter([
                '@type' => 'GeoCoordinates',
                'latitude' => $static['geo']['latitude'] ?? null,
                'longitude' => $static['geo']['longitude'] ?? null,
            ]),
            'image' => self::imageUrl($url),
            'sameAs' => $sameAs ?: null,
            'openingHoursSpecification' => self::openingHours($locale) ?: null,
        ], fn ($value) => $value !== null);
    }

    private static function openingHours(string $locale): array
    {
        $hoursRaw = (string) (Setting::get('footer.hours', $locale) ?? '');
        $openingHours = [];
        $dayOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        foreach (preg_split('/\r?\n/', $hoursRaw) as $line) {
            if (! preg_match('/(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})/', $line, $matches)) {
                continue;
            }

            $openingHours[] = [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => $dayOfWeek,
                'opens' => $matches[1],
                'closes' => $matches[2],
            ];
        }

        return $openingHours;
    }

    private static function parsePostalCodeAndCity(?string $line): array
    {
        if (! $line) {
            return ['', ''];
        }

        $line = trim($line);
        if (preg_match('/^(\d{4,5})\s+(.+)$/', $line, $matches)) {
            return [$matches[1], $matches[2]];
        }

        return ['', $line];
    }

    private static function imageUrl(string $url): string
    {
        $image = Setting::raw('seo.home.og_image') ?: Setting::raw('seo.og_image');

        if (! $image) {
            return $url . '/logo.png';
        }

        if (str_starts_with($image, 'http')) {
            return $image;
        }

        if (str_starts_with($image, '/')) {
            return $url . $image;
        }

        return $url . '/storage/' . ltrim($image, '/');
    }
}
