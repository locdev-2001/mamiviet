@php
    use App\Models\Setting;

    $url = rtrim(config('app.url'), '/');
    $locale = app()->getLocale();

    $name = Setting::get('footer.company_name') ?: 'Mami Viet';
    $phone = Setting::get('footer.phone');
    $email = Setting::get('footer.email');

    $streetLine = Setting::get('footer.address_line1', $locale);
    $localityLine = Setting::get('footer.address_line2', $locale);
    [$postalCode, $city] = (function (?string $line) {
        if (! $line) return ['', ''];
        $line = trim($line);
        if (preg_match('/^(\d{4,5})\s+(.+)$/', $line, $m)) return [$m[1], $m[2]];
        return ['', $line];
    })($localityLine);

    $hoursRaw = (string) (Setting::get('footer.hours', $locale) ?? '');
    $openingHours = [];
    $dayOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    foreach (preg_split('/\r?\n/', $hoursRaw) as $line) {
        if (! preg_match('/(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})/', $line, $m)) continue;
        $openingHours[] = [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => $dayOfWeek,
            'opens' => $m[1],
            'closes' => $m[2],
        ];
    }

    $sameAs = array_values(array_filter([
        Setting::get('social.instagram_url'),
        Setting::get('social.facebook_url'),
    ]));

    $data = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Restaurant',
        'name' => $name,
        'url' => $url,
        'telephone' => $phone ?: null,
        'email' => $email ?: null,
        'servesCuisine' => 'Vietnamese',
        'priceRange' => '€€',
        'address' => array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => rtrim((string) $streetLine, ','),
            'addressLocality' => $city,
            'postalCode' => $postalCode,
            'addressCountry' => 'DE',
        ]),
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => 51.337725,
            'longitude' => 12.327329,
        ],
        'image' => (function () use ($url) {
            $img = Setting::raw('seo.home.og_image') ?: Setting::raw('seo.og_image');
            if (! $img) return $url . '/logo.png';
            if (str_starts_with($img, 'http')) return $img;
            if (str_starts_with($img, '/')) return $url . $img;
            return $url . '/storage/' . ltrim($img, '/');
        })(),
        'sameAs' => $sameAs ?: null,
        'openingHoursSpecification' => $openingHours ?: null,
    ], fn ($v) => $v !== null);
@endphp
<script type="application/ld+json">
{!! json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
