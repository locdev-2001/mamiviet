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
    </x-seo>

    <link rel="icon" type="image/png" href="/logo.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant:ital,wght@0,300..700;1,300..700&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Jost:ital,wght@0,100..900;1,100..900&family=Mrs+Saint+Delafield&family=Roboto:ital,wght@0,100..900;1,100..900&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">

    <script>
        window.__APP_LOCALE__ = @json($locale);
    </script>

    @viteReactRefresh
    @vite(['src/index.css', 'src/styles/font.css', 'src/main.tsx'])
</head>
<body>
    <div id="root"></div>
</body>
</html>
