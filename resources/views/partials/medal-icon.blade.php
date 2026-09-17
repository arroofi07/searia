@php
    $metal = $metal ?? null;
    $pdf = $pdf ?? false;
@endphp
@if (is_string($metal) && $metal !== '')
    @if ($pdf)
        @php
            $fill = match ($metal) {
                'gold' => '#eab308',
                'silver' => '#cbd5e1',
                'bronze' => '#cd7f32',
                default => '#94a3b8',
            };
            $edge = match ($metal) {
                'gold' => '#b45309',
                'silver' => '#64748b',
                'bronze' => '#7c4a1a',
                default => '#64748b',
            };
        @endphp
        <span title="{{ \App\Support\MedalIcon::label($metal) }}" style="display:inline-block;width:11px;height:11px;border-radius:50%;background:{{ $fill }};border:0.7px solid {{ $edge }};"></span>
    @else
        <img
            src="{{ \App\Support\MedalIcon::webSrc($metal) }}"
            alt="{{ \App\Support\MedalIcon::label($metal) }}"
            width="14"
            height="18"
            class="medal-icon"
            style="width:14px;height:18px;vertical-align:middle"
        >
    @endif
@endif
