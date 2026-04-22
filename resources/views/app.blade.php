<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <x-seo :seo="$seo" :locale="$locale">
        @if (($isHome ?? false))
            @include('partials.jsonld-localbusiness')
        @endif
        @isset($breadcrumb)
            @include('partials.jsonld-breadcrumb', ['breadcrumb' => $breadcrumb])
        @endisset
        @if (! empty($jsonLd['article']))
            @include('partials.jsonld-article', ['article' => $jsonLd['article']])
        @endif
    </x-seo>

    <link rel="icon" type="image/png" href="/logo.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant:ital,wght@0,300..700;1,300..700&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Jost:ital,wght@0,100..900;1,100..900&family=Mrs+Saint+Delafield&family=Roboto:ital,wght@0,100..900;1,100..900&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">

    @php($safeJsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    <script>
        window.__APP_LOCALE__ = {!! json_encode($locale, $safeJsonFlags) !!};
        window.__APP_CONTENT__ = {!! json_encode($appContent ?? null, $safeJsonFlags) !!};
    </script>

    @php($heroMedia = $appContent['homepage']['hero']['media']['bg'] ?? null)
    @if ($heroMedia && ! empty($heroMedia['srcset']))
        <link rel="preload" as="image"
              imagesrcset="{{ $heroMedia['srcset'] }}"
              imagesizes="100vw" fetchpriority="high">
    @endif

    @php($postCoverSet = $appContent['blog']['post']['cover'] ?? null)
    @if ($postCoverSet && ! empty($postCoverSet['hero']))
        <link rel="preload" as="image"
              href="{{ $postCoverSet['hero'] }}"
              imagesrcset="{{ $postCoverSet['card'] }} 800w, {{ $postCoverSet['hero'] }} 1600w"
              imagesizes="100vw"
              fetchpriority="high">
    @endif

    @viteReactRefresh
    @vite(['src/index.css', 'src/styles/font.css', 'src/main.tsx'])

    @include('partials.tracking')
</head>
<body>
    @include('partials.tracking-body')
    <div id="root"></div>
    @if (! empty($postContent))
        <div id="post-content-html" hidden aria-hidden="true">{!! $postContent !!}</div>
    @endif
</body>
</html>
