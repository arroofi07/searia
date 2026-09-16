@php
    $markClass = $class ?? 'h-9 w-9';
    $markAlt = $alt ?? 'SeaRIA';
@endphp
<img src="{{ asset('images/logo.svg') }}" alt="{{ $markAlt }}" width="36" height="36"
    class="{{ $markClass }} shrink-0 rounded-[0.65rem]" decoding="async">
