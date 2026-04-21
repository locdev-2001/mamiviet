@php
    use App\Models\Setting;

    $url = rtrim(config('app.url'), '/');
    $name = (string) (Setting::get('footer.company_name') ?: 'Mamiviet');

    $sameAs = array_values(array_filter([
        Setting::get('social.instagram_url'),
        Setting::get('social.facebook_url'),
    ]));

    $data = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $name,
        'url' => $url,
        'logo' => [
            '@type' => 'ImageObject',
            'url' => $url . '/logo.png',
        ],
        'sameAs' => $sameAs ?: null,
    ], fn ($v) => $v !== null);
@endphp
<script type="application/ld+json">
{!! json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}
</script>
