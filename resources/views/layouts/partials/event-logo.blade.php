@php
    use App\Support\PublicAssets;

    $logoClass = $class ?? 'h-24 w-24';
    $logoAlt = $alt ?? (isset($competition) ? $competition->name : 'Aquatic SeaRIA');
    $logoSrc = isset($competition) && $competition?->logoUrl()
        ? $competition->logoUrl()
        : PublicAssets::brandImageUrl('event-logo');
@endphp
<img src="{{ $logoSrc }}" alt="{{ $logoAlt }}" width="72" height="72"
    class="{{ $logoClass }} shrink-0 object-contain" decoding="async">
