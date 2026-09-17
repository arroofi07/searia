@php
    $logoClass = $class ?? 'h-24 w-24';
    $logoAlt = $alt ?? 'Fun Swimming SeaRIA Series 1';
@endphp
<img src="{{ asset('images/event-logo.jpg') }}" alt="{{ $logoAlt }}" width="240" height="240"
    class="{{ $logoClass }} shrink-0 object-contain" decoding="async">
