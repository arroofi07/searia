@php
    $metal = $metal ?? null;
    $pdf = $pdf ?? false;
@endphp
@if (is_string($metal) && $metal !== '')
    <img
        src="{{ $pdf ? \App\Support\MedalIcon::pdfSrc($metal) : \App\Support\MedalIcon::webSrc($metal) }}"
        alt="{{ \App\Support\MedalIcon::label($metal) }}"
        width="14"
        height="18"
        class="medal-icon"
        style="width:14px;height:18px;vertical-align:middle"
    >
@endif
