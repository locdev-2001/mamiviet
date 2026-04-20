@php
    $name = \App\Models\Setting::get('footer.company_name') ?: config('app.name');
    $url = rtrim(config('app.url'), '/');
@endphp
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => $name,
    'url' => $url,
    'inLanguage' => ['de-DE', 'en-US'],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
