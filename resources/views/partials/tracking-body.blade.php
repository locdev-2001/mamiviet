@php
    use App\Models\Setting;
    $gtmId = trim((string) (Setting::raw('tracking.gtm_container_id') ?? ''));
    $gtmValid = (bool) preg_match('/^GTM-[A-Z0-9]+$/', $gtmId);
@endphp

@if ($gtmValid)
    {{-- Google Tag Manager (noscript) --}}
    <noscript>
        <iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}"
                height="0" width="0" style="display:none;visibility:hidden"></iframe>
    </noscript>
@endif
