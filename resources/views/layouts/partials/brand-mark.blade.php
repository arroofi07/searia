@php
    $markClass = $class ?? 'h-12 w-auto';
    $markAlt = $alt ?? 'Aquatic SeaRIA';
@endphp
<img src="{{ asset('images/logo.png') }}" alt="{{ $markAlt }}" width="160" height="104"
    class="{{ $markClass }} shrink-0 object-contain object-left" decoding="async">
