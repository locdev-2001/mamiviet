@props(['seo' => [], 'locale' => 'de'])

<title>{{ $seo['title'] ?? config('app.name') }}</title>
<meta name="description" content="{{ $seo['description'] ?? '' }}">
@if (! empty($seo['keywords']))
    <meta name="keywords" content="{{ $seo['keywords'] }}">
@endif
<meta name="robots" content="{{ $seo['robots'] ?? 'index, follow' }}">
<link rel="canonical" href="{{ $seo['canonical'] ?? url()->current() }}">

@foreach (($seo['hreflang'] ?? []) as $lang => $href)
    <link rel="alternate" hreflang="{{ $lang }}" href="{{ $href }}">
@endforeach
<link rel="alternate" hreflang="x-default" href="{{ $seo['hreflang']['de'] ?? url()->current() }}">

<meta property="og:type" content="website">
<meta property="og:title" content="{{ $seo['title'] ?? config('app.name') }}">
<meta property="og:description" content="{{ $seo['description'] ?? '' }}">
<meta property="og:url" content="{{ $seo['canonical'] ?? url()->current() }}">
<meta property="og:image" content="{{ url($seo['og_image'] ?? '/logo.png') }}">
@php
    $ogLocaleMap = ['de' => 'de_DE', 'en' => 'en_US'];
    $ogLocale = $ogLocaleMap[$locale] ?? 'de_DE';
@endphp
<meta property="og:locale" content="{{ $ogLocale }}">
@foreach ($ogLocaleMap as $code => $ogCode)
    @if ($code !== $locale)
        <meta property="og:locale:alternate" content="{{ $ogCode }}">
    @endif
@endforeach

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo['title'] ?? config('app.name') }}">
<meta name="twitter:description" content="{{ $seo['description'] ?? '' }}">
<meta name="twitter:image" content="{{ url($seo['og_image'] ?? '/logo.png') }}">

@if (! empty($seo['google_site_verification']))
    <meta name="google-site-verification" content="{{ $seo['google_site_verification'] }}">
@endif

@include('partials.jsonld-website')

{{ $slot }}
