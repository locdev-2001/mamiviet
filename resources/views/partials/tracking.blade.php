@php
    use App\Models\Setting;

    $ga4Id = trim((string) (Setting::raw('tracking.ga4_measurement_id') ?? ''));
    $gtmId = trim((string) (Setting::raw('tracking.gtm_container_id') ?? ''));
    $fbPixelId = trim((string) (Setting::raw('tracking.fb_pixel_id') ?? ''));

    // Only allow Google IDs matching expected format (prevent injection via admin)
    $ga4Valid = (bool) preg_match('/^G-[A-Z0-9]+$/', $ga4Id);
    $gtmValid = (bool) preg_match('/^GTM-[A-Z0-9]+$/', $gtmId);
    $fbValid = (bool) preg_match('/^\d{8,20}$/', $fbPixelId);
@endphp

@if ($ga4Valid)
    {{-- Google Analytics 4 --}}
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4Id }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ $ga4Id }}', { 'anonymize_ip': true });
    </script>
@endif

@if ($gtmValid)
    {{-- Google Tag Manager --}}
    <script>
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $gtmId }}');
    </script>
@endif

@if ($fbValid)
    {{-- Facebook Pixel --}}
    <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '{{ $fbPixelId }}');
        fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ $fbPixelId }}&ev=PageView&noscript=1"/></noscript>
@endif
