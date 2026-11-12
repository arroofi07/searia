@php
    $logoClass = $class ?? 'h-24 w-24';
    $logoAlt = $alt ?? (isset($competition) ? $competition->name : 'Aquatic SeaRIA');
    $logoSrc = isset($competition) && $competition?->logoUrl()
        ? $competition->logoUrl()
        : route('brand.image', 'event-logo', false);
@endphp
<img src="{{ $logoSrc }}" alt="{{ $logoAlt }}" width="240" height="240"
    class="{{ $logoClass }} shrink-0 object-contain" decoding="async">
