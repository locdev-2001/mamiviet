@php
    $nap = \App\Models\Setting::group('nap');
    $site = \App\Models\Setting::group('site');
    $hours = \App\Models\Setting::group('hours');
    $social = \App\Models\Setting::group('social');
    $url = rtrim(config('app.url'), '/');

    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'Restaurant',
        'name' => $nap['name'] ?? $site['site_name'] ?? config('app.name'),
        'url' => $url,
        'telephone' => $site['site_phone'] ?? null,
        'email' => $site['site_email'] ?? null,
        'servesCuisine' => $site['cuisine'] ?? 'Vietnamese',
        'priceRange' => $site['price_range'] ?? '€€',
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $nap['street'] ?? '',
            'addressLocality' => $nap['city'] ?? '',
            'postalCode' => $nap['zip'] ?? '',
            'addressCountry' => $nap['country'] ?? 'DE',
        ],
        'image' => $url . '/logo.png',
    ];

    if (! empty($nap['lat']) && ! empty($nap['lng'])) {
        $data['geo'] = [
            '@type' => 'GeoCoordinates',
            'latitude' => (float) $nap['lat'],
            'longitude' => (float) $nap['lng'],
        ];
    }

    $sameAs = array_values(array_filter([
        $social['instagram'] ?? null,
        $social['facebook'] ?? null,
    ]));
    if ($sameAs) {
        $data['sameAs'] = $sameAs;
    }

    $dayMap = [
        'mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday',
        'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday',
    ];
    $weekOrder = array_values($dayMap);

    $openingHours = [];
    foreach ($hours as $key => $range) {
        if (! $range || ! str_contains($range, '-')) continue;
        [$open, $close] = array_map('trim', explode('-', $range, 2));

        $parts = explode('_', $key);
        $startDay = $dayMap[$parts[0] ?? ''] ?? null;
        $endDay = $dayMap[$parts[1] ?? ''] ?? $startDay;
        if (! $startDay) continue;

        $startIdx = array_search($startDay, $weekOrder, true);
        $endIdx = array_search($endDay, $weekOrder, true);
        $days = array_slice($weekOrder, $startIdx, $endIdx - $startIdx + 1);

        $openingHours[] = [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => $days,
            'opens' => $open,
            'closes' => $close,
        ];
    }
    if ($openingHours) {
        $data['openingHoursSpecification'] = $openingHours;
    }
@endphp
<script type="application/ld+json">
{!! json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
